<?php
declare(strict_types=1);
require dirname(__DIR__).'/config/bootstrap.php';
if(PHP_SAPI!=='cli' || getenv('SPORTZONE_TEST_MODE')!=='1')exit(1);
$db=\SportZone\Core\Database::connection();$sqlsrv=$db->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlsrv';
switch($argv[1]??''){
 case 'expire-coupon':$db->exec("UPDATE coupons SET expires_at='2000-01-01T00:00:00' WHERE code='SPORT10'");break;
 case 'restore-coupon':$db->exec("UPDATE coupons SET expires_at='2099-12-31T23:59:59' WHERE code='SPORT10'");break;
 case 'state':echo json_encode(['orders'=>(int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn(),'stock'=>(int)$db->query('SELECT SUM(stock) FROM products')->fetchColumn()]);break;
 case 'fail-payments':$db->exec($sqlsrv?"CREATE TRIGGER test_fail_payments ON payments INSTEAD OF INSERT AS BEGIN THROW 51000, 'Forced test failure', 1; END":"CREATE TRIGGER test_fail_payments BEFORE INSERT ON payments BEGIN SELECT RAISE(ABORT, 'Forced test failure'); END");break;
 case 'restore-payments':$db->exec('DROP TRIGGER test_fail_payments');break;
 case 'zero-stock':$db->exec('UPDATE product_variants SET stock=0 WHERE product_id IN (SELECT product_id FROM cart_items)');$db->exec('UPDATE products SET stock=0 WHERE id IN (SELECT product_id FROM cart_items)');break;
 case 'restore-stock':$db->exec('UPDATE product_variants SET stock=COALESCE((SELECT m.stock_after FROM stock_movements m WHERE m.id=(SELECT MAX(m2.id) FROM stock_movements m2 WHERE m2.variant_id=product_variants.id)),0) WHERE product_id IN (SELECT product_id FROM cart_items)');$db->exec('UPDATE products SET stock=(SELECT COALESCE(SUM(v.stock),0) FROM product_variants v WHERE v.product_id=products.id AND v.active=1) WHERE id IN (SELECT product_id FROM cart_items)');break;
 default:exit(1);
}
