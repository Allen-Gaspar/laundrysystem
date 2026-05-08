<?php
require_once dirname(__DIR__) . '/includes/paths.php';
$bp = base_path();
$isAdmin = ($currentUser['role'] ?? '') === 'Admin';
?>
<nav class="navbar navbar-expand-lg navbar-dark lp-navbar mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= htmlspecialchars($bp) ?>admin/dashboard.php">
            <img src="<?= htmlspecialchars($bp) ?>assets/images/hero-banner.png" alt="Logo" class="lp-nav-logo">
            Thor's Thunder Wash
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($bp) ?>admin/dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($bp) ?>admin/bookings.php">Bookings</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($bp) ?>admin/services.php">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($bp) ?>admin/machines.php">Machines</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($bp) ?>admin/inventory.php">Inventory</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($bp) ?>admin/reports.php">Reports</a></li>
                <?php if ($isAdmin): ?>
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($bp) ?>admin/users.php">Users</a></li>
                <?php endif; ?>
            </ul>
            <span class="navbar-text text-white me-3 small"><?= htmlspecialchars($currentUser['full_name']) ?> (<?= htmlspecialchars($currentUser['role']) ?>)</span>
            <a class="btn btn-sm lp-btn-accent" href="<?= htmlspecialchars($bp) ?>logout.php">Logout</a>
        </div>
    </div>
</nav>
