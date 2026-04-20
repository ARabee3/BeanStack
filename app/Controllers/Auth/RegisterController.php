<?php

class RegisterController
{
  public static function handleRequest(): array
  {
    $result = [
      'success' => false,
      'errors' => [],
    ];

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
      return $result;
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $termsAccepted = isset($_POST['terms']);

    if ($name === '') {
      $result['errors']['name'] = 'Full name is required.';
    } elseif (!preg_match("/^[a-zA-Z\s]{3,255}$/", $name)) {
      $result['errors']['name'] = 'Name must be at least 3 characters and contain only letters and spaces.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $result['errors']['email'] = 'Enter a valid email address.';
    }

    if ($password === '' || strlen($password) < 8) {
      $result['errors']['password'] = 'Password must be at least 8 characters.';
    }

    if ($confirm === '' || $confirm !== $password) {
      $result['errors']['confirm'] = 'Passwords must match.';
    }

    if ($location === '') {
      $result['errors']['location'] = 'Location is required.';
    }

    if (!$termsAccepted) {
      $result['errors']['terms'] = 'You must accept the terms to continue.';
    }

    $profilePicPath = self::processProfilePicture($_FILES['picture'] ?? null, $result['errors']);

    if (!empty($result['errors'])) {
      return $result;
    }

    try {
      $conn = Database::connect();

      $existingStmt = $conn->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
      $existingStmt->execute(['email' => $email]);
      if ($existingStmt->fetch()) {
        $result['errors']['email'] = 'This email is already registered.';

        if ($profilePicPath !== null) {
          self::deleteUploadedFile($profilePicPath);
        }

        return $result;
      }

      $locationId = self::resolveLocationId($conn, $location);

      $insertStmt = $conn->prepare(
        'INSERT INTO users (name, email, password, location_id, profile_pic, role, isActive)
                 VALUES (:name, :email, :password, :location_id, :profile_pic, :role, :isActive)'
      );

      $insertStmt->execute([
        'name' => $name,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'location_id' => $locationId,
        'profile_pic' => $profilePicPath,
        'role' => 'user',
        'isActive' => 1,
      ]);

      $_POST = [];
      $result['success'] = true;
    } catch (Throwable $e) {
      if ($profilePicPath !== null) {
        self::deleteUploadedFile($profilePicPath);
      }

      $result['errors']['general'] = 'Something went wrong while creating your account. Please try again.';
    }

    return $result;
  }

  private static function resolveLocationId(PDO $conn, string $locationDetails): ?int
  {
    $findStmt = $conn->prepare('SELECT id FROM locations WHERE details = :details LIMIT 1');
    $findStmt->execute(['details' => $locationDetails]);
    $existing = $findStmt->fetch();

    if ($existing) {
      return (int) $existing['id'];
    }

    $insertStmt = $conn->prepare('INSERT INTO locations (details) VALUES (:details)');
    $insertStmt->execute(['details' => $locationDetails]);

    return (int) $conn->lastInsertId();
  }

  private static function processProfilePicture(?array $file, array &$errors): ?string
  {
    if ($file === null || !isset($file['error'])) {
      return null;
    }

    if ((int) $file['error'] === UPLOAD_ERR_NO_FILE) {
      return null;
    }

    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
      $errors['picture'] = 'Could not upload profile picture. Please try again.';
      return null;
    }

    $maxBytes = 2 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxBytes) {
      $errors['picture'] = 'Profile picture must be 2 MB or less.';
      return null;
    }

    $tmpName = $file['tmp_name'] ?? '';
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
      $errors['picture'] = 'Invalid uploaded file.';
      return null;
    }

    $allowedMimes = [
      'image/jpeg' => 'jpg',
      'image/png' => 'png',
      'image/webp' => 'webp',
    ];

    $mimeType = mime_content_type($tmpName) ?: '';
    if (!isset($allowedMimes[$mimeType])) {
      $errors['picture'] = 'Only JPG, PNG, and WEBP images are allowed.';
      return null;
    }

    $uploadDir = __DIR__ . '/../../../public/uploads/profile_pictures';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
      $errors['picture'] = 'Could not prepare upload directory.';
      return null;
    }

    $fileName = 'profile_' . bin2hex(random_bytes(8)) . '.' . $allowedMimes[$mimeType];
    $targetPath = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
      $errors['picture'] = 'Failed to save profile picture.';
      return null;
    }

    return 'uploads/profile_pictures/' . $fileName;
  }

  private static function deleteUploadedFile(string $relativePath): void
  {
    $absolutePath = __DIR__ . '/../../../public/' . ltrim($relativePath, '/');
    if (is_file($absolutePath)) {
      unlink($absolutePath);
    }
  }
}
