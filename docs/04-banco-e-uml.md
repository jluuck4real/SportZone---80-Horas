# Banco de dados e UML

## Modelo conceitual

Um usuário possui endereços e realiza pedidos. Produtos pertencem a categorias. Carrinhos contêm produtos em quantidades e tamanhos; o visitante é identificado por token e o cliente por usuário. Cada pedido preserva seus itens e endereço de entrega, tem um pagamento simulado e eventos de evolução de status. Cupons são regras consultadas por código; contatos podem vir de visitantes. Tentativas de login são registros técnicos separados dos dados comerciais.

Cardinalidades: usuário 1:N endereços, usuário 1:N pedidos, categoria 1:N produtos, carrinho N:M produtos resolvido em cart_items, pedido N:M produtos resolvido em order_items, pedido 1:1 pagamento e pedido 1:N eventos. Carrinho pode não ter usuário; usuário tem no máximo um carrinho persistido. Não há entidade de imagem separada porque o escopo utiliza uma imagem principal por produto.

## Modelo lógico

Os nomes correspondem exatamente ao SQL e ao PHP:

```text
users(id PK, email UQ, name, password_hash, role, created_at)
addresses(id PK, user_id FK users, recipient, cep, street, number, complement, district, city, state, created_at)
categories(id PK, name UQ, slug UQ, active)
products(id PK, category_id FK categories, name, description, brand, gender, price, sale_price, image, stock, sizes, is_new, active, created_at)
carts(id PK, token UQ, user_id FK users nullable, created_at); UQ filtrada user_id não nulo
cart_items(id PK, cart_id FK carts, product_id FK products, size, quantity); UQ(cart_id,product_id,size)
coupons(id PK, code UQ, percent_off, minimum, expires_at, active)
orders(id PK, user_id FK users, number UQ, checkout_key UQ, status, subtotal, discount, shipping, total, coupon_code, address_json, delivery_days, created_at)
order_items(id PK, order_id FK orders, product_id FK products, name, size, quantity, unit_price)
payments(id PK, order_id FK orders UQ, method, status, simulated, reference UQ, created_at)
order_events(id PK, order_id FK orders, actor_id FK users, status, note, created_at)
contact_messages(id PK, name, email, message, created_at)
login_attempts(id PK, fingerprint, occurred_at)
```

Preços usam DECIMAL(12,2), estoque/quantidade usam INT e status têm CHECK. `coupon_code` e `address_json` são snapshots históricos. No SportZone 3.0, product_variants é a origem do estoque por tamanho; sizes e products.stock são resumos sincronizados para o catálogo. Movimentações têm integridade stock_after=stock_before+delta. Imagens adicionais estão normalizadas em product_images. Campos financeiros preparatórios não habilitam cobranças externas.

Complementos do modelo lógico da versão 3.0:

```text
product_variants(id PK, product_id FK products, size, stock, active); UQ(product_id,size)
stock_movements(id PK, product_id FK products, variant_id FK product_variants, order_id FK orders nullable, actor_id FK users nullable, movement_type, delta, stock_before, stock_after, note, created_at)
product_images(id PK, product_id FK products, path, sort_order); UQ(product_id,path)
store_settings(id PK CHECK=1, notice, support_email, low_stock_threshold)
orders: acrescentados delivery_status, delivery_previous e stock_released
order_items: acrescentado variant_id FK product_variants
order_events: acrescentados previous_status e delivery_status
```

## Normalização e integridade

Usuários, categorias, produtos e relações são separados para evitar repetição de dados correntes. Itens do pedido guardam nome/preço/tamanho históricos propositalmente. FKs usam NO ACTION, evitando exclusão de histórico e múltiplos caminhos de cascata no SQL Server. A aplicação desativa produtos/categorias. DELETE é utilizado para linhas de carrinho e endereços próprios. Índices apoiam categoria/ativo, marca, histórico por usuário/data, status, endereços, itens/eventos de pedido e tentativas de login. Busca por `%nome%` não utiliza índice full-text; com o catálogo de 27 produtos não justifica infraestrutura extra.

## Diagramas entregues

| Diagrama | Fonte editável |
|---|---|
| Casos de uso UML | `uml/casos-de-uso.puml` (PlantUML) |
| Classes reais da implementação | `uml/classes.mmd` (Mermaid) |
| Atividade da compra | `uml/atividades.mmd` (Mermaid) |
| Sequência de checkout | `uml/sequencia-checkout.mmd` (Mermaid) |
| Entidade-relacionamento | `uml/entidade-relacionamento.mmd` (Mermaid) |

Para visualizar, abra as fontes em editor compatível com Mermaid/PlantUML. Os blocos são fontes locais e não exigem envio de dados privados a serviços online. O diagrama de classes descreve classes reais; arrays de DTO não aparecem como classes artificiais. O dicionário completo está em `dicionario-de-dados.md`, gerado a partir do esquema SQL.

## Scripts

1. `sql/01-database.sql`: cria o banco se ausente.
2. `sql/02-schema.sql`: cria tabelas, CHECKs, FKs, UNIQUE e índices em banco vazio.
3. `sql/03-seed.sql`: sete modalidades, 27 produtos representativos e SPORT10. Não cria usuários com senha padrão.
4. `sql/04-consultas-e-crud.sql`: consultas e CRUD com rollback.
5. `sql/05-permissoes-exemplo.sql`: papel de runtime sem db_owner/DDL.

Alternativa: depois de criar o banco, `php scripts/install.php` aplica esquema/seed usando PDO. Não executar o instalador novamente no mesmo banco; não existe reset destrutivo automático. Para testes, crie outro banco vazio. Credenciais jamais são inseridas no SQL versionado.
