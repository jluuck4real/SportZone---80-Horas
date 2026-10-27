# Dicionário de dados — SQL Server

Gerado a partir de `sql/02-schema.sql`. `id` é PK IDENTITY; datas são UTC. Valores monetários são DECIMAL(12,2). As restrições abaixo são as efetivamente declaradas.

## users

Contas, hash de senha e perfil de autorização.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | IDENTITY(1,1) PRIMARY KEY |
| name | NVARCHAR(120) | NOT NULL |
| email | NVARCHAR(180) | NOT NULL UNIQUE |
| password_hash | VARCHAR(255) | NOT NULL |
| role | VARCHAR(12) | NOT NULL DEFAULT 'customer' CHECK (role IN ('customer','admin')) |
| created_at | DATETIME2 | NOT NULL DEFAULT SYSUTCDATETIME() |

## addresses

Endereços do usuário; cada consulta exige proprietário.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | IDENTITY(1,1) PRIMARY KEY |
| user_id | INT | NOT NULL REFERENCES users(id) |
| recipient | NVARCHAR(120) | NOT NULL |
| cep | CHAR(8) | NOT NULL |
| street | NVARCHAR(180) | NOT NULL |
| number | NVARCHAR(20) | NOT NULL |
| complement | NVARCHAR(100) | NOT NULL DEFAULT '' |
| district | NVARCHAR(100) | NOT NULL |
| city | NVARCHAR(100) | NOT NULL |
| state | CHAR(2) | NOT NULL |
| created_at | DATETIME2 | NOT NULL DEFAULT SYSUTCDATETIME() |

## categories

Modalidades e status de publicação.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | IDENTITY(1,1) PRIMARY KEY |
| name | NVARCHAR(80) | NOT NULL UNIQUE |
| slug | VARCHAR(80) | NOT NULL UNIQUE |
| active | BIT | NOT NULL DEFAULT 1 CHECK (active IN (0,1)) |

## products

Catálogo central; stock é a soma sincronizada das variações ativas.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | IDENTITY(1,1) PRIMARY KEY |
| category_id | INT | NOT NULL REFERENCES categories(id) |
| name | NVARCHAR(160) | NOT NULL |
| description | NVARCHAR(3000) | NOT NULL |
| brand | NVARCHAR(80) | NOT NULL |
| gender | VARCHAR(20) | NOT NULL DEFAULT 'unissex' |
| price | DECIMAL(12,2) | NOT NULL CHECK (price > 0) |
| sale_price | DECIMAL(12,2) | NULL |
| image | NVARCHAR(300) | NOT NULL |
| stock | INT | NOT NULL CHECK (stock >= 0) |
| sizes | NVARCHAR(120) | NOT NULL |
| is_new | BIT | NOT NULL DEFAULT 0 CHECK (is_new IN (0,1)) |
| active | BIT | NOT NULL DEFAULT 1 CHECK (active IN (0,1)) |
| is_test | BIT | NOT NULL DEFAULT 0 CHECK (is_test IN (0,1)) |
| featured | BIT | NOT NULL DEFAULT 0 CHECK (featured IN (0,1)) |
| created_at | DATETIME2 | NOT NULL DEFAULT SYSUTCDATETIME() |

Restrições compostas: `CHECK (sale_price IS NULL OR (sale_price > 0 AND sale_price < price))`.

## product_variants

Estoque individual por produto e tamanho; quantidade não negativa.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | IDENTITY(1,1) PRIMARY KEY |
| product_id | INT | NOT NULL REFERENCES products(id) |
| size | NVARCHAR(20) | NOT NULL |
| stock | INT | NOT NULL DEFAULT 0 CHECK (stock >= 0) |
| active | BIT | NOT NULL DEFAULT 1 CHECK (active IN (0,1)) |

Restrições compostas: `UNIQUE(product_id,size)`.

## carts

