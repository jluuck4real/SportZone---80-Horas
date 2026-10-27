-- SportZone 3.0: executar no banco existente, com backup e conta de instalação.
-- A aplicação deve estar sem operações de escrita durante a migração.
-- Use sql/02-schema.sql e 03-seed.sql para banco novo; depois execute este script.
SET XACT_ABORT ON;
BEGIN TRY
 BEGIN TRANSACTION;
 IF COL_LENGTH('orders','delivery_status') IS NULL
 BEGIN
  ALTER TABLE orders ADD delivery_status VARCHAR(30) NOT NULL DEFAULT 'pedido_realizado';
 END;
 IF COL_LENGTH('orders','delivery_previous') IS NULL
 BEGIN
  ALTER TABLE orders ADD delivery_previous VARCHAR(30) NULL;
 END;
 -- SQL dinâmico permite referenciar colunas recém-criadas na mesma execução.
 DECLARE @dropChecks NVARCHAR(MAX)=N'';
 SELECT @dropChecks=@dropChecks+N'ALTER TABLE orders DROP CONSTRAINT '+QUOTENAME(name)+N';'
 FROM sys.check_constraints WHERE parent_object_id=OBJECT_ID('orders') AND definition LIKE '%status%';
 IF @dropChecks<>N'' EXEC sp_executesql @dropChecks;
 ALTER TABLE orders ADD CONSTRAINT CK_orders_status_admin3 CHECK (status IN ('aguardando_pagamento','pagamento_aprovado','pagamento_recusado','revisao_pagamento','reembolsado','em_processamento','preparando_envio','enviado','saiu_para_entrega','problema_entrega','entregue','cancelado'));
 EXEC sp_executesql N'ALTER TABLE orders ADD CONSTRAINT CK_orders_delivery_admin3 CHECK (delivery_status IN (''pedido_realizado'',''em_separacao'',''preparando_envio'',''enviado'',''saiu_para_entrega'',''problema_entrega'',''entregue'',''cancelado''))';
 IF COL_LENGTH('order_events','previous_status') IS NULL ALTER TABLE order_events ADD previous_status VARCHAR(30) NULL;
 IF COL_LENGTH('order_events','delivery_status') IS NULL ALTER TABLE order_events ADD delivery_status VARCHAR(30) NULL;
 IF OBJECT_ID(N'dbo.product_variants',N'U') IS NULL
