<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/paths.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['Admin', 'Staff'], true)) {
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

$tid = (int) ($_POST['transaction_id'] ?? 0);
$mid = $_POST['machine_id'] ?? '';
$machineId = $mid === '' || $mid === '0' ? null : (int) $mid;

$laundry = new Laundry();
$ok = $laundry->assignMachine($tid, $machineId);

echo json_encode([
    'ok' => $ok,
    'message' => $ok ? 'Machine updated.' : 'Could not assign (machine busy or invalid).',
]);
