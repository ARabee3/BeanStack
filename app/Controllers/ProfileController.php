<?php

/**
 * ProfileController.php
 * Allows users to update their personal information (like location and profile picture).
 */

require_once __DIR__ . '/../../config/Database.php';

class ProfileController
{
    public static function index(): void
    {
        requireLogin();

        $db = Database::connect();
        $userId = $_SESSION['user_id'];

        $stmt = $db->prepare(
            "SELECT u.*, l.details AS location
             FROM users u
             LEFT JOIN locations l ON l.id = u.location_id
             WHERE u.id = ?"
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $pageTitle = 'My Profile';
        $activeNav = 'profile';

        include __DIR__ . '/../../views/profile.php';
    }

    public static function update(): void
    {
        requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=profile');
            exit;
        }

        $db = Database::connect();
        $userId = $_SESSION['user_id'];
        $name = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');

        $errors = [];
        if ($name === '') {
            $errors[] = 'Full name is required.';
        } elseif (!preg_match("/^[a-zA-Z\s]{3,255}$/", $name)) {
            $errors[] = 'Name must be at least 3 characters and contain only letters and spaces.';
        }

        if ($location === '') {
            $errors[] = 'Location cannot be empty.';
        }

        // Handle Profile Picture Upload
        $profilePicPath = null;
        if (!empty($_FILES['picture']['name'])) {
            $profilePicPath = self::processProfilePicture($_FILES['picture'], $errors);
        }

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => implode('<br>', $errors)];
            header('Location: ?page=profile');
            exit;
        }

        try {
            $db->beginTransaction();

            // Fetch current user data to handle old picture deletion
            $currentStmt = $db->prepare("SELECT profile_pic FROM users WHERE id = ?");
            $currentStmt->execute([$userId]);
            $currentUser = $currentStmt->fetch();

            // Resolve location ID
            $locId = self::upsertLocation($db, $location);

            // Update user
            if ($profilePicPath) {
                $stmt = $db->prepare("UPDATE users SET name = ?, location_id = ?, profile_pic = ? WHERE id = ?");
                $stmt->execute([$name, $locId, $profilePicPath, $userId]);
                
                // Delete old picture if it exists
                if (!empty($currentUser['profile_pic'])) {
                    self::deleteOldFile($currentUser['profile_pic']);
                }
            } else {
                $stmt = $db->prepare("UPDATE users SET name = ?, location_id = ? WHERE id = ?");
                $stmt->execute([$name, $locId, $userId]);
            }

            // Update session name if changed
            $_SESSION['name'] = $name;

            $db->commit();
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Profile updated successfully.'];
        } catch (Exception $e) {
            if ($profilePicPath) self::deleteOldFile($profilePicPath);
            $db->rollBack();
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Failed to update profile.'];
        }

        header('Location: ?page=profile');
        exit;
    }

    private static function processProfilePicture(array $file, array &$errors): ?string
    {
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Could not upload profile picture. Please try again.';
            return null;
        }

        $maxBytes = 2 * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            $errors[] = 'Profile picture must be 2 MB or less.';
            return null;
        }

        $tmpName = $file['tmp_name'];
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        $mimeType = mime_content_type($tmpName) ?: '';
        if (!isset($allowedMimes[$mimeType])) {
            $errors[] = 'Only JPG, PNG, and WEBP images are allowed.';
            return null;
        }

        $uploadDir = __DIR__ . '/../../public/uploads/profile_pictures';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            $errors[] = 'Could not prepare upload directory.';
            return null;
        }

        $fileName = 'profile_' . bin2hex(random_bytes(8)) . '.' . $allowedMimes[$mimeType];
        $targetPath = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            $errors[] = 'Failed to save profile picture.';
            return null;
        }

        return 'uploads/profile_pictures/' . $fileName;
    }

    private static function deleteOldFile(string $relativePath): void
    {
        $absolutePath = __DIR__ . '/../../public/' . ltrim($relativePath, '/');
        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }

    private static function upsertLocation(PDO $db, string $details): int
    {
        $stmt = $db->prepare("SELECT id FROM locations WHERE details = :d");
        $stmt->execute([':d' => $details]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) return (int) $row['id'];

        $db->prepare("INSERT INTO locations (details) VALUES (:d)")
           ->execute([':d' => $details]);

        return (int) $db->lastInsertId();
    }
}
