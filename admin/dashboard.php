<?php
require __DIR__ . '/_auth.php';

$pageTitle = "Dashboard — Thor's Thunder Wash";
$laundry = new Laundry();
$salesToday = $laundry->salesToday();
$pending = $laundry->countPendingBookings();
$recent = array_slice($laundry->listAllBookingsForAdmin(8), 0, 8);

$showLowStock = false;
$lowStockMsg = '';
if (!empty($_SESSION['pending_low_stock_alert'])) {
    $_SESSION['pending_low_stock_alert'] = false;
    $low = $laundry->getLowStockItems();
    if ($low) {
        $showLowStock = true;
        $lowStockMsg = implode("\n", array_map(static function ($r) {
            return $r['item_name'] . ': ' . $r['quantity'] . ' ' . $r['unit'] . ' (reorder at ' . $r['reorder_level'] . ')';
        }, $low));
    }
}

require_once dirname(__DIR__) . '/includes/partials/head.php';
require __DIR__ . '/_nav.php';
?>
<div class="container pb-5">
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card lp-card h-100">
                <div class="card-header">Today&apos;s sales</div>
                <div class="card-body">
                    <h3 class="mb-0">P<?= number_format($salesToday, 2) ?></h3>
                    <p class="text-muted small mb-0">Completed + active bookings created today</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card lp-card h-100">
                <div class="card-header">Pending bookings</div>
                <div class="card-body">
                    <h3 class="mb-0"><?= (int) $pending ?></h3>
                    <a href="<?= htmlspecialchars(base_path()) ?>admin/bookings.php" class="small">Go to command center</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card lp-card h-100">
                <div class="card-header">Quick actions</div>
                <div class="card-body d-flex flex-wrap gap-2">
                    <a class="btn btn-sm lp-btn-accent" href="<?= htmlspecialchars(base_path()) ?>admin/services.php">Services</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(base_path()) ?>admin/inventory.php">Inventory</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(base_path()) ?>admin/reports.php">Reports</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card lp-card">
        <div class="card-header">Recent transactions</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Booked At</th>
                        <th>Start</th>
                        <th>End</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent as $r): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($r['tracking_code']) ?></code></td>
                            <td><?= htmlspecialchars($r['full_name']) ?></td>
                            <td><?= htmlspecialchars($r['service_name']) ?></td>
                            <td>P<?= number_format((float) $r['amount'], 2) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($r['status']) ?></span></td>
                            <td class="small"><?= htmlspecialchars((string) $r['created_at']) ?></td>
                            <td class="small"><?= htmlspecialchars((string) ($r['started_at'] ?? '—')) ?></td>
                            <td class="small"><?= htmlspecialchars((string) ($r['completed_at'] ?? '—')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recent): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No transactions yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/partials/footer.php'; ?>
<?php if ($showLowStock): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  LaundryNotify.warning('Low stock alert', <?= json_encode($lowStockMsg) ?>);
});
</script>
<?php endif; ?>
