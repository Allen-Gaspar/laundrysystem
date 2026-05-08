<?php
require __DIR__ . '/_auth.php';

$laundry = new Laundry();
$pageTitle = "Services — Thor's Thunder Wash";
$edit = null;

if (isset($_GET['edit'])) {
    $edit = $laundry->getService((int) $_GET['edit']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $desc = trim((string) ($_POST['description'] ?? ''));
    $price = (float) ($_POST['price_per_kg'] ?? 0);
    $active = isset($_POST['is_active']);
    $id = (int) ($_POST['id'] ?? 0);

    if ($name === '' || $price < 0) {
        $_SESSION['flash_err'] = 'Invalid service data.';
    } elseif ($id > 0) {
        $laundry->updateService($id, $name, $desc, $price, $active);
        $_SESSION['flash_ok'] = 'Service updated.';
    } else {
        $laundry->createService($name, $desc, $price, $active);
        $_SESSION['flash_ok'] = 'Service created.';
    }
    header('Location: ' . base_path() . 'admin/services.php');
    exit;
}

if (isset($_GET['delete'])) {
    $did = (int) $_GET['delete'];
    if ($did > 0) {
        $laundry->deleteService($did);
        $_SESSION['flash_ok'] = 'Service removed.';
    }
    header('Location: ' . base_path() . 'admin/services.php');
    exit;
}

$services = $laundry->allServices(false);
$flashOk = $_SESSION['flash_ok'] ?? '';
$flashErr = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_ok'], $_SESSION['flash_err']);

require_once dirname(__DIR__) . '/includes/partials/head.php';
require __DIR__ . '/_nav.php';
?>
<div class="container pb-5">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card lp-card">
                <div class="card-header"><?= $edit ? 'Edit service' : 'New service' ?></div>
                <div class="card-body">
                    <?php if ($flashErr): ?><div class="alert alert-danger"><?= htmlspecialchars($flashErr) ?></div><?php endif; ?>
                    <form method="post" class="needs-validation" novalidate id="svcForm">
                        <input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required
                                   value="<?= htmlspecialchars($edit['name'] ?? '') ?>">
                            <div class="invalid-feedback">Required</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price per kg (P)</label>
                            <input type="number" step="0.01" min="0" name="price_per_kg" class="form-control" required
                                   value="<?= htmlspecialchars((string) ($edit['price_per_kg'] ?? '0')) ?>">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                <?= (!$edit || !empty($edit['is_active'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">Active (visible to customers)</label>
                        </div>
                        <button type="submit" class="btn lp-btn-accent"><?= $edit ? 'Update' : 'Save' ?></button>
                        <?php if ($edit): ?>
                            <a href="<?= htmlspecialchars(base_path()) ?>admin/services.php" class="btn btn-outline-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card lp-card">
                <div class="card-header">Service menu</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr><th>Name</th><th>P/kg</th><th>Active</th><th width="140"></th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($services as $s): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($s['name']) ?></strong>
                                        <div class="small text-muted"><?= htmlspecialchars($s['description']) ?></div>
                                    </td>
                                    <td>P<?= number_format((float) $s['price_per_kg'], 2) ?></td>
                                    <td><?= (int) $s['is_active'] ? 'Yes' : 'No' ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int) $s['id'] ?>">Edit</a>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-del-svc" data-id="<?= (int) $s['id'] ?>">Delete</button>
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
</div>
<?php require_once dirname(__DIR__) . '/includes/partials/footer.php'; ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
(function () {
  const form = document.getElementById('svcForm');
  if (form) {
    form.addEventListener('submit', function (e) {
      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    });
  }
})();
<?php if ($flashOk): ?>
document.addEventListener('DOMContentLoaded', function () {
  LaundryNotify.success('Saved', <?= json_encode($flashOk) ?>);
});
<?php endif; ?>

$(document).on('click', '.btn-del-svc', function () {
  const id = $(this).data('id');
  LaundryNotify.confirm({ title: 'Delete service?', text: 'This cannot be undone.', danger: true, confirmText: 'Delete' }).then(function (ok) {
    if (ok) window.location.href = 'services.php?delete=' + id;
  });
});
</script>
