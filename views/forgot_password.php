<?php

/**
 * forgot_password.php
 * Placeholder page for future password reset flow.
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Forgot Password — Cafeteria</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <style>
    body {
      min-height: 100vh;
      background: #f3f4f6;
    }

    .stub-card {
      max-width: 520px;
      border-radius: 1rem;
      border: 1px solid #e9ecef;
    }
  </style>
</head>

<body class="d-flex align-items-center justify-content-center p-3">
  <div class="stub-card bg-white shadow-sm p-4 p-md-5 w-100 text-center">
    <div class="mb-3 text-warning" style="font-size: 2.25rem;">
      <i class="bi bi-tools"></i>
    </div>
    <h1 class="h4 fw-bold mb-2">Forgot Password</h1>
    <p class="text-muted mb-4">This page is a placeholder for now. Password reset flow will be implemented in a later issue.</p>

    <div class="d-flex justify-content-center gap-2 flex-wrap">
      <a href="?page=login" class="btn btn-warning fw-semibold">
        <i class="bi bi-arrow-left-circle me-1"></i>Back to Login
      </a>
      <a href="?page=register" class="btn btn-outline-secondary fw-semibold">
        <i class="bi bi-person-plus me-1"></i>Create Account
      </a>
    </div>
  </div>
</body>

</html>