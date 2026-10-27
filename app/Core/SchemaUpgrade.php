<?php
declare(strict_types=1);
namespace SportZone\Core;
use PDO;
final class SchemaUpgrade {
    public static function run(): void {
        $db=Database::connection();$sqlite=$db->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite';
        $columns=fn($table)=>$sqlite?array_column($db->query("PRAGMA table_info($table)")->fetchAll(),'name'):array_column($db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='$table'")->fetchAll(),'COLUMN_NAME');
        if(!in_array('is_test',$columns('products'),true))$db->exec('ALTER TABLE products ADD is_test '.($sqlite?'INTEGER':'BIT').' NOT NULL DEFAULT 0');
        if(!in_array('featured',$columns('products'),true))$db->exec('ALTER TABLE products ADD featured '.($sqlite?'INTEGER':'BIT').' NOT NULL DEFAULT 0');
        $schema=file_get_contents(ROOT.'/sql/02-schema.sql');
        preg_match('/CREATE TABLE orders \([\s\S]*?\n\);/',$schema,$orderDef);
        preg_match('/CREATE TABLE payments \([\s\S]*?\n\);/',$schema,$paymentDef);
        if(!in_array('provider',$columns('payments'),true)){
            if($sqlite){
                $db->exec('PRAGMA foreign_keys=OFF');
                try{Database::transaction(function()use($db,$columns,$orderDef,$paymentDef){
                    foreach(['orders'=>$orderDef[0],'payments'=>$paymentDef[0]] as $table=>$definition){
                        $old=$columns($table);$sql=self::sqliteSql(str_replace("CREATE TABLE $table (","CREATE TABLE {$table}_v3 (",$definition));$db->exec($sql);
                        $fields=implode(',',$old);$db->exec("INSERT INTO {$table}_v3 ($fields) SELECT $fields FROM $table");
                        $db->exec("DROP TABLE $table");$db->exec("ALTER TABLE {$table}_v3 RENAME TO $table");
                    }
                });}finally{$db->exec('PRAGMA foreign_keys=ON');}
                if($db->query('PRAGMA foreign_key_check')->fetch())throw new \RuntimeException('Falha na verificação de integridade após migração.');
            }else{
                foreach($db->query("SELECT name,OBJECT_NAME(parent_object_id) AS tbl FROM sys.check_constraints WHERE parent_object_id IN (OBJECT_ID('orders'),OBJECT_ID('payments')) AND (definition LIKE '%status%' OR definition LIKE '%simulated%')")->fetchAll() as $c)$db->exec('ALTER TABLE ['.$c['tbl'].'] DROP CONSTRAINT ['.str_replace(']',']]',$c['name']).']');
                $db->exec('ALTER TABLE orders ADD stock_released BIT NOT NULL DEFAULT 0');
                $db->exec('ALTER TABLE payments ALTER COLUMN status VARCHAR(32) NOT NULL');
                foreach(explode("\n",$paymentDef[0]) as $line){
                    if(preg_match('/^\s*(provider|environment|idempotency_key|remote_id|preference_id|checkout_url|qr_code|qr_base64|expires_at|verified_at|sync_at|creation_started|request_hash|needs_review|status_detail)\s+(.+?),?$/',$line,$m))$db->exec('ALTER TABLE payments ADD '.$m[1].' '.rtrim($m[2],','));
                }
                $db->exec("ALTER TABLE orders ADD CONSTRAINT CK_orders_status_v3 CHECK (status IN ('aguardando_pagamento','pagamento_aprovado','pagamento_recusado','revisao_pagamento','reembolsado','em_processamento','enviado','entregue','cancelado'))");
                $db->exec("ALTER TABLE payments ADD CONSTRAINT CK_payments_status_v3 CHECK (status IN ('pendente','aprovado_teste','cancelado_teste','not_created','creating','pending','in_process','authorized','approved','rejected','cancelled','refunded','charged_back','creation_unknown','creation_failed'))");
                $db->exec('ALTER TABLE payments ADD CONSTRAINT CK_payments_simulated_v3 CHECK (simulated IN (0,1))');
            }
            $db->exec("UPDATE orders SET stock_released=1 WHERE status='cancelado'");
            // Rebuilt SQLite tables lose their indexes; recreate only the order indexes.
            if($sqlite){$db->exec('CREATE INDEX IF NOT EXISTS IX_orders_user_date ON orders(user_id,created_at)');$db->exec('CREATE INDEX IF NOT EXISTS IX_orders_status ON orders(status)');}
        }
        foreach(['payment_transactions','webhook_events'] as $table){
            $exists=$sqlite?$db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'")->fetchColumn():$db->query("SELECT name FROM sys.tables WHERE name='$table'")->fetchColumn();
            if(!$exists){preg_match("/CREATE TABLE $table \\([\\s\\S]*?\\n\\);/",$schema,$def);$db->exec($sqlite?self::sqliteSql($def[0]):$def[0]);$db->exec($table==='payment_transactions'?'CREATE INDEX IX_transactions_order ON payment_transactions(order_id)':'CREATE INDEX IX_webhook_state ON webhook_events(state,created_at)');}
        }
        if(!in_array('request_hash',$columns('payments'),true))$db->exec('ALTER TABLE payments ADD request_hash CHAR(64) NULL');
        self::testProducts($db);
        AdminUpgrade::run();
    }
    public static function sqliteSql(string $sql): string {
        $sql=str_replace(['SYSUTCDATETIME()','INT IDENTITY(1,1) PRIMARY KEY','NVARCHAR(MAX)','CHECK (total = subtotal - discount + shipping)'],['CURRENT_TIMESTAMP','INTEGER PRIMARY KEY AUTOINCREMENT','TEXT','CHECK (ROUND(total*100)=ROUND((subtotal-discount+shipping)*100))'],$sql);
        return preg_replace('/\bNVARCHAR\(\d+\)|\bDATETIME2\b/','TEXT',$sql);
    }
    private static function testProducts(PDO $db): void {
        $s=$db->prepare('SELECT id FROM categories WHERE slug=?');$s->execute(['testes-pagamento']);$cat=$s->fetchColumn();$initializeFeatured=!$cat;
        if(!$cat){$s=$db->prepare("INSERT INTO categories(name,slug) VALUES (?,?)");$s->execute(['Testes de pagamento','testes-pagamento']);$s=$db->prepare('SELECT id FROM categories WHERE slug=?');$s->execute(['testes-pagamento']);$cat=$s->fetchColumn();}
        foreach([1,2] as $amount){$name='Produto de teste SportZone R$ '.$amount;$s=$db->prepare('SELECT id FROM products WHERE is_test=1 AND name=?');$s->execute([$name]);if($s->fetchColumn())continue;
            $s=$db->prepare('INSERT INTO products(category_id,name,description,brand,gender,price,image,stock,sizes,is_test,featured) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $s->execute([$cat,$name,'Item de demonstração do checkout SportZone, sem entrega física e sem cobrança real. Use este produto para explorar o carrinho, o pedido e a aprovação simulada.','SportZone','unissex',number_format($amount,2,'.',''),'assets/images/teste.svg',100,'Único',1,0]);
        }
        if($initializeFeatured){$s=$db->prepare('UPDATE products SET featured=1 WHERE name IN (?,?,?,?,?)');$s->execute(['Nike Sabrina 3 What The','Jersey Atlanta Hawks','Camiseta Seleção Brasileira','Nike Air Jordan 1 x Fly High','Nike Dunk SB Red Olive']);}
        $s=$db->prepare('UPDATE products SET description=? WHERE is_test=1 AND description=?');$s->execute(['Item de demonstração do checkout SportZone, sem entrega física e sem cobrança real. Use este produto para explorar o carrinho, o pedido e a aprovação simulada.','Item de teste de pagamento SportZone, sem entrega física. Em produção, a confirmação explícita pode gerar cobrança real. Utilize apenas para validar o fluxo e a confirmação pela API.']);
    }
}
