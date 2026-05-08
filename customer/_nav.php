<?php
require_once dirname(__DIR__) . '/includes/paths.php';
$bp = base_path();
?>
<nav class="navbar navbar-expand-lg navbar-dark lp-navbar mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= htmlspecialchars($bp) ?>customer/home.php">
            <img src="<?= htmlspecialchars($bp) ?>assets/images/hero-banner.png" alt="Logo" class="lp-nav-logo">
            Thor's Thunder Wash
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navCust">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navCust">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($bp) ?>customer/home.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($bp) ?>customer/book.php">Book</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($bp) ?>customer/orders.php">My orders</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($bp) ?>customer/profile.php">Profile</a></li>
            </ul>
            <span class="navbar-text text-white me-3 small"><?= htmlspecialchars($currentUser['full_name']) ?></span>
            <a class="btn btn-sm lp-btn-accent" href="<?= htmlspecialchars($bp) ?>logout.php">Logout</a>
        </div>
    </div>
</nav>