Carrinho anônimo por token ou de usuário por identificação.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | IDENTITY(1,1) PRIMARY KEY |
| token | CHAR(64) | NOT NULL UNIQUE |
| user_id | INT | NULL REFERENCES users(id) |
| created_at | DATETIME2 | NOT NULL DEFAULT SYSUTCDATETIME() |

## cart_items

Seleção de produto/tamanho e quantidade, sem preço confiado ao cliente.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | IDENTITY(1,1) PRIMARY KEY |
| cart_id | INT | NOT NULL REFERENCES carts(id) |
| product_id | INT | NOT NULL REFERENCES products(id) |
| size | NVARCHAR(20) | NOT NULL |
| quantity | INT | NOT NULL CHECK (quantity BETWEEN 1 AND 99) |

Restrições compostas: `UNIQUE(cart_id,product_id,size)`.

## coupons

Desconto percentual por código, mínimo e validade UTC.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | IDENTITY(1,1) PRIMARY KEY |
| code | VARCHAR(30) | NOT NULL UNIQUE |
| percent_off | INT | NOT NULL CHECK (percent_off BETWEEN 1 AND 50) |
| minimum | DECIMAL(12,2) | NOT NULL DEFAULT 0 CHECK (minimum >= 0) |
| expires_at | DATETIME2 | NOT NULL |
| active | BIT | NOT NULL DEFAULT 1 CHECK (active IN (0,1)) |

## orders

Cabeçalho com totais, situação geral, etapa logística e snapshots históricos.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | IDENTITY(1,1) PRIMARY KEY |
| user_id | INT | NOT NULL REFERENCES users(id) |
| number | VARCHAR(40) | NOT NULL UNIQUE |
| checkout_key | CHAR(64) | NOT NULL UNIQUE |
| status | VARCHAR(30) | NOT NULL DEFAULT 'aguardando_pagamento' CHECK (status IN ('aguardando_pagamento','pagamento_aprovado','pagamento_recusado','revisao_pagamento','reembolsado','em_processamento','preparando_envio','enviado','saiu_para_entrega','problema_entrega','entregue','cancelado')) |
| delivery_status | VARCHAR(30) | NOT NULL DEFAULT 'pedido_realizado' CHECK (delivery_status IN ('pedido_realizado','em_separacao','preparando_envio','enviado','saiu_para_entrega','problema_entrega','entregue','cancelado')) |
| delivery_previous | VARCHAR(30) | NULL |
| stock_released | BIT | NOT NULL DEFAULT 0 |
| subtotal | DECIMAL(12,2) | NOT NULL CHECK (subtotal >= 0) |
| discount | DECIMAL(12,2) | NOT NULL CHECK (discount >= 0) |
| shipping | DECIMAL(12,2) | NOT NULL CHECK (shipping >= 0) |
| total | DECIMAL(12,2) | NOT NULL CHECK (total >= 0) |
| coupon_code | VARCHAR(30) | NULL |
| address_json | NVARCHAR(2000) | NOT NULL |
| delivery_days | VARCHAR(30) | NOT NULL |
| created_at | DATETIME2 | NOT NULL DEFAULT SYSUTCDATETIME() |

Restrições compostas: `CHECK (total = subtotal - discount + shipping)`.

## order_items

Itens históricos com preço congelado; variant_id é acrescentado pela migração ou pelo script SQL 06.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | IDENTITY(1,1) PRIMARY KEY |
| order_id | INT | NOT NULL REFERENCES orders(id) |
| product_id | INT | NOT NULL REFERENCES products(id) |
| variant_id | INT | NULL REFERENCES product_variants(id) |
| name | NVARCHAR(160) | NOT NULL |
| size | NVARCHAR(20) | NOT NULL |
| quantity | INT | NOT NULL CHECK (quantity BETWEEN 1 AND 99) |
| unit_price | DECIMAL(12,2) | NOT NULL CHECK (unit_price > 0) |

## payments

