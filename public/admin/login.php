<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_user'] = $admin['username'];
        redirect('index.php');
    }

    // Constant-ish delay so a wrong username and a wrong password look the same.
    usleep(300000);
    $error = 'Incorrect username or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in — <?= e(setting('site_title')) ?> Admin</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="admin.css?v=<?= APP_VERSION ?>">
</head>
<body class="a-login-body">

<form class="a-login" method="post" autocomplete="on">
  <?= csrf_field() ?>
  <div class="a-login-mark"><?= e(setting('brand_initials') ?: 'H') ?></div>
  <h1>Site administration</h1>
  <p class="a-login-sub"><?= e(setting('site_title')) ?></p>

  <?php if ($error !== ''): ?>
    <div class="a-flash a-flash-error"><?= e($error) ?></div>
  <?php endif; ?>

  <label class="a-field">
    <span>Username</span>
    <input type="text" name="username" required autofocus autocomplete="username">
  </label>

  <label class="a-field">
    <span>Password</span>
    <input type="password" name="password" required autocomplete="current-password">
  </label>

  <button class="a-btn a-btn-primary a-btn-block" type="submit">Sign in</button>
</form>

</body>
</html>
