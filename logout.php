<?php
require_once 'includes/bootstrap.php';
require_once 'includes/paths.php';

session_unset();

session_destroy();

header('Location: ' . base_path() . 'login.php');
exit;