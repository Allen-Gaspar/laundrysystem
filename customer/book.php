<?php
require __DIR__ . '/_auth.php';

$laundry = new Laundry();
$emailService = new EmailService();
$pageTitle = "Book — Thor's Thunder Wash";
$services = $laundry->allServices(true);
$errors = [];
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['step'] ?? '') === 'confirm') {
    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $weight = (float) ($_POST['weight_kg'] ?? 0);
    $notes = trim((string) ($_POST['notes'] ?? ''));
    if ($serviceId <= 0) {
        $errors[] = 'Select a service.';
    }

        if (!$errors) {
        try {
            // Fetch the selected service first to check its naming parameters
            $serviceRow = $laundry->getService($serviceId);
            if ($serviceRow) {
                $serviceNameLower = strtolower($serviceRow['name']);
                
                // If Wash, Dry & Fold bundle rate applies
                if (strpos($serviceNameLower, 'wash, dry & fold') !== false || strpos($serviceNameLower, 'fold') !== false) {
                    if ((float)$weight === 8.0) {
                        $finalAmount = 179.00;
                    } else {
                        $finalAmount = ceil(23.00 * $weight);
                    }
                } else {
                    // Standard per-kg pricing rules for standard items
                    $finalAmount = round((float)$serviceRow['price_per_kg'] * $weight, 2);
                }
                
                // Use your model function to submit the booking order
                $result = $laundry->createBooking((int) $currentUser['id'], $serviceId, $weight, $notes ?: null);
                
                // FORCE RE-UPDATE: This guarantees that the stored database amount column is always a clean whole number!
                $reflector = new ReflectionClass($laundry);
                $dbProperty = $reflector->getProperty('db');
                $dbProperty->setAccessible(true);
                $pdo = $dbProperty->getValue($laundry);
                
                if ($pdo instanceof PDO) {
                    $stmt = $pdo->prepare("UPDATE transactions SET amount = ? WHERE id = ?");
                    $stmt->execute([$finalAmount, (int)$result['id']]);
                    
                    // Refresh array value for immediate success layout display parameters
                    $result['amount'] = $finalAmount;
                }
            }

            $tx = $laundry->getTransactionById((int) $result['id']);
            if ($tx) {
                $emailService->sendBookingCreated($tx);
            }
        } catch (Throwable $e) {
            $errors[] = 'Could not create booking.';
        }
    }
}

$bp = base_path();
require_once dirname(__DIR__) . '/includes/partials/head.php';
require __DIR__ . '/_nav.php';

