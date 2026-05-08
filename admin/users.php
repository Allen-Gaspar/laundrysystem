<?php
require __DIR__ . '/_auth.php';

if (($currentUser['role'] ?? '') !== 'Admin') {
    header('Location: ' . base_path() . 'admin/dashboard.php');
    exit;
}

$userModel = new User();
$pageTitle = "Users — Thor's Thunder Wash";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['form_action'] ?? '');
    if ($action === 'create_staff') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $full = trim((string) ($_POST['full_name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? 'Staff');
        if (!in_array($role, ['Staff', 'Admin'], true)) {
            $role = 'Staff';
        }
        if (strlen($username) < 3) {
            $errors[] = 'Username too short.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email.';
        }
        if (strlen($pass) < 6) {
            $errors[] = 'Password min 6 chars.';
        }
        if (!$errors && $userModel->usernameExists($username)) {
            $errors[] = 'Username taken.';
        }
        if (!$errors && $userModel->emailExists($email)) {
            $errors[] = 'Email taken.';
        }
        if (!$errors) {
            $userModel->create($username, $email, $pass, $full, $phone, $role);
            $_SESSION['flash_ok'] = 'Staff user created.';
            header('Location: ' . base_path() . 'admin/users.php');
            exit;
        }
    }
    if ($action === 'update_staff') {
        $id = (int) ($_POST['id'] ?? 0);
        $email = trim((string) ($_POST['email'] ?? ''));
        $full = trim((string) ($_POST['full_name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $role = (string) ($_POST['role'] ?? 'Staff');
        if (!in_array($role, ['Staff', 'Admin'], true)) {
            $role = 'Staff';
        }
        if ($id === (int) $currentUser['id'] && $role !== 'Admin') {
            $errors[] = 'You cannot remove your own Admin role.';
        }
        if (!$errors && $userModel->emailExists($email, $id)) {
            $errors[] = 'Email already in use.';
        }
        if (!$errors) {
            $userModel->updateStaff($id, $full, $phone, $email, $role);
            $np = trim((string) ($_POST['new_password'] ?? ''));
            if ($np !== '' && strlen($np) >= 6) {
                $userModel->setPassword($id, $np);
            }
            $_SESSION['flash_ok'] = 'User updated.';
            header('Location: ' . base_path() . 'admin/users.php');
            exit;
        }
    }
}

if (isset($_GET['delete_staff'])) {
    $id = (int) $_GET['delete_staff'];
    if ($id > 0 && $id !== (int) $currentUser['id']) {
        $userModel->delete($id);
        $_SESSION['flash_ok'] = 'User removed.';
    }
    header('Location: ' . base_path() . 'admin/users.php');
    exit;
}

$staff = $userModel->listStaff();
$customers = $userModel->listCustomers();
$flashOk = $_SESSION['flash_ok'] ?? '';
unset($_SESSION['flash_ok']);

$bp = base_path();
require_once dirname(__DIR__) . '/includes/partials/head.php';
require __DIR__ . '/_nav.php';
?>
<div class="container pb-5">
    <?php foreach ($errors as $er): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($er) ?></div>
    <?php endforeach; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card lp-card mb-4">
                <div class="card-header">Add staff / admin</div>
                <div class="card-body">
                    <form method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="form_action" value="create_staff">
                        <div class="mb-2"><input class="form-control" name="username" placeholder="Username" required></div>
                        <div class="mb-2"><input class="form-control" type="email" name="email" placeholder="Email" required></div>
                        <div class="mb-2"><input class="form-control" name="full_name" placeholder="Full name" required></div>
                        <div class="mb-2"><input class="form-control" name="phone" placeholder="Phone"></div>
                        <div class="mb-2"><input class="form-control" type="password" name="password" placeholder="Password" required minlength="6"></div>
                        <div class="mb-3">
                            <select class="form-select" name="role">
                                <option value="Staff">Staff</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                        <button class="btn lp-btn-accent" type="submit">Create</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card lp-card mb-4">
                <div class="card-header">Staff &amp; admins</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                        <tr><th>Name</th><th>Role</th><th>Email</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($staff as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['full_name']) ?></td>
                                <td><?= htmlspecialchars($s['role']) ?></td>
                                <td><?= htmlspecialchars($s['email']) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-staff"
                                        data-id="<?= (int) $s['id'] ?>"
                                        data-full_name="<?= htmlspecialchars($s['full_name']) ?>"
                                        data-email="<?= htmlspecialchars($s['email']) ?>"
                                        data-phone="<?= htmlspecialchars($s['phone']) ?>"
                                        data-role="<?= htmlspecialchars($s['role']) ?>">Edit</button>
                                    <?php if ((int) $s['id'] !== (int) $currentUser['id']): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-del-staff" data-id="<?= (int) $s['id'] ?>">Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card lp-card">
                <div class="card-header">Customers</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                        <tr><th>Name</th><th>Phone</th><th>Email</th><th>Since</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($customers as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['full_name']) ?></td>
                                <td><?= htmlspecialchars($c['phone']) ?></td>
                                <td><?= htmlspecialchars($c['email']) ?></td>
                                <td class="small"><?= htmlspecialchars($c['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="form_action" value="update_staff">
                <input type="hidden" name="id" id="es_id">
                <div class="mb-2"><input class="form-control" name="full_name" id="es_full" required></div>
                <div class="mb-2"><input class="form-control" type="email" name="email" id="es_email" required></div>
                <div class="mb-2"><input class="form-control" name="phone" id="es_phone"></div>
                <div class="mb-2">
                    <select class="form-select" name="role" id="es_role">
                        <option value="Staff">Staff</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label small text-muted">New password (optional)</label>
                    <input class="form-control" type="password" name="new_password" minlength="6" placeholder="Leave blank to keep">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn lp-btn-accent">Save</button>
            </div>
        </form>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/partials/footer.php'; ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
<?php if ($flashOk): ?>
document.addEventListener('DOMContentLoaded', function () { LaundryNotify.success('OK', <?= json_encode($flashOk) ?>); });
<?php endif; ?>

$(document).on('click', '.btn-edit-staff', function () {
  const d = $(this).data();
  $('#es_id').val(d.id);
  $('#es_full').val(d.full_name);
  $('#es_email').val(d.email);
  $('#es_phone').val(d.phone);
  $('#es_role').val(d.role);
  new bootstrap.Modal(document.getElementById('editStaffModal')).show();
});

$(document).on('click', '.btn-del-staff', function () {
  const id = $(this).data('id');
  LaundryNotify.confirm({ title: 'Delete this user?', danger: true, confirmText: 'Delete' }).then(function (ok) {
    if (ok) window.location.href = 'users.php?delete_staff=' + id;
  });
});
</script>
