<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/paths.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'Customer') {
    header('Location: ' . base_path() . 'login.php');
    exit;
}

$currentUser = $_SESSION['user'];
