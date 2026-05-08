<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/paths.php';

if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    if ($role === 'Customer') {
        header('Location: ' . base_path() . 'customer/home.php');
    } else {
        header('Location: ' . base_path() . 'admin/dashboard.php');
    }
    exit;
}

$baseHref = base_path();
$pageTitle = "Thor's Thunder Wash — Clean. Fast. Affordable.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Drop, relax, pick up thunderously fresh. Professional laundry service — clean, fast, affordable.">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars($baseHref) ?>assets/css/landing.css" rel="stylesheet">
</head>
<body class="landing-page d-flex flex-column min-vh-100">
    <header>
        <nav class="navbar navbar-expand-lg landing-nav sticky-top">
            <div class="container">
                <a class="navbar-brand" href="<?= htmlspecialchars($baseHref) ?>">Thor's Thunder Wash ⚡</a>
                <button class="navbar-toggler navbar-dark" type="button" data-bs-toggle="collapse" data-bs-target="#landingNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="landingNav">
                    <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                        <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                        <li class="nav-item"><a class="nav-link" href="#track">Track</a></li>
                        <li class="nav-item"><a class="nav-link" href="#why-us">Why us</a></li>
                        <li class="nav-item"><a class="nav-link landing-btn-login" href="<?= htmlspecialchars($baseHref) ?>login.php">Sign in</a></li>
                        <li class="nav-item mt-2 mt-lg-0">
                            <a class="btn landing-btn-cta" href="<?= htmlspecialchars($baseHref) ?>register.php">Get started</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="flex-grow-1">
        <section class="landing-hero-wrap" aria-label="Hero">
            <img
                src="<?= htmlspecialchars($baseHref) ?>assets/images/hero-banner.png"
                alt="Thor's Thunder Wash — Drop, Relax. Pick Up Thunderously Fresh."
                class="landing-hero-img"
                width="1920"
                height="640"
                fetchpriority="high"
            >
        </section>

        <section class="landing-tagline">
            <h1>Drop, relax — pick up thunderously fresh</h1>
            <p>Book online, track your order, and enjoy spotless laundry with the same care and energy as our name suggests. Perfect for busy families and professionals.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
                <a class="btn landing-btn-cta btn-lg px-4" href="<?= htmlspecialchars($baseHref) ?>register.php">Create free account</a>
                <a class="btn btn-outline-secondary btn-lg px-4" href="<?= htmlspecialchars($baseHref) ?>login.php">I already have an account</a>
            </div>
        </section>

        <section id="services" class="landing-features">
            <div class="container">
                <h2 class="text-center fw-bold mb-2" style="color: var(--landing-deep);">What we offer</h2>
                <p class="text-center text-muted mb-5 mx-auto" style="max-width: 540px;">Simple pricing per kilo, clear status updates, and a team that treats your clothes like their own.</p>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="landing-feature-card">
                            <div class="icon" aria-hidden="true">🧺</div>
                            <h3>Wash &amp; fold</h3>
                            <p>Choose wash-only or full wash-and-dry packages. We weigh transparently so you always know what you pay.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="landing-feature-card">
                            <div class="icon" aria-hidden="true">📱</div>
                            <h3>Track every step</h3>
                            <p>From pending to washing, drying, and ready for pickup — follow progress in your dashboard in real time.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="landing-feature-card">
                            <div class="icon" aria-hidden="true">⚡</div>
                            <h3>Fast turnaround</h3>
                            <p>We prioritize efficiency without cutting corners. Clean, fast, affordable — every time.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="track" class="pb-5">
            <div class="container" style="max-width:680px;">
                <div class="landing-feature-card">
                    <h3 class="mb-2">Track by code</h3>
                    <p class="mb-3">Already booked? Enter your tracking code to view your latest booking details and invoice.</p>
                    <form method="get" action="<?= htmlspecialchars($baseHref) ?>customer/invoice.php" class="row g-2">
                        <div class="col-md-8">
                            <input type="text" name="code" class="form-control" placeholder="e.g. LND-20260420-ABC123" required>
                        </div>
                        <div class="col-md-4 d-grid">
                            <button class="btn landing-btn-cta" type="submit">Track booking</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section id="why-us" class="landing-cta-band">
            <div class="container">
                <h2>Ready for thunderously fresh laundry?</h2>
                <p>Sign in to book a pickup, manage your profile, and view invoices. Staff and admins use the same secure login.</p>
                <a class="btn landing-btn-cta btn-lg px-5" href="<?= htmlspecialchars($baseHref) ?>login.php">Sign in to continue</a>
            </div>
        </section>
    </main>

    <footer class="landing-footer mt-auto">
        <div class="container">
            <strong>CLEAN. FAST. AFFORDABLE.</strong> — Thor's Thunder Wash · <a href="<?= htmlspecialchars($baseHref) ?>login.php" class="text-white text-decoration-underline">Login</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
