<?php

/**
 * layouts/sidebar.php
 * Expects: $sidebarItems (array), $activeNav (string), $sidebarClass (string)
 */
$sidebarItems = $sidebarItems ?? [];
$activeNav = $activeNav ?? '';
$sidebarClass = $sidebarClass ?? 'layout-sidebar';
?>

<aside class="<?= htmlspecialchars(trim($sidebarClass), ENT_QUOTES, 'UTF-8') ?>">
  <nav class="d-flex flex-column py-3 px-2">
    <?php foreach ($sidebarItems as $item): ?>
      <?php $isActive = $activeNav === ($item['key'] ?? ''); ?>
      <a
        href="<?= htmlspecialchars((string) ($item['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"
        class="nav-link px-3 py-2 <?= $isActive ? 'active' : '' ?>">
        <i class="bi <?= htmlspecialchars((string) ($item['icon'] ?? 'bi-circle'), ENT_QUOTES, 'UTF-8') ?>"></i>
        <?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
      </a>
    <?php endforeach; ?>
  </nav>
</aside>