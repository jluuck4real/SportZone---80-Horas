<?php
declare(strict_types=1);
namespace SportZone\Core;
final class Security {
    public static function start(): void {
        ini_set('session.use_strict_mode', '1');
        session_name('sportzone_session');
        session_set_cookie_params(['lifetime'=>0, 'path'=>'/', 'secure'=>getenv('SESSION_SECURE') === '1', 'httponly'=>true, 'samesite'=>'Lax']);
        session_start();
        if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 7200) {
            $_SESSION = []; session_regenerate_id(true);
        }
        $_SESSION['last_activity'] = time();
        $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
        $_SESSION['cart_token'] ??= bin2hex(random_bytes(32));
    }
    public static function csrf(): void {
        if (!hash_equals($_SESSION['csrf'], $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) Http::fail('Sessão de segurança inválida. Recarregue a página.', 419);
    }
    public static function user(): array {
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) Http::fail('Entre na sua conta para continuar.', 401);
        $user = (new \SportZone\Repositories\UsuarioRepository())->publicById((int)$id);
        if (!$user) Http::fail('Sessão inválida.', 401);
        return $user;
    }
    public static function admin(): array {
        $user = self::user();
        if ($user['role'] !== 'admin') Http::fail('Acesso exclusivo da administração.', 403);
        return $user;
    }
}
