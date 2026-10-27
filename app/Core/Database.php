<?php
declare(strict_types=1);
namespace SportZone\Core;
use PDO;
use RuntimeException;
final class Database {
    private static ?PDO $pdo = null;
    public static function connection(): PDO {
        if (self::$pdo) return self::$pdo;
        $driver = getenv('DB_DRIVER') ?: 'sqlsrv';
        if ($driver === 'sqlite') {
            if (getenv('APP_ENV') === 'production') throw new RuntimeException('SQLite permitido apenas para testes locais.');
            $path = getenv('DB_SQLITE_PATH') ?: 'storage/sportzone.sqlite';
            if (!preg_match('~^(?:[A-Za-z]:|/)~', $path)) $path = ROOT . '/' . $path;
            self::$pdo = new PDO('sqlite:' . $path);
            self::$pdo->exec('PRAGMA foreign_keys=ON');
            self::$pdo->exec('PRAGMA busy_timeout=5000');
        } elseif ($driver === 'sqlsrv') {
            if (!in_array('sqlsrv', PDO::getAvailableDrivers(), true)) throw new RuntimeException('Instale PDO_SQLSRV e ODBC Driver 18 para SQL Server.');
            $dsn = 'sqlsrv:Server=' . (getenv('DB_SERVER') ?: 'localhost,1433') . ';Database=' . (getenv('DB_DATABASE') ?: 'SportZone') . ';Encrypt=' . (getenv('DB_ENCRYPT') ?: 'yes') . ';TrustServerCertificate=' . (getenv('DB_TRUST_CERTIFICATE') ?: 'no');
            self::$pdo = new PDO($dsn, getenv('DB_USERNAME') ?: null, getenv('DB_PASSWORD') ?: null);
            self::$pdo->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
        } else throw new RuntimeException('Driver não suportado.');
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return self::$pdo;
    }
    public static function lock(string $table): string {
        return self::connection()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlsrv' ? "$table WITH (UPDLOCK, HOLDLOCK)" : $table;
    }
    public static function transaction(callable $callback): mixed {
        $pdo = self::connection();
        $pdo->beginTransaction();
        try { $value = $callback(); $pdo->commit(); return $value; }
        catch (\Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
}
