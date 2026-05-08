<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/paths.php';

$code = trim((string) ($_GET['code'] ?? ''));
$laundry = new Laundry();
$row = $code !== '' ? $laundry->getTransactionByTracking($code) : null;

$logged = !empty($_SESSION['user']) && $_SESSION['user']['role'] === 'Customer';
if ($row && $logged && (int) $row['user_id'] !== (int) $_SESSION['user']['id']) {
    $row = null;
}
 
if (!$row) {
    http_response_code(404);
    $pageTitle = "Invoice — Thor's Thunder Wash";
    require_once dirname(__DIR__) . '/includes/partials/head.php';
    echo '<div class="container py-5"><div class="card lp-card"><div class="card-body">Invoice not found.</div></div></div>';
    require_once dirname(__DIR__) . '/includes/partials/footer.php';
    exit;
}

$pageTitle = 'Invoice ' . $row['tracking_code'];
require_once dirname(__DIR__) . '/includes/partials/head.php';
?>
<div class="container py-4 pb-5">
    <div class="card lp-card">
        <div class="card-header">Thor's Thunder Wash — Invoice</div>
        <div class="card-body">
            <p class="text-muted mb-1">Tracking: <strong><?= htmlspecialchars($row['tracking_code']) ?></strong></p>
            <?php if ($logged): ?>
                <p class="small">Signed in as <?= htmlspecialchars($_SESSION['user']['full_name']) ?></p>
            <?php else: ?>
                <p class="small text-muted">Opened via secure link. <a href="<?= htmlspecialchars(base_path()) ?>login.php">Sign in</a> for full account history.</p>
            <?php endif; ?>
            <table class="table table-bordered mt-3">
                <tr><th>Service</th><td><?= htmlspecialchars($row['service_name']) ?></td></tr>
                <tr><th>Weight</th><td><?= htmlspecialchars((string) $row['weight_kg']) ?> kg</td></tr>
                <tr><th>Price/kg</th><td>P<?= number_format((float) $row['price_per_kg'], 2) ?></td></tr>
                <tr><th>Notes</th><td><?= nl2br(htmlspecialchars((string) ($row['notes'] ?: '-'))) ?></td></tr>
                <tr><th>Status</th><td><?= htmlspecialchars($row['status']) ?></td></tr>
                <tr><th>Booked at</th><td><?= htmlspecialchars((string) $row['created_at']) ?></td></tr>
                <tr><th>Start time</th><td><?= htmlspecialchars((string) ($row['started_at'] ?: 'Not started yet')) ?></td></tr>
                <tr><th>End time</th><td><?= htmlspecialchars((string) ($row['completed_at'] ?: 'Not completed yet')) ?></td></tr>
                <tr><th>Total</th><td><strong>P<?= number_format((float) $row['amount'], 2) ?></strong></td></tr>
            </table>
            <a href="<?= htmlspecialchars(base_path()) ?>customer/home.php" class="btn lp-btn-accent">Customer home</a>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/partials/footer.php'; ?>
