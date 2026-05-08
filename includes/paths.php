<?php

function app_config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require dirname(__DIR__) . '/config/config.php';
    }
    return $cfg;
}

function detect_base_path(): string
{
    $docRoot = str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $appRoot = str_replace('\\', '/', dirname(__DIR__));
    if ($docRoot !== '' && str_starts_with($appRoot, $docRoot)) {
        $rel = trim(substr($appRoot, strlen($docRoot)), '/');
        return $rel === '' ? '/' : '/' . $rel . '/';
    }
    return '/';
}

function base_path(): string
{
    $raw = (string) (app_config()['app']['base_path'] ?? 'auto');
    if ($raw !== '' && strtolower($raw) !== 'auto') {
        $clean = '/' . trim($raw, '/');
        return $clean === '/' ? '/' : $clean . '/';
    }
    return detect_base_path();
}

function base_url(): string
{
    $raw = (string) (app_config()['app']['base_url'] ?? 'auto');
    if ($raw !== '' && strtolower($raw) !== 'auto') {
        return rtrim($raw, '/');
    }
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . $host . rtrim(base_path(), '/');
}