$step = (int) ($_GET['step'] ?? $_POST['step_visible'] ?? 1);
if ($step < 1) {
    $step = 1;
}
if ($step > 3) {
    $step = 3;
}
?>
<div class="container pb-5" style="max-width:720px">
    <?php if ($result): ?>
        <div class="card lp-card">
            <div class="card-header">Booking complete</div>
            <div class="card-body text-center py-4">
                <p class="mb-2">Your tracking code:</p>
                <h3 class="mb-4"><code id="trackCode"><?= htmlspecialchars($result['tracking_code']) ?></code></h3>
                
                <!-- BINAGO: Kung walang presyo (0), itatago ang P0.00 at ipapakita ang paalala sa bayad -->
                <?php if ((float)$result['amount'] > 0): ?>
                    <p class="text-muted">Total: <strong>P<?= number_format($result['amount'], 2) ?></strong></p>
                <?php else: ?>
                    <p class="text-primary fw-bold" style="font-size: 1rem; max-width: 400px; margin: 0 auto 1.5rem;">
                        Ang kabuuang babayaran ay kukuwentahin sa shop matapos matimbang ng aming staff.
                    </p>
                <?php endif; ?>

                <a href="<?= htmlspecialchars($bp) ?>customer/home.php" class="btn lp-btn-accent mt-2">Home</a>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
          LaundryNotify.success('Booking successful!', 'Tracking: ' + document.getElementById('trackCode').textContent, 0);
        });
        </script>

    <?php else: ?>
        <?php if ($errors): ?>
            <div class="alert alert-danger"><?= htmlspecialchars(implode(' ', $errors)) ?></div>
        <?php endif; ?>

        <ul class="nav nav-pills mb-4 justify-content-center gap-2">
            <li class="nav-item"><span class="nav-link <?= $step === 1 ? 'active lp-btn-accent' : 'disabled' ?>">1. Detalye</span></li>
            <li class="nav-item"><span class="nav-link <?= $step === 2 ? 'active lp-btn-accent' : 'disabled' ?>">2. Bigat</span></li>
            <li class="nav-item"><span class="nav-link <?= $step === 3 ? 'active lp-btn-accent' : 'disabled' ?>">3. Kumpirmasyon</span></li>
        </ul>

        <?php
        $notes = $_POST['notes'] ?? '';
        $selService = (int) ($_POST['service_id'] ?? 0);
        $weight = $_POST['weight_kg'] ?? '';
        ?>

        <?php if ($step === 1): ?>
            <div class="card lp-card">
                <div class="card-header">Unang Hakbang — Detalye ng Ipalalaba</div>
                <div class="card-body">
                    <form method="get" class="needs-validation" novalidate>
                        <input type="hidden" name="step" value="2">
                        <div class="mb-3">
                            <label class="form-label">Ano ang ipalalaba mo? (kailangan)</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="kumot, damit... (maaari ring ilagay ang estimated weight kung may timbangan kayo)" required><?= htmlspecialchars((string) $notes) ?></textarea>
                        </div>
                        <button type="submit" class="btn lp-btn-accent">Sunod</button>
                    </form>
                </div>
            </div>
        <?php elseif ($step === 2): ?>
            <div class="card lp-card">
                <div class="card-header">Step 2 — Bigat at Serbisyo</div>
                <div class="card-body">
                    <form method="get" class="needs-validation" novalidate>
                        <input type="hidden" name="step" value="3">
                        <input type="hidden" name="notes" value="<?= htmlspecialchars((string) ($_GET['notes'] ?? '')) ?>">
                        <div class="mb-3">
                            <label class="form-label">Inaasahang Timbang (kg)</label>
                            <input type="number" step="0.1" min="0" name="weight_kg" placeholder="(optional — Pwede timbangin sa shop)" class="form-control" value="<?= htmlspecialchars((string) ($_GET['weight_kg'] ?? '')) ?>">

                        </div>
                        <div class="mb-3">
                            <label class="form-label">Serbisyo</label>
                            <select name="service_id" class="form-select" required>
                                <option value="">Mamili...</option>
                                <?php foreach ($services as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> — P<?= number_format((float) $s['price_per_kg'], 2) ?>/kg</option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Mamili ng Serbisyo</div>
                        </div>
                        <button type="submit" class="btn lp-btn-accent">Sunod</button>
                        <a href="?step=1&notes=<?= urlencode((string) ($_GET['notes'] ?? '')) ?>" class="btn btn-link">Balik</a>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <?php
            $notes3 = (string) ($_GET['notes'] ?? '');
            $w3 = (float) ($_GET['weight_kg'] ?? 0);
                        $sid3 = (int) ($_GET['service_id'] ?? 0);
            $svcRow = $sid3 ? $laundry->getService($sid3) : null;
            
            if ($svcRow && $w3 > 0) {
                $serviceNameLower = strtolower($svcRow['name']);
                
                if (strpos($serviceNameLower, 'wash, dry & fold') !== false || strpos($serviceNameLower, 'fold') !== false) {
                    // Check if the weight is exactly 8kg to apply the bundle promo discount
                    if ((float)$w3 === 8.0) {
                        $est = 179.00;
                    } else {
                        // Standard ₱23.00 per kg calculation rounded up to the nearest whole peso
                        $est = ceil(23.00 * $w3);
                    }
                } else {
                    $est = round((float) $svcRow['price_per_kg'] * $w3, 2);
                }
            } else {
                $est = 0;
            }

            ?>
                        <div class="card lp-card">
                <div class="card-header">Step 3 — Kumpirmasyon</div>
                <div class="card-body">
                    <?php if (!$svcRow): ?>
                        <p class="text-danger">Nakakalimutan ang serbisyo. <a href="?step=2">Magbalik</a></p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Service</span>
                                <strong><?= htmlspecialchars($svcRow['name']) ?></strong>
                            </li>
                            
                            <?php if ($w3 > 0): ?>
                                <!-- Ipakita lamang ito kung may inilagay na timbang -->
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Weight</span>
                                    <strong><?= htmlspecialchars((string) $w3) ?> kg</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Est. total</span>
                                    <strong>P<?= number_format($est, 2) ?></strong>
                                </li>
                            <?php else: ?>
                                <!-- Paalala na ipapakita kapag BLANKO o walang timbang -->
                                <li class="list-group-item text-center py-3 bg-light">
                                    <span class="text-muted d-block small mb-1">Paalala sa Timbang at Presyo:</span>
                                    <strong class="text-primary text-balance" style="font-size: 0.95rem;">
                                        Pumunta sa aming shop para matimbang at macompute ng staff.
                                    </strong>
                                </li>
                            <?php endif; ?>

                            <?php if ($notes3 !== ''): ?>
                                <li class="list-group-item">
                                    <span class="text-muted small">Notes</span><br>
                                    <?= nl2br(htmlspecialchars($notes3)) ?>
                                </li>
                            <?php endif; ?>
                        </ul>
                        
                        <form method="post" class="needs-validation" novalidate id="confirmForm">
                            <input type="hidden" name="step" value="confirm">
                            <input type="hidden" name="step_visible" value="3">
                            <input type="hidden" name="service_id" value="<?= $sid3 ?>">
                            <input type="hidden" name="weight_kg" value="<?= htmlspecialchars((string) $w3) ?>">
                            <input type="hidden" name="notes" value="<?= htmlspecialchars($notes3) ?>">
                            <button type="submit" class="btn lp-btn-accent">Kumpirmahin ang booking</button>
                            <a href="?step=2&notes=<?= urlencode($notes3) ?>&weight_kg=<?= $w3 > 0 ? urlencode((string)$w3) : '' ?>&service_id=<?= $sid3 ?>" class="btn btn-link">Balik</a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>
    <?php endif; ?>
</div>
<?php require_once dirname(__DIR__) . '/includes/partials/footer.php'; ?>
<script>
(function () {
  document.querySelectorAll('.needs-validation').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    });
  });
  const cf = document.getElementById('confirmForm');
  if (cf) {
    cf.addEventListener('submit', function (e) {
      if (cf.dataset.lpOk === '1') {
        cf.dataset.lpOk = '0';
        return;
      }
      e.preventDefault();
      LaundryNotify.confirm({ title: 'Kumpirmahin ang Booking?', text: 'Maraming Salamat sa pag-book, hinatayin lamang ang aming update .', confirmText: 'Okay' }).then(function (ok) {
        if (ok) {
          cf.dataset.lpOk = '1';
          cf.submit();
        }
      });
    });
  }
})();
</script>