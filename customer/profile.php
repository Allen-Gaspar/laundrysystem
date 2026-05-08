<?php
require __DIR__ . '/_auth.php';

$userModel = new User();
$pageTitle = "Profile — Thor's Thunder Wash";
$errors = [];
$ok = false;
$pwErrors = [];
$pwOk = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['change_password'])) {
    $cur = (string) ($_POST['current_password'] ?? '');
    $p1 = (string) ($_POST['new_password'] ?? '');
    $p2 = (string) ($_POST['new_password_confirm'] ?? '');
    if ($cur === '') {
        $pwErrors[] = 'Current password is required.';
    }
    if (strlen($p1) < 6) {
        $pwErrors[] = 'New password must be at least 6 characters.';
    }
    if ($p1 !== $p2) {
        $pwErrors[] = 'New passwords do not match.';
    }
    if (!$pwErrors) {
        if ($userModel->verifyAndChangePassword((int) $currentUser['id'], $cur, $p1)) {
            $me = $userModel->findById((int) $currentUser['id']);
            if ($me) {
                $emailService = new EmailService();
                $emailService->sendPasswordChangedNotice($me['email'], $me['full_name']);
            }
            $pwOk = true;
        } else {
            $pwErrors[] = 'Current password is incorrect.';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full = trim((string) ($_POST['full_name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    if ($full === '') {
        $errors[] = 'Name required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email required.';
    }
    if (!$errors && $userModel->emailExists($email, (int) $currentUser['id'])) {
        $errors[] = 'Email already in use.';
    }
    if (!$errors) {
        $userModel->updateProfile((int) $currentUser['id'], $full, $phone, $email);
        $_SESSION['user'] = $userModel->findById((int) $currentUser['id']) ?? $_SESSION['user'];
        $currentUser = $_SESSION['user'];
        $ok = true;
    }
}

$me = $userModel->findById((int) $currentUser['id']);

require_once dirname(__DIR__) . '/includes/partials/head.php';
require __DIR__ . '/_nav.php';
?>
<div class="container pb-5" style="max-width:560px">
    <div class="card lp-card mb-4">
        <div class="card-header">Your profile</div>
        <div class="card-body">
            <?php foreach ($errors as $er): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($er) ?></div>
            <?php endforeach; ?>
            <?php if ($ok): ?>
                <div class="alert alert-success py-2">Profile saved.</div>
            <?php endif; ?>
            <form method="post" class="needs-validation" novalidate id="profile-form">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($me['username'] ?? '') ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Full name</label>
                    <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($me['full_name'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($me['phone'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($me['email'] ?? '') ?>">
                </div>
                <button type="submit" class="btn lp-btn-accent">Save profile</button>
            </form>
        </div>
    </div>

    <div class="card lp-card">
        <div class="card-header">Password</div>
        <div class="card-body">
            <?php foreach ($pwErrors as $er): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($er) ?></div>
            <?php endforeach; ?>
            <?php if ($pwOk): ?>
                <div class="alert alert-success py-2">Password updated. A confirmation was sent to your email.</div>
            <?php endif; ?>
            <form method="post" id="password-form" autocomplete="off">
                <input type="hidden" name="change_password" value="1">
                <div class="mb-3">
                    <label class="form-label">Current password</label>
                    <div class="input-group">
                        <input type="password" name="current_password" class="form-control pw-field" autocomplete="current-password" required>
                        <button type="button" class="btn btn-outline-secondary" data-pw-toggle>Show</button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">New password</label>
                    <div class="input-group">
                        <input type="password" name="new_password" class="form-control pw-field" minlength="6" autocomplete="new-password" required>
                        <button type="button" class="btn btn-outline-secondary" data-pw-toggle>Show</button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm new password</label>
                    <div class="input-group">
                        <input type="password" name="new_password_confirm" class="form-control pw-field" minlength="6" autocomplete="new-password" required>
                        <button type="button" class="btn btn-outline-secondary" data-pw-toggle>Show</button>
                    </div>
                </div>
                <button type="submit" class="btn lp-btn-accent">Change password</button>
            </form>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/partials/footer.php'; ?>
<script>
document.querySelector('.needs-validation').addEventListener('submit', function (e) {
  if (!this.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
  this.classList.add('was-validated');
});
document.querySelectorAll('[data-pw-toggle]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var input = btn.closest('.input-group').querySelector('.pw-field');
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.textContent = input.type === 'password' ? 'Show' : 'Hide';
  });
});
</script>
