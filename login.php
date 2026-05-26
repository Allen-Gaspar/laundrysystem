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
    <link href="https://googleapis.com" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars($baseHref) ?>assets/css/brand.css" rel="stylesheet">
    
</head>
<body> <!-- Fixed: Added class framework hook to trigger global background cascades -->

<div class="lp-login-wrap"> 
    <!-- Updated: Applied dynamic card boundaries with premium typography elements -->
    <div class="card lp-card lp-login-card shadow-lg border-0 animate-fade-in">
        <div class="card-header text-center py-3 border-0">
            <h5 class="m-0 fw-extrabold text-uppercase tracking-wider">Thor's Thunder Wash</h5>
            <span class="small opacity-75">Laundry Services</span>
        </div>
        <div class="card-body p-4">
            
            <p class="text-muted small mb-4 text-center">Sign in to book laundry, track orders, or manage the shop.</p>

            <?php if ($regOk): ?>
                <div class="alert alert-success py-2 border-0 rounded-3 small">Account created. You can sign in.</div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 border-0 rounded-3 small"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small">Username or email</label>
                    <input type="text" name="username" class="form-control form-control-lg border-light-subtle bg-light-subtle" style="font-size: 0.95rem; border-radius: 8px;" required>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0 fw-semibold text-secondary small">Password</label>
                        <a href="<?= htmlspecialchars($baseHref) ?>forgot_password.php" class="small text-decoration-none text-primary-emphasis fw-medium">Forgot password?</a>
                    </div>
                    <input type="password" name="password" class="form-control form-control-lg border-light-subtle bg-light-subtle" style="font-size: 0.95rem; border-radius: 8px;" required>
                </div>
                
                <!-- Applied our beautiful golden gradient action buttons block link directly -->
                <button type="submit" class="btn lp-btn-accent btn-lg w-100 mt-2 py-2 fs-6">Sign in</button>
            </form>

            <div class="text-center mt-4 pt-2 border-top border-light-subtle">
                <p class="mb-0 small">
                    <a href="<?= htmlspecialchars($baseHref) ?>" class="text-decoration-none text-secondary fw-medium me-2">← Back Home</a>
                    <span class="text-black-50">·</span>
                    <a href="<?= htmlspecialchars($baseHref) ?>register.php" class="text-decoration-none text-primary fw-semibold ms-2">Create account</a>
                </p>
            </div>
        </div>
    </div>
</div>

</body>
</html>
