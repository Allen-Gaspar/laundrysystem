<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/paths.php';

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['Admin', 'Staff'], true)) {
    header('Location: ' . base_path() . 'login.php');
    exit;
}

$currentUser = $_SESSION['user'];
