<?php
declare(strict_types=1);
namespace SportZone\Core;
final class Http {
    public static function json(mixed $data, int $status = 200): never {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); exit;
    }
    public static function fail(string $message, int $status = 422): never { self::json(['error' => $message], $status); }
    public static function input(): array {
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data')) return $_POST;
        try { $data = json_decode(file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { self::fail('JSON inválido.', 400); }
        if (!is_array($data)) self::fail('Requisição inválida.', 400);
        return $data;
    }
}
