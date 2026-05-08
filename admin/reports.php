<?php
require __DIR__ . '/_auth.php';

$laundry = new Laundry();
$period = (string) ($_GET['period'] ?? 'daily');
if (!in_array($period, ['daily', 'weekly', 'monthly', 'yearly'], true)) {
    $period = 'daily';
}
$pageTitle = "Sales reports — Thor's Thunder Wash";
$series = $laundry->salesByPeriod($period);
$labels = $series['labels'];
$values = $series['values'];

require_once dirname(__DIR__) . '/includes/partials/head.php';
require __DIR__ . '/_nav.php';
?>
<div class="container pb-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card lp-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><?= ucfirst($period) ?> sales report</span>
                    <form method="get" class="d-flex align-items-center gap-2">
                        <select name="period" class="form-select form-select-sm">
                            <?php foreach (['daily','weekly','monthly','yearly'] as $p): ?>
                                <option value="<?= $p ?>" <?= $p === $period ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-sm lp-btn-accent" type="submit">Apply</button>
                    </form>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card lp-card mb-3">
                <div class="card-header">Today</div>
                <div class="card-body">
                    <h4>P<?= number_format($laundry->salesToday(), 2) ?></h4>
                    <p class="small text-muted mb-0">Sum of transaction amounts (non-cancelled) created today.</p>
                </div>
            </div>
            <div class="card lp-card">
                <div class="card-header">Report note</div>
                <div class="card-body small text-muted">
                    Use the dropdown to switch between daily, weekly, monthly, and yearly sales.
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/partials/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('salesChart');
new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [{
      label: 'Sales (P)',
      data: <?= json_encode($values) ?>,
      borderColor: '#2c3e50',
      backgroundColor: 'rgba(44,62,80,0.1)',
      fill: true,
      tension: 0.25
    }]
  },
  options: {
    scales: {
      y: { beginAtZero: true }
    },
    plugins: { legend: { display: false } }
  }
});
</script>
