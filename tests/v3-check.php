<?php
declare(strict_types=1);
require dirname(__DIR__).'/config/bootstrap.php';
if(PHP_SAPI!=='cli'||getenv('SPORTZONE_TEST_MODE')!=='1')exit(1);
$db=\SportZone\Core\Database::connection();$count=fn($sql)=>(int)$db->query($sql)->fetchColumn();
echo json_encode(['users'=>$count('SELECT COUNT(*) FROM users'),'orders'=>$count('SELECT COUNT(*) FROM orders'),'stock'=>$count('SELECT SUM(stock) FROM products'),'movements'=>$count('SELECT COUNT(*) FROM stock_movements'),'mismatches'=>$count('SELECT COUNT(*) FROM products p WHERE p.stock<>(SELECT COALESCE(SUM(v.stock),0) FROM product_variants v WHERE v.product_id=p.id AND v.active=1)'),'negative'=>$count('SELECT COUNT(*) FROM product_variants WHERE stock<0'),'foreign_keys'=>count($db->query('PRAGMA foreign_key_check')->fetchAll()),'first_delivery'=>$db->query('SELECT delivery_status FROM orders ORDER BY id LIMIT 1')->fetchColumn(),'linked_items'=>$count('SELECT COUNT(*) FROM order_items WHERE variant_id IS NOT NULL')]);
