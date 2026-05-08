<?php
require_once 'includes/bootstrap.php';

if (!empty($_SESSION['user'])) {
    $path = ($_SESSION['user']['role'] === 'Customer') ? 'customer/home.php' : 'admin/dashboard.php';
    header('Location: ' . base_path() . $path);
    exit;
}

$error = '';
$regOk = !empty($_SESSION['flash_register_ok']);
unset($_SESSION['flash_register_ok']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    
    $uModel = new User();
    $row = $uModel->authenticate($user, $pass);

    if ($row) {
        $_SESSION['user'] = $row;
        if ($row['role'] !== 'Customer') {
            $_SESSION['pending_low_stock_alert'] = true;
        }
        $path = ($row['role'] === 'Customer') ? 'customer/home.php' : 'admin/dashboard.php';
        header('Location: ' . base_path() . $path);
        exit;
    }
    $error = 'Invalid username or password.';
}

$baseHref = base_path();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — Thor's Thunder Wash</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars($baseHref) ?>assets/css/brand.css" rel="stylesheet">
</head>
<body>

<div class="lp-login-wrap"> 
    <div class="card lp-card lp-login-card shadow-lg">
        <div class="card-header text-center">Thor's Thunder Wash Laundry Services</div>
        <div class="card-body p-4">
            
            <p class="text-muted small mb-4 text-center">Sign in to book laundry, track orders, or manage the shop.</p>

            <?php if ($regOk): ?>
                <div class="alert alert-success py-2">Account created. You can sign in.</div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Username or email</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label mb-0">Password</label>
                        <a href="<?= htmlspecialchars($baseHref) ?>forgot_password.php" class="small link-secondary">Forgot password?</a>
                    </div>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn lp-btn-accent w-100">Sign in</button>
            </form>

            <p class="text-center mt-3 mb-0 small">
                <a href="<?= htmlspecialchars($baseHref) ?>" class="link-secondary me-2">← Home</a>
                <span class="text-muted">·</span>
                <a href="<?= htmlspecialchars($baseHref) ?>register.php" class="link-secondary ms-2">Create account</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>