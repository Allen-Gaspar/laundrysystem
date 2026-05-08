<?php
require_once dirname(__DIR__) . '/paths.php';
$title = $pageTitle ?? "Thor's Thunder Wash";
$url = base_path();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="<?= htmlspecialchars($url) ?>assets/css/brand.css" rel="stylesheet">
</head>
<body class="lp-body">