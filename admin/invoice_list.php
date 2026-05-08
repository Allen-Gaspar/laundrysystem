<?php
require __DIR__ . '/_auth.php';

$laundry = new Laundry();
$pageTitle = "Invoices — Thor's Thunder Wash";
$list = $laundry->listAllBookingsForAdmin(100);
$bp = base_path();

require_once dirname(__DIR__) . '/includes/partials/head.php';
require __DIR__ . '/_nav.php';
?>
<div class="container pb-5">
    <div class="card lp-card">
        <div class="card-header">Generate / view invoices</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                    <tr><th>Code</th><th>Customer</th><th>Booked</th><th>Start</th><th>End</th><th>Amount</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($list as $r): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($r['tracking_code']) ?></code></td>
                            <td><?= htmlspecialchars($r['full_name']) ?></td>
                            <td class="small"><?= htmlspecialchars((string) $r['created_at']) ?></td>
                            <td class="small"><?= htmlspecialchars((string) ($r['started_at'] ?? '—')) ?></td>
                            <td class="small"><?= htmlspecialchars((string) ($r['completed_at'] ?? '—')) ?></td>
                            <td>P<?= number_format((float) $r['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($r['status']) ?></td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= htmlspecialchars($bp) ?>admin/invoice_print.php?code=<?= urlencode($r['tracking_code']) ?>">Open</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/partials/footer.php'; ?>
