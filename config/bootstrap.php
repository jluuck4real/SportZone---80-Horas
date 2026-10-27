<?php
declare(strict_types=1);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
define('ROOT', dirname(__DIR__));
spl_autoload_register(function (string $class): void {
    $file = ROOT . '/' . str_replace('SportZone\\', 'app/', $class);
    $file = str_replace('\\', '/', $file) . '.php';
    if (is_file($file)) require $file;
});
if (is_file(ROOT . '/.env')) {
    foreach (file(ROOT . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        if (getenv(trim($key)) === false) putenv(trim($key) . '=' . trim($value, " \t\n\r\0\x0B\"'"));
    }
}
date_default_timezone_set('America/Sao_Paulo');
