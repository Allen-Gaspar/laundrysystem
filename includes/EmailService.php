<?php

class EmailService
{
    private string $logFile;
    private string $from;
    private string $appName;

    public function __construct()
    {
        $cfg = app_config();
        $this->from = (string) ($cfg['app']['mail_from'] ?? 'no-reply@localhost');
        $this->appName = (string) ($cfg['app']['name'] ?? 'Laundry');
        $this->logFile = dirname(__DIR__) . '/storage/email_log.txt';
    }

    public function sendBookingCreated(array $tx): void
    {
        $subject = "Booking confirmed: {$tx['tracking_code']}";
        $message = $this->buildBookingMessage($tx);
        $this->send((string) $tx['email'], $subject, $message);
    }

    public function sendStatusUpdated(array $tx): void
    {
        $label = $this->statusLabel((string) $tx['status']);
        $subject = "Order update: {$tx['tracking_code']} — {$label}";
        $message = $this->buildStatusMessage($tx);
        $this->send((string) $tx['email'], $subject, $message);
    }

    public function sendWelcomeRegistered(string $email, string $fullName, string $username): void
    {
        $subject = 'Welcome — your account is ready';
        $message = "Hi {$fullName},\n\n"
            . "Your account at {$this->appName} was created successfully.\n"
            . "Username: {$username}\n\n"
            . "You can sign in anytime to book and track your laundry.\n\n"
            . 'Thank you for choosing ' . $this->appName . '.';
        $this->send($email, $subject, $message);
    }

    public function sendPasswordResetLink(string $email, string $fullName, string $resetUrl): void
    {
        $subject = 'Reset your password';
        $message = "Hi {$fullName},\n\n"
            . "We received a request to reset your password for {$this->appName}.\n"
            . "Open this link to choose a new password (it expires in one hour):\n\n"
            . "{$resetUrl}\n\n"
            . "If you did not ask for this, you can ignore this email.\n\n"
            . "Note: For your security we never send your actual password by email.";
        $this->send($email, $subject, $message);
    }

    public function sendPasswordChangedNotice(string $email, string $fullName): void
    {
        $subject = 'Your password was changed';
        $message = "Hi {$fullName},\n\n"
            . "The password for your {$this->appName} account was just changed.\n"
            . "If this was not you, contact support immediately.\n";
        $this->send($email, $subject, $message);
    }

    private function send(string $to, string $subject, string $message): void
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        $ok = MailTransport::send($to, $subject, $message, $this->from, $this->appName);
        $line = date('c') . " | to={$to} | subject={$subject} | sent=" . ($ok ? 'ok' : 'failed') . PHP_EOL;
        @file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }

    private function statusLabel(string $status): string
    {
        $map = [
            'pending' => 'Pending',
            'washing' => 'Washing',
            'drying' => 'Drying',
            'ready' => 'Ready for pickup',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
        return $map[$status] ?? ucfirst($status);
    }

    private function buildBookingMessage(array $tx): string
    {
        return "Hi {$tx['full_name']},\n\n"
            . "Your booking is confirmed.\n"
            . "Tracking code: {$tx['tracking_code']}\n"
            . "Service: {$tx['service_name']}\n"
            . "Weight: {$tx['weight_kg']} kg\n"
            . "Price/kg: P" . number_format((float) $tx['price_per_kg'], 2) . "\n"
            . "Total: P" . number_format((float) $tx['amount'], 2) . "\n"
            . "Notes: " . (($tx['notes'] ?? '') !== '' ? $tx['notes'] : '-') . "\n"
            . "Booked at: {$tx['created_at']}\n"
            . "Start time: " . (!empty($tx['started_at']) ? $tx['started_at'] : 'Not started yet') . "\n"
            . "End time: " . (!empty($tx['completed_at']) ? $tx['completed_at'] : 'Not completed yet') . "\n\n"
            . 'Thank you for choosing ' . $this->appName . '.';
    }

    private function buildStatusMessage(array $tx): string
    {
        $st = (string) $tx['status'];
        $label = $this->statusLabel($st);
        return "Hi {$tx['full_name']},\n\n"
            . "Your laundry order was updated.\n"
            . "Tracking code: {$tx['tracking_code']}\n"
            . "Status: {$label}\n"
            . "Service: {$tx['service_name']}\n"
            . "Booked at: {$tx['created_at']}\n"
            . "Start time: " . (!empty($tx['started_at']) ? $tx['started_at'] : '—') . "\n"
            . "End time: " . (!empty($tx['completed_at']) ? $tx['completed_at'] : '—') . "\n"
            . "Total: P" . number_format((float) $tx['amount'], 2) . "\n\n"
            . $this->appName;
    }
}
