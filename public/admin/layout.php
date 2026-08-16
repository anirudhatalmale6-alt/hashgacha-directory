<?php
/** Shared admin chrome. Set $pageTitle and $activeNav before including. */
declare(strict_types=1);

$flash = take_flash();
$navItems = [
    'businesses' => ['index.php', 'Certified Businesses'],
    'content'    => ['content.php', 'Page Content'],
    'contact'    => ['contact.php', 'Contact & Branding'],
    'account'    => ['account.php', 'Account'],
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Admin') ?> — <?= e(setting('site_title')) ?> Admin</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="admin.css?v=<?= APP_VERSION ?>">
</head>
<body>

<header class="a-top">
  <div class="a-top-inner">
    <a class="a-logo" href="index.php">
      <span class="a-logo-mark"><?= e(setting('brand_initials') ?: 'H') ?></span>
      <span>
        <strong><?= e(setting('site_title')) ?></strong>
        <small>Site administration</small>
      </span>
    </a>
    <div class="a-top-actions">
      <a class="a-btn a-btn-ghost" href="../index.php" target="_blank" rel="noopener">View site</a>
      <a class="a-btn a-btn-quiet" href="logout.php">Sign out</a>
    </div>
  </div>
</header>

<nav class="a-nav">
  <div class="a-nav-inner">
    <?php foreach ($navItems as $key => [$href, $label]): ?>
      <a href="<?= e($href) ?>" class="a-nav-item<?= ($activeNav ?? '') === $key ? ' is-active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
</nav>

<main class="a-main">
  <?php if ($flash): ?>
    <div class="a-flash a-flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
  <?php endif; ?>
