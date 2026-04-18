<?php

if (!function_exists('requireLogin')) {
  function requireLogin(): void
  {
    if (empty($_SESSION['user_id'])) {
      header('Location: ?page=login');
      exit;
    }
  }
}

if (!function_exists('redirectAuthenticatedUser')) {
  function redirectAuthenticatedUser(): void
  {
    if (empty($_SESSION['user_id'])) {
      return;
    }

    $destination = ($_SESSION['role'] ?? '') === 'admin' ? '?page=products' : '?page=home';
    header('Location: ' . $destination);
    exit;
  }
}

if (!function_exists('requireAdmin')) {
  function requireAdmin(): void
  {
    requireLogin();

    if (($_SESSION['role'] ?? '') !== 'admin') {
      http_response_code(403);
      echo '403 - Forbidden';
      exit;
    }
  }
}

if (!function_exists('logout')) {
  function logout(): void
  {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
      );
    }

    session_destroy();
    header('Location: ?page=login');
    exit;
  }
}
