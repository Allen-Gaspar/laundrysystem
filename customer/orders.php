<?php
require __DIR__ . '/_auth.php';

$laundry = new Laundry();
$pageTitle = "My Booking — Thor's Thunder Wash";
$list = $laundry->listTransactionsForCustomer((int) $currentUser['id']);
$bp = base_path();

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

require_once dirname(__DIR__) . '/includes/partials/head.php';
require __DIR__ . '/_nav.php';
?>
<div class="container pb-5">
    <div class="card lp-card">
        <div class="card-header">Transaction history</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                    <tr><th>Code</th><th>Service</th><th>Weight</th><th>Amount</th><th>Booked At</th><th>Start</th><th>End</th><th>Progress</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($list as $r): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($r['tracking_code']) ?></code></td>
                            <td><?= htmlspecialchars($r['service_name']) ?></td>
                            <td><?= htmlspecialchars((string) $r['weight_kg']) ?> kg</td>
                            <td>P<?= number_format((float) $r['amount'], 2) ?></td>
                            <td class="small"><?= htmlspecialchars((string) $r['created_at']) ?></td>
                            <td class="small"><?= htmlspecialchars((string) (($r['started_at'] ?? null) ?: '—')) ?></td>
                            <td class="small"><?= htmlspecialchars((string) (($r['completed_at'] ?? null) ?: '—')) ?></td>
                            <td style="min-width:140px">
                                <div class="progress lp-progress" style="height:0.6rem">
                                    <div class="progress-bar" style="width:<?= (int) status_progress($r['status']) ?>%"></div>
                                </div>
                                <span class="small text-muted"><?= htmlspecialchars($r['status']) ?></span>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($bp) ?>customer/invoice.php?code=<?= urlencode($r['tracking_code']) ?>">Invoice</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$list): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">No orders yet. <a href="<?= htmlspecialchars($bp) ?>customer/book.php">Book now</a></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/partials/footer.php'; ?>
