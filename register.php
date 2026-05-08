<?php
require_once 'includes/bootstrap.php';

if (!empty($_SESSION['user'])) {
    header('Location: ' . base_path() . 'customer/home.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $full     = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $pass2    = $_POST['password_confirm'] ?? '';

    if (strlen($username) < 3) $errors[] = "Username too short (min 3).";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (empty($full)) $errors[] = "Full name is required.";
    if (strlen($pass) < 6) $errors[] = "Password too short (min 6).";
    if ($pass !== $pass2) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $userModel = new User();
        if ($userModel->usernameExists($username)) $errors[] = "Username taken.";
        if ($userModel->emailExists($email)) $errors[] = "Email already in use.";
        
        if (empty($errors)) {
            $userModel->create($username, $email, $pass, $full, $phone, 'Customer');
            $emailService = new EmailService();
            $emailService->sendWelcomeRegistered($email, $full, $username);
            $_SESSION['flash_register_ok'] = 1;
            header('Location: ' . base_path() . 'login.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register — Thor's Thunder Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_path() ?>assets/css/brand.css" rel="stylesheet">
</head>
<body>

<div class="lp-login-wrap">
    <div class="card lp-card lp-login-card shadow-lg" style="max-width:480px">
        <div class="card-header text-center">Create account</div>
        <div class="card-body p-4">

            <?php foreach ($errors as $e): ?>
                <div class="alert alert-danger py-1 small"><?= $e ?></div>
            <?php endforeach; ?>

            <form method="post">
                <div class="mb-2">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
                <div class="mb-2">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="mb-2">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                </div>
                <div class="mb-2">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Confirm</label>
                        <input type="password" name="password_confirm" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn lp-btn-accent w-100">Register</button>
            </form>
            
            <p class="text-center mt-3 mb-0 small">
                <a href="<?= htmlspecialchars(base_path()) ?>">Home</a>
                <span class="text-muted"> · </span>
                <a href="<?= htmlspecialchars(base_path()) ?>login.php">Sign in</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>