Somente pagamentos simulados ativos; campos externos reservados não habilitam cobranças.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | IDENTITY(1,1) PRIMARY KEY |
| order_id | INT | NOT NULL UNIQUE REFERENCES orders(id) |
| method | VARCHAR(10) | NOT NULL CHECK (method IN ('pix','cartao','boleto')) |
| status | VARCHAR(32) | NOT NULL DEFAULT 'pendente' CHECK (status IN ('pendente','aprovado_teste','cancelado_teste','not_created','creating','pending','in_process','authorized','approved','rejected','cancelled','refunded','charged_back','creation_unknown','creation_failed')) |
| simulated | BIT | NOT NULL DEFAULT 1 CHECK (simulated IN (0,1)) |
| provider | VARCHAR(20) | NOT NULL DEFAULT 'simulation' |
| environment | VARCHAR(12) | NOT NULL DEFAULT 'simulation' CHECK (environment IN ('simulation','sandbox','production')) |
| idempotency_key | VARCHAR(36) | NULL |
| remote_id | VARCHAR(40) | NULL |
| preference_id | VARCHAR(100) | NULL |
| checkout_url | NVARCHAR(2000) | NULL |
| qr_code | NVARCHAR | (MAX) NULL |
| qr_base64 | NVARCHAR | (MAX) NULL |
| expires_at | VARCHAR(40) | NULL |
| verified_at | VARCHAR(30) | NULL |
| sync_at | VARCHAR(30) | NULL |
| creation_started | VARCHAR(30) | NULL |
| request_hash | CHAR(64) | NULL |
| needs_review | BIT | NOT NULL DEFAULT 0 |
| status_detail | NVARCHAR(160) | NULL |
| reference | VARCHAR(80) | NOT NULL UNIQUE |
| created_at | DATETIME2 | NOT NULL DEFAULT SYSUTCDATETIME() |

## contact_messages

Mensagens recebidas pelo formulário, sem envio de e-mail.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | IDENTITY(1,1) PRIMARY KEY |
| name | NVARCHAR(120) | NOT NULL |
| email | NVARCHAR(180) | NOT NULL |
| message | NVARCHAR(3000) | NOT NULL |
| created_at | DATETIME2 | NOT NULL DEFAULT SYSUTCDATETIME() |

## order_events

Auditoria de situação, etapa logística, estado anterior, autor e observação.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | IDENTITY(1,1) PRIMARY KEY |
| order_id | INT | NOT NULL REFERENCES orders(id) |
| actor_id | INT | NOT NULL REFERENCES users(id) |
| status | VARCHAR(30) | NOT NULL |
| note | NVARCHAR(200) | NOT NULL |
| previous_status | VARCHAR(30) | NULL |
| delivery_status | VARCHAR(30) | NULL |
| created_at | DATETIME2 | NOT NULL DEFAULT SYSUTCDATETIME() |

## login_attempts

Limite de tentativas por hash do IP+e-mail; não armazena senha.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | IDENTITY(1,1) PRIMARY KEY |
| fingerprint | CHAR(64) | NOT NULL |
| occurred_at | DATETIME2 | NOT NULL DEFAULT SYSUTCDATETIME() |

## payment_transactions

Reserva histórica de análise de integração; não utilizada para cobranças nesta demonstração.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | IDENTITY(1,1) PRIMARY KEY |
| order_id | INT | NOT NULL REFERENCES orders(id) |
| remote_id | VARCHAR(40) | NOT NULL UNIQUE |
| status | VARCHAR(32) | NOT NULL |
| status_detail | NVARCHAR(160) | NOT NULL |
| amount | DECIMAL(12,2) | NOT NULL |
| refunded_amount | DECIMAL(12,2) | NOT NULL DEFAULT 0 |
| currency | CHAR(3) | NOT NULL |
| collector_id | VARCHAR(40) | NOT NULL |
| live_mode | BIT | NOT NULL |
| remote_updated | VARCHAR(40) | NOT NULL |
| verified_at | VARCHAR(30) | NOT NULL |

