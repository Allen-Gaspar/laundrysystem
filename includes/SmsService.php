<?php
class SmsService {
    private $logFile;

    public function __construct() {
        $this->logFile = 'storage/sms_log.txt';
        
        if (!is_dir('storage')) {
            mkdir('storage', 0755, true);
        }
    }

    public function send($phone, $message) {
        $date = date('Y-m-d H:i:s');
        $line = "{$date} | {$phone} | {$message}" . PHP_EOL;

        return file_put_contents($this->logFile, $line, FILE_APPEND);
    }
}