BEGIN
CREATE TABLE product_variants (
 id INT IDENTITY(1,1) PRIMARY KEY,
 product_id INT NOT NULL REFERENCES products(id),
 size NVARCHAR(20) NOT NULL,
 stock INT NOT NULL DEFAULT 0 CHECK (stock >= 0),
 active BIT NOT NULL DEFAULT 1 CHECK (active IN (0,1)),
 UNIQUE(product_id,size)
);
END;
IF OBJECT_ID(N'dbo.stock_movements',N'U') IS NULL
BEGIN
CREATE TABLE stock_movements (
 id INT IDENTITY(1,1) PRIMARY KEY,
 product_id INT NOT NULL REFERENCES products(id),
 variant_id INT NOT NULL REFERENCES product_variants(id),
 order_id INT NULL REFERENCES orders(id),
 actor_id INT NULL REFERENCES users(id),
 movement_type VARCHAR(20) NOT NULL CHECK (movement_type IN ('initial','adjustment','sale','cancellation','product_edit')),
 delta INT NOT NULL,
 stock_before INT NOT NULL CHECK (stock_before >= 0),
 stock_after INT NOT NULL CHECK (stock_after >= 0),
 note NVARCHAR(250) NOT NULL,
 created_at DATETIME2 NOT NULL DEFAULT SYSUTCDATETIME(),
 CHECK (stock_after = stock_before + delta)
);
END;
IF OBJECT_ID(N'dbo.product_images',N'U') IS NULL
BEGIN
CREATE TABLE product_images (
 id INT IDENTITY(1,1) PRIMARY KEY,
 product_id INT NOT NULL REFERENCES products(id),
 path NVARCHAR(300) NOT NULL,
 sort_order INT NOT NULL DEFAULT 0,
 UNIQUE(product_id,path)
);
END;
IF OBJECT_ID(N'dbo.store_settings',N'U') IS NULL
BEGIN
CREATE TABLE store_settings (
 id INT PRIMARY KEY CHECK (id=1),
 notice NVARCHAR(140) NOT NULL DEFAULT '',
 support_email NVARCHAR(180) NOT NULL DEFAULT '',
 low_stock_threshold INT NOT NULL DEFAULT 5 CHECK (low_stock_threshold BETWEEN 1 AND 50)
);
END;
 IF COL_LENGTH('order_items','variant_id') IS NULL ALTER TABLE order_items ADD variant_id INT NULL REFERENCES product_variants(id);
 EXEC sp_executesql N'UPDATE orders SET delivery_status=CASE status WHEN ''em_processamento'' THEN ''em_separacao'' WHEN ''enviado'' THEN ''enviado'' WHEN ''entregue'' THEN ''entregue'' WHEN ''cancelado'' THEN ''cancelado'' ELSE delivery_status END WHERE delivery_status=''pedido_realizado'' AND status IN (''em_processamento'',''enviado'',''entregue'',''cancelado'')';
 -- Divide apenas o total existente, sem criar unidades. A posição deriva da lista original.
 ;WITH source AS (
  SELECT p.id,p.stock,LTRIM(RTRIM(s.value)) AS size,
   ROW_NUMBER() OVER(PARTITION BY p.id ORDER BY CHARINDEX(','+s.value+',',','+p.sizes+',')) AS position,
   COUNT(*) OVER(PARTITION BY p.id) AS size_count
  FROM products p CROSS APPLY STRING_SPLIT(p.sizes,',') s
  WHERE LTRIM(RTRIM(s.value))<>'' AND NOT EXISTS(SELECT 1 FROM product_variants v WHERE v.product_id=p.id)
 )
 INSERT INTO product_variants(product_id,size,stock,active)
 SELECT id,size,stock/size_count+CASE WHEN position<=stock%size_count THEN 1 ELSE 0 END,1 FROM source;
 INSERT INTO stock_movements(product_id,variant_id,movement_type,delta,stock_before,stock_after,note)
 SELECT v.product_id,v.id,'initial',v.stock,0,v.stock,N'Migração do estoque agregado por tamanho; total original preservado.'
 FROM product_variants v WHERE NOT EXISTS(SELECT 1 FROM stock_movements m WHERE m.variant_id=v.id);
 INSERT INTO product_variants(product_id,size,stock,active)
 SELECT DISTINCT i.product_id,i.size,0,0 FROM order_items i WHERE NOT EXISTS(SELECT 1 FROM product_variants v WHERE v.product_id=i.product_id AND v.size=i.size);
 EXEC sp_executesql N'UPDATE i SET variant_id=v.id FROM order_items i JOIN product_variants v ON v.product_id=i.product_id AND v.size=i.size WHERE i.variant_id IS NULL';
 IF NOT EXISTS(SELECT 1 FROM store_settings WHERE id=1) INSERT INTO store_settings(id,notice,support_email,low_stock_threshold) VALUES (1,N'',N'',5);
 IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE name='IX_variants_product' AND object_id=OBJECT_ID('product_variants')) CREATE INDEX IX_variants_product ON product_variants(product_id,active);
 IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE name='IX_stock_movements_variant' AND object_id=OBJECT_ID('stock_movements')) CREATE INDEX IX_stock_movements_variant ON stock_movements(variant_id,created_at);
 IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE name='IX_stock_movements_order' AND object_id=OBJECT_ID('stock_movements')) CREATE INDEX IX_stock_movements_order ON stock_movements(order_id);
 IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE name='IX_delivery_status' AND object_id=OBJECT_ID('orders')) EXEC sp_executesql N'CREATE INDEX IX_delivery_status ON orders(delivery_status,created_at)';
 COMMIT TRANSACTION;
END TRY
BEGIN CATCH
 IF @@TRANCOUNT>0 ROLLBACK TRANSACTION;
 THROW;
END CATCH;
-- Verificação: zero linhas esperadas.
SELECT p.id,p.stock FROM products p WHERE p.stock<>(SELECT COALESCE(SUM(v.stock),0) FROM product_variants v WHERE v.product_id=p.id AND v.active=1);
SELECT id FROM product_variants WHERE stock<0;
