<?php
require_once dirname(__DIR__) . '/includes/paths.php';
$bp = base_path();
?>
<!-- Fixed: Changed class to 'fixed-top' to lock the navbar at the very top of the screen always -->
<nav class="navbar navbar-expand-lg navbar-dark lp-navbar fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-inline-flex align-items-center gap-2" href="<?= htmlspecialchars($bp) ?>customer/home.php">
    <!-- FIXED: Tries to load logo.png, falls back to hero-banner.png if missing, and shows a bolt icon if both fail -->
    <img src="<?= htmlspecialchars($bp) ?>assets/images/logo.png" 
         onerror="this.src='<?= htmlspecialchars($bp) ?>assets/images/hero-banner.png'; this.onerror=function(){this.style.display='none';};"
         alt="⚡" 
         class="lp-nav-logo"
         style="width: 34px; height: 34px; border-radius: 50%; object-fit: cover; object-position: left center;">
    Thor's Thunder Wash ⚡
</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navCust">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navCust">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($bp) ?>customer/home.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($bp) ?>customer/book.php">Book</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($bp) ?>customer/orders.php">My Laundry</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($bp) ?>customer/profile.php">Profile</a></li>
            </ul>
            <span class="navbar-text text-white me-3 small"><?= htmlspecialchars($currentUser['full_name']) ?></span>
            <a class="btn btn-sm lp-btn-accent" href="<?= htmlspecialchars($bp) ?>logout.php">Logout</a>
        </div>
    </div>
</nav>