## webhook_events

Reserva de análise; não há webhook financeiro ativo.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | IDENTITY(1,1) PRIMARY KEY |
| fingerprint | CHAR(64) | NOT NULL UNIQUE |
| remote_id | VARCHAR(40) | NOT NULL |
| request_id | VARCHAR(150) | NOT NULL |
| state | VARCHAR(12) | NOT NULL DEFAULT 'pending' |
| attempts | INT | NOT NULL DEFAULT 0 |
| created_at | DATETIME2 | NOT NULL DEFAULT SYSUTCDATETIME() |
| processed_at | VARCHAR(30) | NULL |

## stock_movements

Entradas, saídas, edição, venda e cancelamento, com saldo anterior/posterior e responsável.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | IDENTITY(1,1) PRIMARY KEY |
| product_id | INT | NOT NULL REFERENCES products(id) |
| variant_id | INT | NOT NULL REFERENCES product_variants(id) |
| order_id | INT | NULL REFERENCES orders(id) |
| actor_id | INT | NULL REFERENCES users(id) |
| movement_type | VARCHAR(20) | NOT NULL CHECK (movement_type IN ('initial','adjustment','sale','cancellation','product_edit')) |
| delta | INT | NOT NULL |
| stock_before | INT | NOT NULL CHECK (stock_before >= 0) |
| stock_after | INT | NOT NULL CHECK (stock_after >= 0) |
| note | NVARCHAR(250) | NOT NULL |
| created_at | DATETIME2 | NOT NULL DEFAULT SYSUTCDATETIME() |

Restrições compostas: `CHECK (stock_after = stock_before + delta)`.

## product_images

Galeria adicional validada; até seis imagens pela regra de aplicação.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | IDENTITY(1,1) PRIMARY KEY |
| product_id | INT | NOT NULL REFERENCES products(id) |
| path | NVARCHAR(300) | NOT NULL |
| sort_order | INT | NOT NULL DEFAULT 0 |

Restrições compostas: `UNIQUE(product_id,path)`.

## store_settings

Preferências públicas da loja e limiar de estoque baixo; não contém credenciais.

| Campo | Tipo | Nulabilidade e regras SQL |
|---|---|---|
| id | INT | PRIMARY KEY CHECK (id=1) |
| notice | NVARCHAR(140) | NOT NULL DEFAULT '' |
| support_email | NVARCHAR(180) | NOT NULL DEFAULT '' |
| low_stock_threshold | INT | NOT NULL DEFAULT 5 CHECK (low_stock_threshold BETWEEN 1 AND 50) |

## Índices adicionais

```sql
CREATE UNIQUE INDEX UX_carts_user ON carts(user_id) WHERE user_id IS NOT NULL;
CREATE INDEX IX_transactions_order ON payment_transactions(order_id);
CREATE INDEX IX_webhook_state ON webhook_events(state,created_at);
CREATE INDEX IX_products_category ON products(category_id,active);
CREATE INDEX IX_products_brand ON products(brand);
CREATE INDEX IX_orders_user_date ON orders(user_id,created_at);
CREATE INDEX IX_orders_status ON orders(status);
CREATE INDEX IX_addresses_user ON addresses(user_id);
CREATE INDEX IX_order_items_order ON order_items(order_id);
CREATE INDEX IX_order_events_order ON order_events(order_id);
CREATE INDEX IX_login_attempts ON login_attempts(fingerprint,occurred_at);
CREATE INDEX IX_variants_product ON product_variants(product_id,active);
CREATE INDEX IX_stock_movements_variant ON stock_movements(variant_id,created_at);
CREATE INDEX IX_stock_movements_order ON stock_movements(order_id);
CREATE INDEX IX_delivery_status ON orders(delivery_status,created_at);
```
