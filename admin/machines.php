<?php
require __DIR__ . '/_auth.php';

$laundry = new Laundry();
$pageTitle = "Machines — Thor's Thunder Wash";
$edit = null;
if (isset($_GET['edit'])) {
    $edit = $laundry->getMachine((int) $_GET['edit']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $type = (string) ($_POST['machine_type'] ?? 'washer');
    $status = (string) ($_POST['status'] ?? 'available');
    $id = (int) ($_POST['id'] ?? 0);
    if (!in_array($type, ['washer', 'dryer'], true)) {
        $type = 'washer';
    }
    if (!in_array($status, ['available', 'in_use', 'maintenance'], true)) {
        $status = 'available';
    }
    if ($name === '') {
        $_SESSION['flash_err'] = 'Machine name required.';
    } elseif ($id > 0) {
        $laundry->updateMachine($id, $name, $type, $status);
        $_SESSION['flash_ok'] = 'Machine updated.';
    } else {
        $laundry->createMachine($name, $type, $status);
        $_SESSION['flash_ok'] = 'Machine added.';
    }
    header('Location: ' . base_path() . 'admin/machines.php');
    exit;
}

if (isset($_GET['delete'])) {
    $did = (int) $_GET['delete'];
    if ($did > 0) {
        $laundry->deleteMachine($did);
        $_SESSION['flash_ok'] = 'Machine removed.';
    }
    header('Location: ' . base_path() . 'admin/machines.php');
    exit;
}

$machines = $laundry->allMachines();
$flashOk = $_SESSION['flash_ok'] ?? '';
unset($_SESSION['flash_ok'], $_SESSION['flash_err']);

require_once dirname(__DIR__) . '/includes/partials/head.php';
require __DIR__ . '/_nav.php';
?>
<div class="container pb-5">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card lp-card">
                <div class="card-header"><?= $edit ? 'Edit machine' : 'Add machine' ?></div>
                <div class="card-body">
                    <form method="post" class="needs-validation" novalidate id="macForm">
                        <input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($edit['name'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="machine_type" class="form-select">
                                <option value="washer" <?= (($edit['machine_type'] ?? '') === 'washer') ? 'selected' : '' ?>>Washer</option>
                                <option value="dryer" <?= (($edit['machine_type'] ?? '') === 'dryer') ? 'selected' : '' ?>>Dryer</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (['available', 'in_use', 'maintenance'] as $st): ?>
                                    <option value="<?= $st ?>" <?= (($edit['status'] ?? 'available') === $st) ? 'selected' : '' ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn lp-btn-accent"><?= $edit ? 'Update' : 'Save' ?></button>
                        <?php if ($edit): ?>
                            <a href="machines.php" class="btn btn-outline-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card lp-card">
                <div class="card-header">Machines</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                        <tr><th>Name</th><th>Type</th><th>Status</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($machines as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['name']) ?></td>
                                <td><?= htmlspecialchars($m['machine_type']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($m['status']) ?></span></td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int) $m['id'] ?>">Edit</a>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-del-mac" data-id="<?= (int) $m['id'] ?>">Delete</button>
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
document.getElementById('macForm').addEventListener('submit', function (e) {
  if (!this.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
  this.classList.add('was-validated');
});
<?php if ($flashOk): ?>
document.addEventListener('DOMContentLoaded', function () { LaundryNotify.success('OK', <?= json_encode($flashOk) ?>); });
<?php endif; ?>
$(document).on('click', '.btn-del-mac', function () {
  const id = $(this).data('id');
  LaundryNotify.confirm({ title: 'Delete machine?', danger: true, confirmText: 'Delete' }).then(function (ok) {
    if (ok) window.location.href = 'machines.php?delete=' + id;
  });
});
</script>
