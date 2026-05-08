<?php
require __DIR__ . '/_auth.php';

function status_progress(string $status): int
{
    return match ($status) {
        'pending' => 15,
        'washing' => 40,
        'drying' => 70,
        'ready' => 95,
        'completed' => 100,
        default => 5,
    };
}

$code = trim((string) ($_GET['code'] ?? ''));
$laundry = new Laundry();
$row = $code !== '' ? $laundry->getTransactionByTracking($code) : null;

if ($row && (int) $row['user_id'] !== (int) $currentUser['id']) {
    $row = null;
}

$pageTitle = "Track order — Thor's Thunder Wash";
$bp = base_path();
require_once dirname(__DIR__) . '/includes/partials/head.php';
require __DIR__ . '/_nav.php';
?>
<div class="container pb-5">
    <div class="card lp-card" style="max-width:640px">
        <div class="card-header">Order status</div>
        <div class="card-body">
            <?php if (!$row): ?>
                <p class="text-muted">No order found for that code on your account.</p>
                <a href="<?= htmlspecialchars($bp) ?>customer/home.php" class="btn btn-outline-secondary">Back</a>
            <?php else: ?>
                <p><strong>Code:</strong> <code><?= htmlspecialchars($row['tracking_code']) ?></code></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($row['status']) ?></p>
                <div class="progress lp-progress mb-3" style="height: 1.5rem">
                    <div class="progress-bar" style="width: <?= (int) status_progress($row['status']) ?>%">
                        <?= (int) status_progress($row['status']) ?>%
                    </div>
                </div>
                <a href="<?= htmlspecialchars($bp) ?>customer/invoice.php?code=<?= urlencode($row['tracking_code']) ?>" class="btn lp-btn-accent">View invoice</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/partials/footer.php'; ?>
