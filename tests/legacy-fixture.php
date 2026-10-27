<?php
declare(strict_types=1);
require dirname(__DIR__).'/config/bootstrap.php';
if(PHP_SAPI!=='cli'||getenv('SPORTZONE_TEST_MODE')!=='1')exit(1);
$db=\SportZone\Core\Database::connection();
$schema=file_get_contents(ROOT.'/sql/02-schema.sql');$schema=preg_replace('/^--.*$/m','',$schema);
$schema=preg_replace('/CREATE TABLE (product_variants|stock_movements|product_images|store_settings) \([\s\S]*?\n\);/','',$schema);
$schema=preg_replace('/CREATE INDEX IX_(variants_product|stock_movements_variant|stock_movements_order|delivery_status)[^;]+;/','',$schema);
$schema=preg_replace('/^ (delivery_status|delivery_previous|previous_status) .*\n/m','',$schema);
$schema=preg_replace('/^ variant_id INT NULL REFERENCES product_variants\(id\),\n/m','',$schema);
$db->exec(\SportZone\Core\SchemaUpgrade::sqliteSql($schema));$db->exec(str_replace("N'","'",file_get_contents(ROOT.'/sql/03-seed.sql')));
$s=$db->prepare('INSERT INTO users(name,email,password_hash) VALUES (?,?,?)');$s->execute(['Cliente Legado','legado@test.local',password_hash('TesteSeguro123!',PASSWORD_DEFAULT)]);
$s=$db->prepare('INSERT INTO orders(user_id,number,checkout_key,status,subtotal,discount,shipping,total,address_json,delivery_days) VALUES (1,?,?,?,100,0,0,100,?,?)');$s->execute(['SZ-LEGADO-1',str_repeat('a',64),'enviado','{}','Demonstração']);
$db->exec("INSERT INTO order_items(order_id,product_id,name,size,quantity,unit_price) VALUES (1,1,'Produto histórico','P',2,50)");
$db->exec("INSERT INTO payments(order_id,method,status,simulated,reference) VALUES (1,'pix','aprovado_teste',1,'SIM-LEGADO')");
$db->exec("INSERT INTO order_events(order_id,actor_id,status,note) VALUES (1,1,'enviado','Pedido enviado antes da migração')");$db->exec('UPDATE products SET stock=18 WHERE id=1');
