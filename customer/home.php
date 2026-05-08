<?php
require __DIR__ . '/_auth.php';

$laundry = new Laundry();
$pageTitle = "Home — Thor's Thunder Wash";
$current = $laundry->getCurrentOrderForUser((int) $currentUser['id']);

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

$bp = base_path();
require_once dirname(__DIR__) . '/includes/partials/head.php';
require __DIR__ . '/_nav.php';
?>
<div class="container pb-5">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card lp-card h-100">
                <div class="card-header">Current order</div>
                <div class="card-body">
                    <?php if ($current): ?>
                        <p class="mb-1"><strong>Tracking:</strong> <code><?= htmlspecialchars($current['tracking_code']) ?></code></p>
                        <p class="mb-1"><strong>Service:</strong> <?= htmlspecialchars($current['service_name']) ?></p>
                        <p class="mb-1"><strong>Status:</strong> <?= htmlspecialchars($current['status']) ?></p>
                        <p class="mb-3"><strong>Total:</strong> P<?= number_format((float) $current['amount'], 2) ?></p>
                        <div class="progress lp-progress mb-2" style="height: 1.25rem">
                            <div class="progress-bar" role="progressbar"
                                 style="width: <?= (int) status_progress($current['status']) ?>%"
                                 aria-valuenow="<?= (int) status_progress($current['status']) ?>"
                                 aria-valuemin="0" aria-valuemax="100">
                                <?= (int) status_progress($current['status']) ?>%
                            </div>
                        </div>
                        <p class="small text-muted mb-0">Progress updates when staff change your order status.</p>
                    <?php else: ?>
                        <p class="text-muted mb-3">You have no active laundry in progress.</p>
                        <a href="<?= htmlspecialchars($bp) ?>customer/book.php" class="btn lp-btn-accent">Book a service</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card lp-card h-100">
                <div class="card-header">Track by code</div>
                <div class="card-body">
                    <form method="get" action="<?= htmlspecialchars($bp) ?>customer/track.php" class="needs-validation" novalidate>
                        <label class="form-label">Tracking code</label>
                        <input type="text" name="code" class="form-control mb-3" required placeholder="e.g. LND-20250321-ABC123">
                        <button type="submit" class="btn lp-btn-accent">View status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/partials/footer.php'; ?>
<script>
(function () {
  const f = document.querySelector('.needs-validation');
  if (!f) return;
  f.addEventListener('submit', function (e) {
    if (!f.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
    f.classList.add('was-validated');
  });
})();
</script>
