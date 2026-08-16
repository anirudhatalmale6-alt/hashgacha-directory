<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$errors = [];

$biz = [
    'id' => 0, 'name' => '', 'category' => '', 'description' => '', 'address' => '',
    'phone' => '', 'whatsapp' => '', 'email' => '', 'website' => '', 'logo' => '',
    'is_active' => 1,
];

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM businesses WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash('That business no longer exists.', 'error');
        redirect('index.php');
    }
    $biz = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $biz['name']        = trim((string) ($_POST['name'] ?? ''));
    $biz['category']    = trim((string) ($_POST['category'] ?? ''));
    $biz['description'] = trim((string) ($_POST['description'] ?? ''));
    $biz['address']     = trim((string) ($_POST['address'] ?? ''));
    $biz['phone']       = trim((string) ($_POST['phone'] ?? ''));
    $biz['whatsapp']    = trim((string) ($_POST['whatsapp'] ?? ''));
    $biz['email']       = trim((string) ($_POST['email'] ?? ''));
    $biz['website']     = normalize_url((string) ($_POST['website'] ?? ''));
    $biz['is_active']   = isset($_POST['is_active']) ? 1 : 0;

    if ($biz['name'] === '') {
        $errors['name'] = 'Business name is required.';
    }
    if ($biz['email'] !== '' && !filter_var($biz['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'That does not look like a valid email address.';
    }

    $newLogo = null;
    try {
        $newLogo = handle_logo_upload('logo', 'biz');
    } catch (RuntimeException $exception) {
        $errors['logo'] = $exception->getMessage();
    }

    if (!$errors) {
        $oldLogo = (string) $biz['logo'];
        $logo = $newLogo ?? $oldLogo;

        if (!empty($_POST['remove_logo']) && $newLogo === null) {
            $logo = '';
        }

        if ($id > 0) {
            $stmt = db()->prepare(
                'UPDATE businesses SET name = ?, category = ?, description = ?, address = ?,
                 phone = ?, whatsapp = ?, email = ?, website = ?, logo = ?, is_active = ?
                 WHERE id = ?'
            );
            $stmt->execute([
                $biz['name'], $biz['category'], $biz['description'], $biz['address'],
                $biz['phone'], $biz['whatsapp'], $biz['email'], $biz['website'],
                $logo, $biz['is_active'], $id,
            ]);
            if ($logo !== $oldLogo) {
                delete_upload($oldLogo);
            }
            flash('"' . $biz['name'] . '" was updated.');
        } else {
            $next = (int) db()->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM businesses')->fetchColumn();
            $stmt = db()->prepare(
                'INSERT INTO businesses (name, category, description, address, phone, whatsapp, email, website, logo, is_active, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $biz['name'], $biz['category'], $biz['description'], $biz['address'],
                $biz['phone'], $biz['whatsapp'], $biz['email'], $biz['website'],
                $logo, $biz['is_active'], $next,
            ]);
            flash('"' . $biz['name'] . '" was added.');
        }

        redirect('index.php');
    }

    // Keep the newly uploaded file visible in the form if validation failed elsewhere.
    if ($newLogo !== null) {
        $biz['logo'] = $newLogo;
    }
}

$existingCategories = db()->query(
    'SELECT DISTINCT category FROM businesses WHERE TRIM(category) <> "" ORDER BY category COLLATE NOCASE'
)->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = $id > 0 ? 'Edit business' : 'Add business';
$activeNav = 'businesses';
require __DIR__ . '/layout.php';
?>

<div class="a-head">
  <div>
    <a class="a-back" href="index.php">&larr; All businesses</a>
    <h1><?= $id > 0 ? 'Edit business' : 'Add business' ?></h1>
  </div>
</div>

<?php if ($errors): ?>
  <div class="a-flash a-flash-error">Please correct the highlighted fields.</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="a-form">
  <?= csrf_field() ?>

  <div class="a-card a-card-pad">
    <h2 class="a-card-title">Business details</h2>

    <label class="a-field">
      <span>Business name <em>required</em></span>
      <input type="text" name="name" value="<?= e($biz['name']) ?>" required>
      <?php if (isset($errors['name'])): ?><small class="a-err"><?= e($errors['name']) ?></small><?php endif; ?>
    </label>

    <label class="a-field">
      <span>Category <em>optional — used for the filter buttons</em></span>
      <input type="text" name="category" value="<?= e($biz['category']) ?>" list="catList" placeholder="e.g. Restaurant, Bakery, Caterer">
      <datalist id="catList">
        <?php foreach ($existingCategories as $cat): ?>
          <option value="<?= e($cat) ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </label>

    <label class="a-field">
      <span>Short description <em>optional — shown in the popup</em></span>
      <textarea name="description" rows="3"><?= e($biz['description']) ?></textarea>
    </label>
  </div>

  <div class="a-card a-card-pad">
    <h2 class="a-card-title">Contact information</h2>

    <div class="a-grid-2">
      <label class="a-field">
        <span>Phone number</span>
        <input type="text" name="phone" value="<?= e($biz['phone']) ?>" placeholder="+1 (555) 010-2030">
      </label>

      <label class="a-field">
        <span>WhatsApp <em>with country code</em></span>
        <input type="text" name="whatsapp" value="<?= e($biz['whatsapp']) ?>" placeholder="+15550102030">
      </label>

      <label class="a-field">
        <span>Email</span>
        <input type="text" name="email" value="<?= e($biz['email']) ?>" placeholder="info@business.com">
        <?php if (isset($errors['email'])): ?><small class="a-err"><?= e($errors['email']) ?></small><?php endif; ?>
      </label>

      <label class="a-field">
        <span>Website</span>
        <input type="text" name="website" value="<?= e($biz['website']) ?>" placeholder="www.business.com">
      </label>
    </div>

    <label class="a-field">
      <span>Address <em>optional</em></span>
      <input type="text" name="address" value="<?= e($biz['address']) ?>">
    </label>
  </div>

  <div class="a-card a-card-pad">
    <h2 class="a-card-title">Logo</h2>
    <p class="a-hint">PNG with a transparent background looks best. Max 4 MB.</p>

    <div class="a-logo-row">
      <div class="a-thumb a-thumb-lg" id="logoPreview">
        <?php if ($biz['logo'] !== '' && is_file(UPLOAD_DIR . '/' . $biz['logo'])): ?>
          <img src="../uploads/<?= e($biz['logo']) ?>" alt="">
        <?php else: ?>
          <span><?= e(initials($biz['name'] !== '' ? $biz['name'] : '?')) ?></span>
        <?php endif; ?>
      </div>

      <div class="a-logo-controls">
        <input type="file" name="logo" id="logoInput" accept="image/*">
        <?php if ($biz['logo'] !== ''): ?>
          <label class="a-check">
            <input type="checkbox" name="remove_logo" value="1">
            <span>Remove the current logo</span>
          </label>
        <?php endif; ?>
        <?php if (isset($errors['logo'])): ?><small class="a-err"><?= e($errors['logo']) ?></small><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="a-card a-card-pad">
    <label class="a-check">
      <input type="checkbox" name="is_active" value="1" <?= (int) $biz['is_active'] === 1 ? 'checked' : '' ?>>
      <span>Show this business on the website</span>
    </label>
  </div>

  <div class="a-form-actions">
    <button class="a-btn a-btn-primary" type="submit"><?= $id > 0 ? 'Save changes' : 'Add business' ?></button>
    <a class="a-btn a-btn-quiet" href="index.php">Cancel</a>
  </div>
</form>

<?php require __DIR__ . '/layout_end.php'; ?>
