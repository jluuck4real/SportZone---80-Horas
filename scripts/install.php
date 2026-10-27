<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';
use SportZone\Core\Database;
if (PHP_SAPI !== 'cli') exit(1);
$pdo = Database::connection();
$schema = file_get_contents(ROOT . '/sql/02-schema.sql');
$schema = preg_replace('/^--.*$/m', '', $schema);
$seed = file_get_contents(ROOT . '/sql/03-seed.sql');
if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
    $schema = str_replace(['SYSUTCDATETIME()','NVARCHAR(MAX)'], ['CURRENT_TIMESTAMP','TEXT'], $schema);
    // SQLite stores DECIMAL using numeric affinity; compare cents to avoid binary-float CHECK noise.
    $schema = str_replace('CHECK (total = subtotal - discount + shipping)', 'CHECK (ROUND(total*100) = ROUND((subtotal-discount+shipping)*100))', $schema);
    $schema = str_replace('INT IDENTITY(1,1) PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $schema);
    $schema = preg_replace('/\bNVARCHAR\(\d+\)|\bDATETIME2\b/', 'TEXT', $schema);
    // SQLite does not use N-prefixed Unicode literals.
    $seed = str_replace("N'", "'", $seed);
    $pdo->exec($schema);
    $pdo->exec($seed);
} else {
    // PDO_SQLSRV accepts statements sequentially; CREATE DATABASE stays in separate script.
    foreach (explode(';', $schema) as $sql) if (trim($sql)) $pdo->exec($sql);
    // Seed is executed as individual lines to preserve semicolons inside product text.
    foreach (explode("\n", $seed) as $sql) if (trim($sql) && !str_starts_with(trim($sql),'--')) $pdo->exec($sql);
}
\SportZone\Core\SchemaUpgrade::run();
echo "Esquema e produtos instalados em " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . ".\n";
