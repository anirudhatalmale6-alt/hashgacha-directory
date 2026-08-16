<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require_login();

/* ---------- actions ---------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        $stmt = db()->prepare('SELECT logo, name FROM businesses WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row) {
            delete_upload($row['logo']);
            db()->prepare('DELETE FROM businesses WHERE id = ?')->execute([$id]);
            flash('"' . $row['name'] . '" was deleted.');
        }
        redirect('index.php');
    }

    if ($action === 'toggle' && $id > 0) {
        db()->prepare('UPDATE businesses SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
        flash('Visibility updated.');
        redirect('index.php');
    }

    if ($action === 'order_mode') {
        $mode = ($_POST['business_order'] ?? '') === 'manual' ? 'manual' : 'alpha';
        save_setting('business_order', $mode);
        flash($mode === 'alpha'
            ? 'Businesses are now listed in alphabetical order.'
            : 'Businesses are now listed in your own order — drag the rows to change it.');
        redirect('index.php');
    }

    if ($action === 'reorder') {
        $order = $_POST['order'] ?? [];
        if (is_array($order)) {
            $stmt = db()->prepare('UPDATE businesses SET sort_order = ? WHERE id = ?');
            foreach (array_values($order) as $position => $bizId) {
                $stmt->execute([$position, (int) $bizId]);
            }
            flash('New order saved.');
        }
        redirect('index.php');
    }
}

$alphabetical = setting('business_order') !== 'manual';

// Show the admin table the same way the website will show the grid.
$rows = db()->query(
    'SELECT * FROM businesses ORDER BY ' .
    ($alphabetical ? 'name COLLATE NOCASE ASC' : 'sort_order ASC, name COLLATE NOCASE ASC')
)->fetchAll();

$activeCount = 0;
foreach ($rows as $row) {
    if ((int) $row['is_active'] === 1) {
        $activeCount++;
    }
}

$pageTitle = 'Certified Businesses';
$activeNav = 'businesses';
require __DIR__ . '/layout.php';
?>

<div class="a-head">
  <div>
    <h1>Certified Businesses</h1>
    <p class="a-sub"><?= count($rows) ?> total &middot; <?= $activeCount ?> visible on the website</p>
  </div>
  <a class="a-btn a-btn-primary" href="business.php">+ Add business</a>
</div>

<?php if (!$rows): ?>
  <div class="a-empty">
    <p>No businesses added yet.</p>
    <a class="a-btn a-btn-primary" href="business.php">Add your first business</a>
  </div>
<?php else: ?>

  <form method="post" class="a-order-mode">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="order_mode">
    <span class="a-order-label">Order on the website</span>
    <label class="a-check">
      <input type="radio" name="business_order" value="alpha" <?= $alphabetical ? 'checked' : '' ?> onchange="this.form.submit()">
      <span>A–Z by name</span>
    </label>
    <label class="a-check">
      <input type="radio" name="business_order" value="manual" <?= $alphabetical ? '' : 'checked' ?> onchange="this.form.submit()">
      <span>My own order (drag the rows)</span>
    </label>
    <noscript><button class="a-btn a-btn-tiny" type="submit">Apply</button></noscript>
  </form>

  <form method="post" id="orderForm">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="reorder">

    <div class="a-card">
      <table class="a-table" id="bizTable"<?= $alphabetical ? ' data-locked="1"' : '' ?>>
        <thead>
          <tr>
            <?php if (!$alphabetical): ?><th class="a-col-drag" aria-label="Reorder"></th><?php endif; ?>
            <th class="a-col-logo">Logo</th>
            <th>Business</th>
            <th class="a-hide-sm">Contact</th>
            <th class="a-col-status">Status</th>
            <th class="a-col-actions"></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
          <tr data-id="<?= (int) $row['id'] ?>"<?= $alphabetical ? '' : ' draggable="true"' ?>>
            <?php if (!$alphabetical): ?>
              <td class="a-col-drag">
                <span class="a-drag" title="Drag to reorder">⋮⋮</span>
                <input type="hidden" name="order[]" value="<?= (int) $row['id'] ?>">
              </td>
            <?php endif; ?>
            <td>
              <div class="a-thumb">
                <?php if ($row['logo'] !== '' && is_file(UPLOAD_DIR . '/' . $row['logo'])): ?>
                  <img src="../uploads/<?= e($row['logo']) ?>" alt="">
                <?php else: ?>
                  <span><?= e(initials($row['name'])) ?></span>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <a class="a-strong-link" href="business.php?id=<?= (int) $row['id'] ?>"><?= e($row['name']) ?></a>
              <?php if ($row['category'] !== ''): ?>
                <span class="a-tag"><?= e($row['category']) ?></span>
              <?php endif; ?>
            </td>
            <td class="a-hide-sm a-muted">
              <?php
                $bits = array_filter([$row['phone'], $row['email'], pretty_url($row['website'])]);
                echo $bits ? e(implode(' · ', $bits)) : '<span class="a-muted">—</span>';
              ?>
            </td>
            <td>
              <span class="a-pill <?= (int) $row['is_active'] === 1 ? 'is-on' : 'is-off' ?>">
                <?= (int) $row['is_active'] === 1 ? 'Visible' : 'Hidden' ?>
              </span>
            </td>
            <td class="a-row-actions">
              <a class="a-btn a-btn-tiny" href="business.php?id=<?= (int) $row['id'] ?>">Edit</a>
              <button class="a-btn a-btn-tiny" type="submit" form="rowForm<?= (int) $row['id'] ?>" name="action" value="toggle">
                <?= (int) $row['is_active'] === 1 ? 'Hide' : 'Show' ?>
              </button>
              <button class="a-btn a-btn-tiny a-btn-danger" type="submit" form="rowForm<?= (int) $row['id'] ?>" name="action" value="delete"
                      data-confirm="Delete &quot;<?= e($row['name']) ?>&quot;? This cannot be undone.">Delete</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="a-actions-bar" id="orderBar" hidden>
      <span>Order changed</span>
      <button class="a-btn a-btn-primary" type="submit">Save new order</button>
    </div>
  </form>

  <?php foreach ($rows as $row): ?>
    <form method="post" id="rowForm<?= (int) $row['id'] ?>" class="a-hidden-form">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
    </form>
  <?php endforeach; ?>

<?php endif; ?>

<?php require __DIR__ . '/layout_end.php'; ?>
