<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require_login();

$errors = [];

$stmt = db()->prepare('SELECT * FROM admins WHERE id = ?');
$stmt->execute([(int) $_SESSION['admin_id']]);
$admin = $stmt->fetch();

if (!$admin) {
    redirect('logout.php');
}

$weak = ['password', '12345678', 'admin123', 'hashgacha2026', 'letmein1', 'qwerty123'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $current  = (string) ($_POST['current_password'] ?? '');
    $username = trim((string) ($_POST['username'] ?? ''));
    $new      = (string) ($_POST['new_password'] ?? '');
    $confirm  = (string) ($_POST['confirm_password'] ?? '');

    if (!password_verify($current, $admin['password_hash'])) {
        $errors['current_password'] = 'Your current password is not correct.';
    }

    if ($username === '' || !preg_match('/^[A-Za-z0-9._-]{3,32}$/', $username)) {
        $errors['username'] = 'Use 3–32 letters, numbers, dot, dash or underscore.';
    } elseif ($username !== $admin['username']) {
        $check = db()->prepare('SELECT COUNT(*) FROM admins WHERE username = ? AND id <> ?');
        $check->execute([$username, (int) $admin['id']]);
        if ((int) $check->fetchColumn() > 0) {
            $errors['username'] = 'That username is already taken.';
        }
    }

    if ($new !== '') {
        if (mb_strlen($new) < 8) {
            $errors['new_password'] = 'Use at least 8 characters.';
        } elseif (in_array(mb_strtolower($new), $weak, true)) {
            $errors['new_password'] = 'That password is too easy to guess — please pick another.';
        } elseif ($new !== $confirm) {
            $errors['confirm_password'] = 'The two passwords do not match.';
        }
    }

    if (!$errors) {
        if ($new !== '') {
            $update = db()->prepare('UPDATE admins SET username = ?, password_hash = ? WHERE id = ?');
            $update->execute([$username, password_hash($new, PASSWORD_DEFAULT), (int) $admin['id']]);
            flash('Username and password updated.');
        } else {
            db()->prepare('UPDATE admins SET username = ? WHERE id = ?')->execute([$username, (int) $admin['id']]);
            flash('Username updated.');
        }
        $_SESSION['admin_user'] = $username;
        redirect('account.php');
    }
}

$usingDefault = password_verify('hashgacha2026', $admin['password_hash']);

$pageTitle = 'Account';
$activeNav = 'account';
require __DIR__ . '/layout.php';
?>

<div class="a-head">
  <div>
    <h1>Account</h1>
    <p class="a-sub">The login you use to reach this admin area.</p>
  </div>
</div>

<?php if ($usingDefault): ?>
  <div class="a-flash a-flash-warn">
    You are still using the password this site shipped with. Please change it below.
  </div>
<?php endif; ?>

<form method="post" class="a-form a-form-narrow">
  <?= csrf_field() ?>

  <div class="a-card a-card-pad">
    <h2 class="a-card-title">Sign-in details</h2>

    <label class="a-field">
      <span>Username</span>
      <input type="text" name="username" value="<?= e($_POST['username'] ?? $admin['username']) ?>" autocomplete="username">
      <?php if (isset($errors['username'])): ?><small class="a-err"><?= e($errors['username']) ?></small><?php endif; ?>
    </label>

    <label class="a-field">
      <span>Current password <em>required to save any change</em></span>
      <input type="password" name="current_password" autocomplete="current-password">
      <?php if (isset($errors['current_password'])): ?><small class="a-err"><?= e($errors['current_password']) ?></small><?php endif; ?>
    </label>

    <hr class="a-rule">

    <label class="a-field">
      <span>New password <em>leave blank to keep the current one</em></span>
      <input type="password" name="new_password" autocomplete="new-password">
      <?php if (isset($errors['new_password'])): ?><small class="a-err"><?= e($errors['new_password']) ?></small><?php endif; ?>
    </label>

    <label class="a-field">
      <span>Repeat new password</span>
      <input type="password" name="confirm_password" autocomplete="new-password">
      <?php if (isset($errors['confirm_password'])): ?><small class="a-err"><?= e($errors['confirm_password']) ?></small><?php endif; ?>
    </label>
  </div>

  <div class="a-form-actions">
    <button class="a-btn a-btn-primary" type="submit">Save</button>
  </div>
</form>

<?php require __DIR__ . '/layout_end.php'; ?>
