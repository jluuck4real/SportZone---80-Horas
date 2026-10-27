<?php
declare(strict_types=1);
namespace SportZone\Core;
use PDO;
final class AdminUpgrade {
    public static function run(): void {
        $db=Database::connection();$sqlite=$db->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite';
        $columns=fn($t)=>$sqlite?array_column($db->query("PRAGMA table_info($t)")->fetchAll(),'name'):array_column($db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='$t'")->fetchAll(),'COLUMN_NAME');
        $schema=file_get_contents(ROOT.'/sql/02-schema.sql');
        if(!in_array('delivery_status',$columns('orders'),true)){
            if($sqlite){
                preg_match('/CREATE TABLE orders \([\s\S]*?\n\);/',$schema,$definition);$db->exec('PRAGMA foreign_keys=OFF');
                try{Database::transaction(function()use($db,$columns,$definition){$fields=implode(',',$columns('orders'));$db->exec(SchemaUpgrade::sqliteSql(str_replace('CREATE TABLE orders (','CREATE TABLE orders_admin_v3 (',$definition[0])));$db->exec("INSERT INTO orders_admin_v3 ($fields) SELECT $fields FROM orders");$db->exec('DROP TABLE orders');$db->exec('ALTER TABLE orders_admin_v3 RENAME TO orders');});}
                finally{$db->exec('PRAGMA foreign_keys=ON');}
                $db->exec('CREATE INDEX IF NOT EXISTS IX_orders_user_date ON orders(user_id,created_at)');$db->exec('CREATE INDEX IF NOT EXISTS IX_orders_status ON orders(status)');
            }else{
                Database::transaction(function()use($db){
                    foreach($db->query("SELECT name FROM sys.check_constraints WHERE parent_object_id=OBJECT_ID('orders') AND definition LIKE '%status%'")->fetchAll() as $c)$db->exec('ALTER TABLE orders DROP CONSTRAINT ['.str_replace(']',']]',$c['name']).']');
                    $db->exec("ALTER TABLE orders ADD delivery_status VARCHAR(30) NOT NULL DEFAULT 'pedido_realizado'");$db->exec('ALTER TABLE orders ADD delivery_previous VARCHAR(30) NULL');
                    $db->exec("ALTER TABLE orders ADD CONSTRAINT CK_orders_status_admin_v3 CHECK (status IN ('aguardando_pagamento','pagamento_aprovado','pagamento_recusado','revisao_pagamento','reembolsado','em_processamento','preparando_envio','enviado','saiu_para_entrega','problema_entrega','entregue','cancelado'))");
                    $db->exec("ALTER TABLE orders ADD CONSTRAINT CK_orders_delivery_v3 CHECK (delivery_status IN ('pedido_realizado','em_separacao','preparando_envio','enviado','saiu_para_entrega','problema_entrega','entregue','cancelado'))");
                });
            }
            $db->exec("UPDATE orders SET delivery_status=CASE status WHEN 'em_processamento' THEN 'em_separacao' WHEN 'enviado' THEN 'enviado' WHEN 'entregue' THEN 'entregue' WHEN 'cancelado' THEN 'cancelado' ELSE 'pedido_realizado' END");
        }
        foreach(['previous_status','delivery_status'] as $c)if(!in_array($c,$columns('order_events'),true))$db->exec("ALTER TABLE order_events ADD $c VARCHAR(30) NULL");
        $db->exec("UPDATE orders SET delivery_status=CASE status WHEN 'em_processamento' THEN 'em_separacao' WHEN 'enviado' THEN 'enviado' WHEN 'entregue' THEN 'entregue' WHEN 'cancelado' THEN 'cancelado' ELSE delivery_status END WHERE delivery_status='pedido_realizado' AND status IN ('em_processamento','enviado','entregue','cancelado')");
        foreach(['product_variants','stock_movements','product_images','store_settings'] as $table){
            $exists=$sqlite?$db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'")->fetchColumn():$db->query("SELECT name FROM sys.tables WHERE name='$table'")->fetchColumn();
            if(!$exists){preg_match("/CREATE TABLE $table \\([\\s\\S]*?\\n\\);/",$schema,$definition);$db->exec($sqlite?SchemaUpgrade::sqliteSql($definition[0]):$definition[0]);}
        }
        foreach(['IX_variants_product'=>'CREATE INDEX IX_variants_product ON product_variants(product_id,active)','IX_stock_movements_variant'=>'CREATE INDEX IX_stock_movements_variant ON stock_movements(variant_id,created_at)','IX_stock_movements_order'=>'CREATE INDEX IX_stock_movements_order ON stock_movements(order_id)','IX_delivery_status'=>'CREATE INDEX IX_delivery_status ON orders(delivery_status,created_at)'] as $name=>$sql){
            $exists=$sqlite?$db->query("SELECT name FROM sqlite_master WHERE type='index' AND name='$name'")->fetchColumn():$db->query("SELECT name FROM sys.indexes WHERE name='$name'")->fetchColumn();if(!$exists)$db->exec($sql);
        }
        if(!in_array('variant_id',$columns('order_items'),true))$db->exec('ALTER TABLE order_items ADD variant_id INT NULL REFERENCES product_variants(id)');
        Database::transaction(function()use($db){
            $missing=$db->query('SELECT p.id,p.stock,p.sizes FROM products p WHERE NOT EXISTS(SELECT 1 FROM product_variants v WHERE v.product_id=p.id) ORDER BY p.id')->fetchAll();
            foreach($missing as $p){$sizes=array_values(array_unique(array_filter(array_map('trim',explode(',',$p['sizes'])))));if(!$sizes)$sizes=['Único'];$base=intdiv((int)$p['stock'],count($sizes));$extra=(int)$p['stock']%count($sizes);
                foreach($sizes as $i=>$size){$stock=$base+($i<$extra?1:0);$s=$db->prepare('INSERT INTO product_variants(product_id,size,stock) VALUES (?,?,?)');$s->execute([$p['id'],$size,$stock]);$s=$db->prepare('SELECT id FROM product_variants WHERE product_id=? AND size=?');$s->execute([$p['id'],$size]);$variant=$s->fetchColumn();$s=$db->prepare("INSERT INTO stock_movements(product_id,variant_id,movement_type,delta,stock_before,stock_after,note) VALUES (?,?,'initial',?,0,?,?)");$s->execute([$p['id'],$variant,$stock,$stock,'Migração do estoque agregado por tamanho; total original preservado.']);}
            }
            // Preserve legacy purchased sizes even if they no longer occur in the catalog.
            foreach($db->query('SELECT DISTINCT product_id,size FROM order_items')->fetchAll() as $i){$s=$db->prepare('SELECT id FROM product_variants WHERE product_id=? AND size=?');$s->execute([$i['product_id'],$i['size']]);if(!$s->fetchColumn()){$s=$db->prepare('INSERT INTO product_variants(product_id,size,stock,active) VALUES (?,?,0,0)');$s->execute([$i['product_id'],$i['size']]);}}
            $db->exec('UPDATE order_items SET variant_id=(SELECT v.id FROM product_variants v WHERE v.product_id=order_items.product_id AND v.size=order_items.size) WHERE variant_id IS NULL');
            if(!$db->query('SELECT id FROM store_settings WHERE id=1')->fetchColumn())$db->exec("INSERT INTO store_settings(id,notice,support_email,low_stock_threshold) VALUES (1,'','',5)");
        });
        if($sqlite && $db->query('PRAGMA foreign_key_check')->fetch())throw new \RuntimeException('Integridade referencial inválida após migração administrativa.');
    }
}
