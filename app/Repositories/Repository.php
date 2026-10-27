<?php
declare(strict_types=1);
namespace SportZone\Repositories;
use PDO;
use SportZone\Core\Database;
abstract class Repository {
    protected PDO $db;
    public function __construct() { $this->db = Database::connection(); }
    public function query(string $sql, array $params = []): \PDOStatement {
        $statement = $this->db->prepare($sql);
        foreach (array_values($params) as $i=>$value) $statement->bindValue($i+1, $value, is_int($value) ? PDO::PARAM_INT : ($value === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
        $statement->execute(); return $statement;
    }
    public function insert(string $table, array $data): int {
        // Table and columns come exclusively from source code, never request parameters.
        $columns = implode(',', array_keys($data));
        $output = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlsrv' ? ' OUTPUT INSERTED.id' : '';
        $s = $this->query("INSERT INTO $table ($columns)$output VALUES (" . implode(',', array_fill(0,count($data),'?')) . ')', array_values($data));
        return $output ? (int)$s->fetchColumn() : (int)$this->db->lastInsertId();
    }
}
