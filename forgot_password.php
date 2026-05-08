<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!empty($_SESSION['user'])) {
    $path = ($_SESSION['user']['role'] === 'Customer') ? 'customer/home.php' : 'admin/dashboard.php';
    header('Location: ' . base_path() . $path);
    exit;
}

$info = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $userModel = new User();
        $row = $userModel->findByEmail($email);
        if ($row) {
            $token = bin2hex(random_bytes(32));
            $expires = new DateTimeImmutable('+1 hour');
            $userModel->setPasswordResetToken((int) $row['id'], $token, $expires);
            $emailService = new EmailService();
            $resetUrl = rtrim(base_url(), '/') . '/reset_password.php?token=' . urlencode($token);
            $emailService->sendPasswordResetLink($row['email'], $row['full_name'], $resetUrl);
        }
        $info = 'If that email is registered, we sent instructions to reset your password. Check your inbox.';
    }
}

$baseHref = base_path();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password — Thor's Thunder Wash</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars($baseHref) ?>assets/css/brand.css" rel="stylesheet">
</head>
<body>

<div class="lp-login-wrap">
    <div class="card lp-card lp-login-card shadow-lg">
        <div class="card-header text-center">Reset password</div>
        <div class="card-body p-4">
            <p class="text-muted small mb-4">Enter the email on your account. We will send a secure link to set a new password (we never send your actual password by email).</p>

            <?php if ($info): ?>
                <div class="alert alert-success py-2"><?= htmlspecialchars($info) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email">
                </div>
                <button type="submit" class="btn lp-btn-accent w-100">Send reset link</button>
            </form>

            <p class="text-center mt-3 mb-0 small">
                <a href="<?= htmlspecialchars($baseHref) ?>login.php" class="link-secondary">← Back to sign in</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>
