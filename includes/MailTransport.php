<?php

/**
 * Sends mail via optional SMTP (SSL 465) or PHP mail().
 * Configure in config/config.php under 'mail'.
 */
class MailTransport
{
    public static function send(string $to, string $subject, string $body, string $fromEmail, string $fromName = 'Thor\'s Thunder Wash'): bool
    {
        $cfg = app_config();
        $mail = $cfg['mail'] ?? [];
        $host = trim((string) ($mail['smtp_host'] ?? ''));
        $user = (string) ($mail['smtp_user'] ?? '');
        $pass = (string) ($mail['smtp_pass'] ?? '');
        if ($host !== '' && $user !== '' && $pass !== '') {
            $ok = self::sendSmtpSsl($mail, $to, $subject, $body, $fromEmail, $fromName);
            if ($ok) {
                return true;
            }
        }
        return self::sendPhpMail($to, $subject, $body, $fromEmail, $fromName);
    }

    private static function sendPhpMail(string $to, string $subject, string $body, string $fromEmail, string $fromName): bool
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: ' . self::encodeHeaderName($fromName) . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'X-Mailer: PHP/' . PHP_VERSION,
        ];
        $param = '';
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            $param = '-f' . $fromEmail;
        }
        $ok = @mail($to, self::encodeSubject($subject), str_replace("\n", "\r\n", $body), implode("\r\n", $headers), $param);
        return $ok;
    }

    /** SMTP over SSL (typical port 465, e.g. Gmail app password). */
    private static function sendSmtpSsl(array $mail, string $to, string $subject, string $body, string $fromEmail, string $fromName): bool
    {
        $host = (string) $mail['smtp_host'];
        $port = (int) ($mail['smtp_port'] ?? 465);
        $user = (string) $mail['smtp_user'];
        $pass = (string) $mail['smtp_pass'];
        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);
        $socket = @stream_socket_client(
            'ssl://' . $host . ':' . $port,
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $ctx
        );
        if (!$socket) {
            return false;
        }
        stream_set_timeout($socket, 30);
        try {
            self::expect($socket, '220');
            fwrite($socket, "EHLO localhost\r\n");
            self::readMultiline($socket);
            fwrite($socket, "AUTH LOGIN\r\n");
            self::expect($socket, '334');
            fwrite($socket, base64_encode($user) . "\r\n");
            self::expect($socket, '334');
            fwrite($socket, base64_encode($pass) . "\r\n");
            self::expect($socket, '235');
            fwrite($socket, 'MAIL FROM:<' . $fromEmail . ">\r\n");
            self::expect($socket, '250');
            fwrite($socket, 'RCPT TO:<' . $to . ">\r\n");
            self::expect($socket, '250');
            fwrite($socket, "DATA\r\n");
            self::expect($socket, '354');
            $hdr = "From: " . self::encodeHeaderName($fromName) . " <{$fromEmail}>\r\n";
            $hdr .= "To: <{$to}>\r\n";
            $hdr .= 'Subject: ' . self::encodeSubject($subject) . "\r\n";
            $hdr .= "MIME-Version: 1.0\r\n";
            $hdr .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $hdr .= "\r\n";
            $payload = $hdr . str_replace("\r\n", "\n", $body);
            $payload = str_replace("\n", "\r\n", $payload);
            fwrite($socket, $payload . "\r\n.\r\n");
            self::expect($socket, '250');
            fwrite($socket, "QUIT\r\n");
        } finally {
            fclose($socket);
        }
        return true;
    }

    private static function readMultiline($socket): string
    {
        $out = '';
        while ($line = fgets($socket, 8192)) {
            $out .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        return $out;
    }

    private static function expect($socket, string $codePrefix): void
    {
        $line = self::readMultiline($socket);
        if ($line === '' || strpos($line, $codePrefix) !== 0) {
            throw new RuntimeException('SMTP unexpected: ' . trim($line));
        }
    }

    private static function encodeSubject(string $s): string
    {
        if (preg_match('/[^\x20-\x7E]/', $s)) {
            return '=?UTF-8?B?' . base64_encode($s) . '?=';
        }
        return $s;
    }

    private static function encodeHeaderName(string $s): string
    {
        if (preg_match('/[^\x20-\x7E]/', $s)) {
            return '=?UTF-8?B?' . base64_encode($s) . '?=';
        }
        return '"' . addcslashes($s, '"\\') . '"';
    }
}
