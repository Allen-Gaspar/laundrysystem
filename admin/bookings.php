<?php
require __DIR__ . '/_auth.php';

$laundry = new Laundry();
$sms = new SmsService();
$emailService = new EmailService();
$pageTitle = "Bookings — Thor's Thunder Wash";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'status') {
    $tid = (int) ($_POST['transaction_id'] ?? 0);
    $st = (string) ($_POST['status'] ?? '');
    try {
        $laundry->updateStatus($tid, $st, $sms, $emailService);
        $_SESSION['flash_ok'] = 'Status updated.';
    } catch (Throwable $e) {
        $_SESSION['flash_err'] = 'Invalid status change.';
    }
    header('Location: ' . base_path() . 'admin/bookings.php');
    exit;
}

$bookings = $laundry->listIncomingBookings();
$machines = $laundry->allMachines();
$flashOk = $_SESSION['flash_ok'] ?? '';
$flashErr = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_ok'], $_SESSION['flash_err']);

$bp = base_path();

require_once dirname(__DIR__) . '/includes/partials/head.php';
require __DIR__ . '/_nav.php';
?>
<div class="container pb-5">
    <div class="card lp-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Incoming bookings</span>
            <a class="btn btn-sm btn-outline-light" href="<?= htmlspecialchars($bp) ?>admin/invoice_list.php">Invoices</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                    <tr>
                        <th>Tracking</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Weight</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th style="min-width:160px">Booked At</th>
                        <th>Machine</th>
                        <th style="min-width:220px">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr data-tx="<?= (int) $b['id'] ?>">
                            <td>
                                <code><?= htmlspecialchars($b['tracking_code']) ?></code>
                                <a class="small ms-1" target="_blank" href="<?= htmlspecialchars($bp) ?>admin/invoice_print.php?code=<?= urlencode($b['tracking_code']) ?>">Invoice</a>
                            </td>
                            <td>
                                <?= htmlspecialchars($b['full_name']) ?>
                                <div class="small text-muted"><?= htmlspecialchars($b['phone']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($b['service_name']) ?></td>
                            <td><?= htmlspecialchars((string) $b['weight_kg']) ?> kg</td>
                            <td>P<?= number_format((float) $b['amount'], 2) ?></td>
                            <td><span class="badge bg-primary status-pill"><?= htmlspecialchars($b['status']) ?></span></td>
                            <td class="small"><?= htmlspecialchars((string) $b['created_at']) ?></td>
                            <td class="machine-cell"><?= htmlspecialchars($b['machine_name'] ?? '—') ?></td>
                            <td>
                                <form method="post" class="d-inline mb-1 status-form">
                                    <input type="hidden" name="action" value="status">
                                    <input type="hidden" name="transaction_id" value="<?= (int) $b['id'] ?>">
                                    <select name="status" class="form-select form-select-sm d-inline-block w-auto">
                                        <?php foreach (['pending', 'washing', 'drying', 'ready', 'completed', 'cancelled'] as $st): ?>
                                            <option value="<?= $st ?>" <?= $b['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm lp-btn-accent">Set</button>
                                </form>
                                <div class="input-group input-group-sm mt-1 assign-wrap">
                                    <select class="form-select machine-select" data-tx="<?= (int) $b['id'] ?>">
                                        <option value="0">— Unassign —</option>
                                        <?php foreach ($machines as $m): ?>
                                            <option value="<?= (int) $m['id'] ?>"
                                                <?= ((int) ($b['machine_id'] ?? 0) === (int) $m['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['status']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary btn-assign-machine">Assign</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$bookings): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">No active bookings.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/partials/footer.php'; ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
const ajaxAssignUrl = <?= json_encode($bp . 'admin/ajax/assign_machine.php') ?>;

document.querySelectorAll('.status-form').forEach(function (form) {
  form.addEventListener('submit', function (e) {
    if (form.dataset.lpConfirm === '1') {
      form.dataset.lpConfirm = '0';
      return;
    }
    e.preventDefault();
    LaundryNotify.confirm({ title: 'Update status?', confirmText: 'Yes' }).then(function (ok) {
      if (!ok) return;
      form.dataset.lpConfirm = '1';
      form.submit();
    });
  });
});

$(document).on('click', '.btn-assign-machine', function () {
  const wrap = $(this).closest('tr');
  const tx = wrap.data('tx');
  const mid = wrap.find('.machine-select').val();
  $.post(ajaxAssignUrl, { transaction_id: tx, machine_id: mid }, function (res) {
    if (res.ok) {
      LaundryNotify.success('Assigned', res.message, 1800);
      setTimeout(function () { location.reload(); }, 600);
    } else {
      LaundryNotify.error('Failed', res.message);
    }
  }, 'json').fail(function () {
    LaundryNotify.error('Error', 'Network error');
  });
});

<?php if ($flashOk): ?>
document.addEventListener('DOMContentLoaded', function () { LaundryNotify.success('OK', <?= json_encode($flashOk) ?>); });
<?php endif; ?>
<?php if ($flashErr): ?>
document.addEventListener('DOMContentLoaded', function () { LaundryNotify.error('Error', <?= json_encode($flashErr) ?>); });
<?php endif; ?>
</script>
