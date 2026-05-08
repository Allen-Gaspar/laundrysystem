<?php

class Database
{
    private static ?PDO $pdo = null;

    private static bool $bookingTimeColumnsEnsured = false;

    private static bool $passwordResetColumnsEnsured = false;

    /**
     * Adds started_at / completed_at to transactions if missing (avoids manual SQL on upgrades).
     */
    public static function ensureBookingTimeColumns(): void
    {
        if (self::$bookingTimeColumnsEnsured) {
            return;
        }
        self::$bookingTimeColumnsEnsured = true;
        try {
            $pdo = self::getConnection();
            $dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
            if ($dbName === '') {
                return;
            }
            $chk = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl AND COLUMN_NAME = :col'
            );
            $chk->execute(['db' => $dbName, 'tbl' => 'transactions', 'col' => 'started_at']);
            if ((int) $chk->fetchColumn() === 0) {
                $pdo->exec('ALTER TABLE transactions ADD COLUMN started_at DATETIME NULL AFTER notes');
            }
            $chk->execute(['db' => $dbName, 'tbl' => 'transactions', 'col' => 'completed_at']);
            if ((int) $chk->fetchColumn() === 0) {
                $pdo->exec('ALTER TABLE transactions ADD COLUMN completed_at DATETIME NULL AFTER started_at');
            }
        } catch (Throwable $e) {
            // Run sql/migration_2026_04_20_booking_times.sql manually if this fails.
        }
    }

    public static function ensurePasswordResetColumns(): void
    {
        if (self::$passwordResetColumnsEnsured) {
            return;
        }
        self::$passwordResetColumnsEnsured = true;
        try {
            $pdo = self::getConnection();
            $dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
            if ($dbName === '') {
                return;
            }
            $chk = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl AND COLUMN_NAME = :col'
            );
            $chk->execute(['db' => $dbName, 'tbl' => 'users', 'col' => 'password_reset_token']);
            if ((int) $chk->fetchColumn() === 0) {
                $pdo->exec('ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) NULL DEFAULT NULL AFTER password_hash');
            }
            $chk->execute(['db' => $dbName, 'tbl' => 'users', 'col' => 'password_reset_expires']);
            if ((int) $chk->fetchColumn() === 0) {
                $pdo->exec('ALTER TABLE users ADD COLUMN password_reset_expires DATETIME NULL DEFAULT NULL AFTER password_reset_token');
            }
        } catch (Throwable $e) {
            // Run sql/migration_password_reset.sql manually if this fails.
        }
    }

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $cfg = require dirname(__DIR__) . '/config/config.php';
            $db = $cfg['db'];
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $db['host'],
                $db['name'],
                $db['charset']
            );
            self::$pdo = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$pdo;
    }
}
