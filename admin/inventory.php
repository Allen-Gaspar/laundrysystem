<?php
require __DIR__ . '/_auth.php';

$laundry = new Laundry();
$pageTitle = "Inventory — Thor's Thunder Wash";
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$edit = null;
if ($editId) {
    foreach ($laundry->allInventory() as $row) {
        if ((int) $row['id'] === $editId) {
            $edit = $row;
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['item_name'] ?? ''));
    $qty = (float) ($_POST['quantity'] ?? 0);
    $unit = trim((string) ($_POST['unit'] ?? 'pcs'));
    $reorder = (int) ($_POST['reorder_level'] ?? 0);
    $id = (int) ($_POST['id'] ?? 0);
    if ($name === '') {
        $_SESSION['flash_err'] = 'Item name required.';
    } elseif ($id > 0) {
        $laundry->updateInventoryItem($id, $name, $qty, $unit, $reorder);
        $_SESSION['flash_ok'] = 'Inventory updated.';
    } else {
        $laundry->createInventoryItem($name, $qty, $unit, $reorder);
        $_SESSION['flash_ok'] = 'Item added.';
    }
    header('Location: ' . base_path() . 'admin/inventory.php');
    exit;
}

if (isset($_GET['delete'])) {
    $did = (int) $_GET['delete'];
    if ($did > 0) {
        $laundry->deleteInventoryItem($did);
        $_SESSION['flash_ok'] = 'Item removed.';
    }
    header('Location: ' . base_path() . 'admin/inventory.php');
    exit;
}

$items = $laundry->allInventory();
$flashOk = $_SESSION['flash_ok'] ?? '';
unset($_SESSION['flash_ok'], $_SESSION['flash_err']);

require_once dirname(__DIR__) . '/includes/partials/head.php';
require __DIR__ . '/_nav.php';
?>
<div class="container pb-5">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card lp-card">
                <div class="card-header"><?= $edit ? 'Edit item' : 'Add stock item' ?></div>
                <div class="card-body">
                    <form method="post" class="needs-validation" novalidate id="invForm">
                        <input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
                        <div class="mb-3">
                            <label class="form-label">Item name</label>
                            <input type="text" name="item_name" class="form-control" required value="<?= htmlspecialchars($edit['item_name'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" step="0.01" name="quantity" class="form-control" required value="<?= htmlspecialchars((string) ($edit['quantity'] ?? '0')) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" class="form-control" value="<?= htmlspecialchars($edit['unit'] ?? 'pcs') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pieces</label>
                            <input type="number" step="0.01" name="reorder_level" class="form-control" required value="<?= htmlspecialchars((int) ($edit['reorder_level'] ?? '5')) ?>">
                        </div>
                        <button type="submit" class="btn lp-btn-accent"><?= $edit ? 'Update' : 'Save' ?></button>
                        <?php if ($edit): ?>
                            <a href="inventory.php" class="btn btn-outline-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card lp-card">
                <div class="card-header">Stock levels</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                        <tr><th>Item</th><th>Qty</th><th>Pcs</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $it): ?>
                            <?php $low = (float) $it['quantity'] <= (float) $it['reorder_level']; ?>
                            <tr class="<?= $low ? 'table-warning' : '' ?>">
                                <td><?= htmlspecialchars($it['item_name']) ?></td>
                                <td><?= htmlspecialchars((string) $it['quantity']) ?> <?= htmlspecialchars($it['unit']) ?></td>
                                <td><?= htmlspecialchars((int) $it['reorder_level']) ?></td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int) $it['id'] ?>">Edit</a>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-del-inv" data-id="<?= (int) $it['id'] ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/partials/footer.php'; ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
document.getElementById('invForm').addEventListener('submit', function (e) {
  if (!this.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
  this.classList.add('was-validated');
});
<?php if ($flashOk): ?>
document.addEventListener('DOMContentLoaded', function () { LaundryNotify.success('OK', <?= json_encode($flashOk) ?>); });
<?php endif; ?>
$(document).on('click', '.btn-del-inv', function () {
  const id = $(this).data('id');
  LaundryNotify.confirm({ title: 'Delete item?', danger: true, confirmText: 'Delete' }).then(function (ok) {
    if (ok) window.location.href = 'inventory.php?delete=' + id;
  });
});
</script>
