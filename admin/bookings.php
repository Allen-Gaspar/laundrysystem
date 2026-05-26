<?php
require __DIR__ . '/_auth.php';

$laundry = new Laundry();
$sms = new SmsService();
$emailService = new EmailService();
$pageTitle = "Bookings — Thor's Thunder Wash";

// 1. PINAGSAMA: Dito pinoproseso ang pagbabago ng Status ng Booking
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

// 2. BAGONG DAGDAG: Dito pinoproseso ang Pag-update ng Timbang, Serbisyo, at Auto-Amount Computation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_weight_service') {
    $txId = (int)$_POST['transaction_id'];
    $serviceId = (int)$_POST['service_id'];
    $weightKg = (float)$_POST['weight_kg'];

    // Kuhanin ang presyo ng piniling serbisyo gamit ang orihinal na model function mo
    $serviceRow = $laundry->getService($serviceId); 
    
    if ($serviceRow) {
                // 2. Dynamic Price Computation Handler
        $serviceNameLower = strtolower($serviceRow['name']);
        
        if (strpos($serviceNameLower, 'wash, dry & fold') !== false || strpos($serviceNameLower, 'fold') !== false) {
            // Check if the weight is exactly 8kg to apply the bundle promo discount
            if ((float)$weightKg === 8.0) {
                $computedAmount = 179.00;
            } else {
                // Otherwise, calculate using the standard ₱23.00 per kg rate and round up
                $computedAmount = ceil(23.00 * $weightKg);
            }
        } else {
            // Standard dynamic multiplication rule for regular per-kg services (Wash only, Dry only)
            $computedAmount = round((float)$serviceRow['price_per_kg'] * $weightKg, 2);
        }

        
        try {
            // MATALINONG TRICK: Dahil gumagana ang getService(), hihiramin natin ang PDO connection instance 
            // sa pamamagitan ng Reflection helper para lampasan ang "private" property lock!
            $reflector = new ReflectionClass($laundry);
            $dbProperty = $reflector->getProperty('db');
            $dbProperty->setAccessible(true); // Binubuksan ang private property nang ligtas
            $pdo = $dbProperty->getValue($laundry);
            
            if (!$pdo instanceof PDO) {
                throw new Exception("Hindi ma-access ang database module instance.");
            }
            
            // Isagawa ang pag-update sa database nang diretso at mabilis
            $stmt = $pdo->prepare("UPDATE transactions SET service_id = ?, weight_kg = ?, amount = ? WHERE id = ?");
            $stmt->execute([$serviceId, $weightKg, $computedAmount, $txId]);
            
            $_SESSION['flash_ok'] = 'Booking weight, service, and amount updated successfully!';
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "Hindi mai-save ang detalye: " . $e->getMessage();
        }

    } else {
        $_SESSION['flash_err'] = 'Invalid service selection.';
    }
    header('Location: ' . base_path() . 'admin/bookings.php');
    exit;
}


$bookings = $laundry->listIncomingBookings();
$machines = $laundry->allMachines();

// 3. SINOLVE ANG PROBLIMA: Tinawag natin ang allServices(true) para lumabas ang tatlong uri ng serbisyo sa dropdown modal mo!
$services = $laundry->allServices(true);

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
                                <?php $isUnweighed = (!isset($b['weight_kg']) || (float)$b['weight_kg'] <= 0); ?>
                                
                                <!-- Status Action Form -->
                                <form method="post" class="d-inline mb-1 status-form">
                                    <input type="hidden" name="action" value="status">
                                    <input type="hidden" name="transaction_id" value="<?= (int) $b['id'] ?>">
                                    <select name="status" class="form-select form-select-sm d-inline-block w-auto" <?= $isUnweighed ? 'disabled' : '' ?>>
                                        <?php foreach (['pending', 'washing', 'drying', 'ready', 'completed', 'cancelled'] as $st): ?>
                                            <option value="<?= $st ?>" <?= $b['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm lp-btn-accent" <?= $isUnweighed ? 'disabled' : '' ?>>Set</button>
                                </form>

                                <!-- Machine Allocation Selection Group -->
                                <div class="input-group input-group-sm mt-1 assign-wrap">
                                    <select class="form-select machine-select" data-tx="<?= (int) $b['id'] ?>" <?= $isUnweighed ? 'disabled' : '' ?>>
                                        <option value="0">Unassign —</option>
                                        <?php foreach ($machines as $m): ?>
                                            <option value="<?= (int) $m['id'] ?>" <?= ((int)($b['machine_id'] ?? 0) === (int) $m['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['status']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-outline-secondary machine-assign-btn" <?= $isUnweighed ? 'disabled' : '' ?>>Assign</button>
                                </div>

                                <!-- Trigger Control Button for Modal Adjustment -->
                                <button type="button" class="btn btn-sm btn-primary mt-1 w-100" data-bs-toggle="modal" data-bs-target="#editModal<?= $b['id'] ?>">
                                    ⚙️ Update Weight / Service
                                </button>

                                <!-- Pop-up Dynamic Edit Modal Frame Box (MUST STAY INSIDE THE ROW ELEMENT LOOP) -->
                                <div class="modal fade" id="editModal<?= $b['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $b['id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="update_weight_service">
                                            <input type="hidden" name="transaction_id" value="<?= (int)$b['id'] ?>">
                                            
                                            <div class="modal-content text-dark text-start">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editModalLabel<?= $b['id'] ?>">⚙️ Update Booking Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="small text-muted mb-3">Tracking Code: <strong class="text-danger"><?= htmlspecialchars($b['tracking_code']) ?></strong></p>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Tunay na Timbang (kg)</label>
                                                        <input type="number" step="0.01" min="0.01" name="weight_kg" class="form-control" value="<?= (float)($b['weight_kg'] ?? 0) > 0 ? (float)$b['weight_kg'] : '' ?>" placeholder="Ipasok ang timbang (hal. 5.5)" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Uri ng Serbisyo</label>
                                                        <select name="service_id" class="form-select" required>
                                                            <option value="">Mamili ng Serbisyo...</option>
                                                            <?php foreach ($services as $s): ?>
                                                                <option value="<?= $s['id'] ?>" <?= (int)($b['service_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?> — P<?= number_format($s['price_per_kg'], 2) ?>/kg</option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">I-cancel</button>
                                                    <button type="submit" class="btn btn-success">Save & Auto-Compute Amount</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
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

// Handler para sa pagkumpirma ng pagbabago ng Status ng Booking
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

// NATAMAAN: Pinalitan ang selector papuntang '.machine-assign-btn' para magtugma sa iyong HTML layout button
$(document).on('click', '.machine-assign-btn', function () {
  const row = $(this).closest('tr');
  const tx = row.data('tx');
  // Kukuha ng tamang halaga mula sa pinakamalapit na select element ng button
  const mid = row.find('.machine-select').val();

  $.post(ajaxAssignUrl, { transaction_id: tx, machine_id: mid }, function (res) {
    // TANDAAN: Sumasagot ang AJAX engine mo ng { success: true } o { ok: true } 
    // Ginawa nating (res.ok || res.success) para sigurado itong pumasok sa database block
    if (res.ok || res.success) {
      LaundryNotify.success('Assigned', res.message || 'Machine assignment saved.', 1800);
      setTimeout(function () { location.reload(); }, 600);
    } else {
      LaundryNotify.error('Failed', res.message || 'Hindi mai-save ang makina.');
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
