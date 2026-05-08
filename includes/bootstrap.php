<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/includes/paths.php';
require_once dirname(__DIR__) . '/includes/Database.php';
Database::ensureBookingTimeColumns();
Database::ensurePasswordResetColumns();
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Laundry.php';
require_once dirname(__DIR__) . '/includes/SmsService.php';
require_once dirname(__DIR__) . '/includes/MailTransport.php';
require_once dirname(__DIR__) . '/includes/EmailService.php';
