<?php

/**
 * layouts/flash.php
 * Reads one-time flash message from session and clears it.
 */
if (!array_key_exists('flash_message', $_SESSION)) {
  return;
}

$rawMessage = $_SESSION['flash_message'];
$rawType = $_SESSION['flash_type'] ?? 'success';

unset($_SESSION['flash_message'], $_SESSION['flash_type']);

if (is_array($rawMessage)) {
  $rawType = $rawMessage['type'] ?? $rawType;
  $rawMessage = $rawMessage['message'] ?? ($rawMessage['text'] ?? '');
}

$message = trim((string) $rawMessage);
if ($message === '') {
  return;
}

$type = strtolower((string) $rawType);
$alertClass = $type === 'error' ? 'danger' : 'success';
?>

<div class="alert alert-<?= htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show mb-3" role="alert">
  <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>