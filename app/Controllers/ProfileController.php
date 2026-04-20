<?php

/**
 * ProfileController.php
 * Allows users to update their personal information (like location).
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

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => implode('<br>', $errors)];
            header('Location: ?page=profile');
            exit;
        }

        try {
            $db->beginTransaction();

            // Resolve location ID
            $locId = self::upsertLocation($db, $location);

            // Update user
            $stmt = $db->prepare("UPDATE users SET name = ?, location_id = ? WHERE id = ?");
            $stmt->execute([$name, $locId, $userId]);

            // Update session name if changed
            $_SESSION['name'] = $name;

            $db->commit();
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Profile updated successfully.'];
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Failed to update profile.'];
        }

        header('Location: ?page=profile');
        exit;
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
