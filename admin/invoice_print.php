<?php
require __DIR__ . '/_auth.php';

$code = trim((string) ($_GET['code'] ?? ''));
$laundry = new Laundry();
$row = $code !== '' ? $laundry->getTransactionByTracking($code) : null;

if (!$row) {
    http_response_code(404);
    echo 'Invoice not found.';
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice <?= htmlspecialchars($row['tracking_code']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Inter, system-ui, sans-serif; padding: 2rem; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="container" style="max-width:640px">
    <div class="no-print mb-3">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        <a href="bookings.php" class="btn btn-outline-secondary">Back</a>
    </div>
    <h2>Thor's Thunder Wash — Invoice</h2>
    <p class="text-muted mb-4">Tracking: <strong><?= htmlspecialchars($row['tracking_code']) ?></strong></p>
    <table class="table table-bordered">
        <tr><th>Customer</th><td><?= htmlspecialchars($row['full_name']) ?></td></tr>
        <tr><th>Phone</th><td><?= htmlspecialchars($row['phone']) ?></td></tr>
        <tr><th>Service</th><td><?= htmlspecialchars($row['service_name']) ?></td></tr>
        <tr><th>Weight</th><td><?= htmlspecialchars((string) $row['weight_kg']) ?> kg</td></tr>
        <tr><th>Price/kg</th><td>P<?= number_format((float) $row['price_per_kg'], 2) ?></td></tr>
        <tr><th>Notes</th><td><?= nl2br(htmlspecialchars((string) ($row['notes'] ?: '-'))) ?></td></tr>
        <tr><th>Status</th><td><?= htmlspecialchars($row['status']) ?></td></tr>
        <tr><th>Booked at</th><td><?= htmlspecialchars((string) $row['created_at']) ?></td></tr>
        <tr><th>Start time</th><td><?= htmlspecialchars((string) ($row['started_at'] ?: 'Not started yet')) ?></td></tr>
        <tr><th>End time</th><td><?= htmlspecialchars((string) ($row['completed_at'] ?: 'Not completed yet')) ?></td></tr>
        <tr><th>Amount due</th><td><strong>P<?= number_format((float) $row['amount'], 2) ?></strong></td></tr>
    </table>
    <p class="small text-muted">Thank you for your business.</p>
</div>
</body>
</html>
