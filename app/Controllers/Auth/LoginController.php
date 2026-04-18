<?php

class LoginController
{
  public static function handlePost(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      return;
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
      SessionHelper::flash('flash_error', 'Email and password are required.');
      SessionHelper::flash('old_email', $email);
      header('Location: ?page=login');
      exit;
    }

    try {
      $conn = Database::connect();
      $stmt = $conn->prepare(
        'SELECT id, name, role, password FROM users WHERE email = :email LIMIT 1'
      );
      $stmt->execute(['email' => $email]);
      $user = $stmt->fetch();
    } catch (Throwable $e) {
      SessionHelper::flash('flash_error', 'Unable to log in right now. Please try again.');
      SessionHelper::flash('old_email', $email);
      header('Location: ?page=login');
      exit;
    }

    if (!$user || !password_verify($password, $user['password'])) {
      SessionHelper::flash('flash_error', 'Invalid email or password.');
      SessionHelper::flash('old_email', $email);
      header('Location: ?page=login');
      exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];

    $destination = $user['role'] === 'admin' ? '?page=products' : '?page=home';
    header('Location: ' . $destination);
    exit;
  }
}
