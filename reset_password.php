<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!empty($_SESSION['user'])) {
    $path = ($_SESSION['user']['role'] === 'Customer') ? 'customer/home.php' : 'admin/dashboard.php';
    header('Location: ' . base_path() . $path);
    exit;
}

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$userModel = new User();
$row = $token !== '' ? $userModel->findByValidPasswordResetToken($token) : null;

$error = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $row) {
    $p1 = (string) ($_POST['password'] ?? '');
    $p2 = (string) ($_POST['password_confirm'] ?? '');
    if (strlen($p1) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($p1 !== $p2) {
        $error = 'Passwords do not match.';
    } else {
        $userModel->setPassword((int) $row['id'], $p1);
        $userModel->clearPasswordResetToken((int) $row['id']);
        $ok = true;
        $row = null;
    }
}

$baseHref = base_path();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New password — Thor's Thunder Wash</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars($baseHref) ?>assets/css/brand.css" rel="stylesheet">
</head>
<body>

<div class="lp-login-wrap">
    <div class="card lp-card lp-login-card shadow-lg">
        <div class="card-header text-center">Set new password</div>
        <div class="card-body p-4">
            <?php if ($ok): ?>
                <div class="alert alert-success py-2">Your password was updated. You can sign in now.</div>
                <p class="text-center mb-0"><a href="<?= htmlspecialchars($baseHref) ?>login.php" class="btn lp-btn-accent">Sign in</a></p>
            <?php elseif (!$row && $token !== ''): ?>
                <div class="alert alert-danger py-2">This reset link is invalid or has expired. Request a new one.</div>
                <p class="text-center mb-0"><a href="<?= htmlspecialchars($baseHref) ?>forgot_password.php">Forgot password</a></p>
            <?php elseif (!$row): ?>
                <div class="alert alert-warning py-2">Missing reset token.</div>
                <p class="text-center mb-0"><a href="<?= htmlspecialchars($baseHref) ?>forgot_password.php">Request a reset</a></p>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <div class="mb-3">
                        <label class="form-label">New password</label>
                        <input type="password" name="password" class="form-control" required minlength="6" autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm password</label>
                        <input type="password" name="password_confirm" class="form-control" required minlength="6" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn lp-btn-accent w-100">Save password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
