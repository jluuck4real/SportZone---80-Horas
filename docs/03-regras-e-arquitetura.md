# Regras de negócio e arquitetura

## Regras efetivas

| ID | Regra |
|---|---|
| RN01 | Cada linha tem quantidade inteira de 1 a 99 e tamanho ativo do cadastro. O saldo é individual em product_variants; products.stock é a soma sincronizada. |
| RN02 | Cada tamanho deve ter saldo suficiente para sua própria quantidade. A soma também respeita o total do produto. Carrinho não reserva estoque. |
| RN03 | Produto/categoria inativos não podem ser comprados; produto esgotado continua consultável sem permitir adição. |
| RN04 | O preço unitário é sale_price quando presente, caso contrário price. Valores finais são calculados em centavos inteiros e gravados em DECIMAL(12,2). |
| RN05 | SPORT10 concede 10% a partir de R$ 100 de subtotal; cupom precisa estar ativo e não expirado. Não acumula outro cupom. Arredondamento comercial: meio centavo para cima. Remover o texto do cupom e recalcular o retira. |
| RN06 | Frete simulado: CEP com oito dígitos, opcionalmente hífen. Rejeita todos os dígitos repetidos. Sem consulta à existência geográfica do CEP. |
| RN07 | Primeiro dígito 0–3: R$ 14,90 e 3–5 dias úteis; 4–6: R$ 24,90 e 5–8 dias; 7–9: R$ 34,90 e 8–12 dias. Valor líquido após cupom ≥ R$ 299 dá frete grátis mantendo prazo. |
| RN08 | Total = subtotal − desconto + frete. A revisão enviada pelo navegador é somente uma confirmação: o servidor recalcula e rejeita mudança de total. |
| RN09 | Checkout exige login, endereço completo/UF válida, carrinho não vazio e PIX/cartão/boleto. Não coleta dados de cartão. |
| RN10 | Pedido nasce aguardando pagamento e payment.status=pendente. Aprovação é somente simulação explícita, registrada em eventos. Não gera código pagável. |
| RN11 | Criação, itens, pagamento, evento, baixa de estoque e limpeza de carrinho fazem parte da mesma transação. Falha desfaz a operação. |
| RN12 | Mesma chave de checkout devolve o mesmo pedido; uma chave única em banco impede duplicação. Novo checkout recebe nova chave. |
| RN13 | Visitante pode usar carrinho; o token aleatório está na sessão do servidor, não em localStorage. Após login, mescla com carrinho salvo, ajustando indisponibilidades com aviso. Após logout, carrinho do usuário não aparece ao visitante. |
| RN14 | Cliente consulta/edita apenas dados próprios; papel admin é verificado no banco a cada requisição administrativa. Cadastro público nunca aceita o papel enviado pelo cliente. |
| RN15 | Cliente cancela somente enquanto aguarda pagamento; administrador cancela também aprovado/em separação/preparando envio. Cada tamanho recebe suas unidades de volta uma única vez. Depois do envio, cancelamento é recusado. |
| RN16 | Fluxo normal: aguardando → pagamento confirmado → em separação → preparando envio → enviado → saiu para entrega → entregue. Problema na entrega exige motivo e retoma somente a etapa anterior. Estados finais não regridem. |
| RN17 | Exclusão de produtos é lógica: preserva referências e histórico. Nome, preço, tamanho e endereço são cópias históricas do pedido. |
| RN18 | E-mail único; senha 10–72 bytes no cadastro e confirmação obrigatória. Login incorreto retorna mensagem genérica; cinco falhas por IP+e-mail em 15 minutos bloqueiam novas tentativas temporariamente. |
| RN19 | Mensagem de contato de 10–3000 caracteres é persistida; não há envio de e-mail. Intervalo mínimo de 30 segundos entre mensagens por sessão. |
| RN20 | Upload admin: JPEG/PNG/WebP, até 2 MB e até 4096px por dimensão; MIME real e dimensões validados; nome aleatório; SVG de usuário e executáveis rejeitados. |

## Organização

`*.html`, `style.css` e `site.js` são apresentação. Mantêm os nomes de todas as páginas originais. JavaScript usa a API HTTP de mesma origem, sem persistência em navegador. Os métodos de escape tratam nomes/descrições/endereços/mensagens como texto antes de compor HTML.

`index.php` é o front controller da API; `ApiController` seleciona os casos de uso, valida CSRF e exige papel. `Security`, `Http`, `Validation` e `Database` centralizam infraestrutura. `Usuario`, `Produto` e `Pedido` encapsulam verificação de credenciais, disponibilidade/tamanhos e transições. Repositórios concentram consultas; serviços aplicam regras e transações. As linhas e snapshots de carrinho/pedido são arrays de dados, sem criar classes vazias apenas para contar classes.

`Database::transaction` faz begin/commit/rollback. No SQL Server, produtos, variações e pedido usam UPDLOCK/HOLDLOCK; o novo saldo é calculado sob lock e precisa ser não negativo, além do CHECK no banco. Produtos são processados em ordem de ID para reduzir deadlocks. StockService registra movimentações e sincroniza o total. A disputa da última unidade foi testada com dois processos PHP no SQLite; os locks específicos do SQL Server ainda precisam de homologação na instância exigida.

`router.php` é a opção de desenvolvimento recomendada: serve uma lista explícita de páginas e assets, bloqueando `.env`, SQL, docs, app, storage e scripts. `.htaccess` aplica a mesma lista em VirtualHost Apache na raiz. Não execute com servidor estático, nem `php -S` sem o router. Instalação, ferramentas e criação de administrador só aceitam PHP CLI.

## HTTP/API

Respostas JSON; GET somente leitura. POST com `X-CSRF-Token` obtido em GET `/api/session`. Cookie de sessão obrigatório; respostas da API usam no-store. Códigos: 200 sucesso; 201 criado; 401 login necessário; 403 papel proibido; 404 registro/rota ausente; 409 conflito; 419 CSRF; 422 validação; 429 frequência de contato; 503 indisponibilidade.

| Acesso | Rotas principais |
|---|---|
| Público GET | session, metadata, catalog, product?id, cart |
| Público POST + CSRF | register, login, logout, contact, cart/item, cart/remove, cart/clear, cart/quote |
| Autenticado GET | profile, orders, order?id |
| Autenticado POST + CSRF | profile, addresses, addresses/remove, checkout/key, checkout, order/status |
| Admin GET | admin/dashboard, admin/products, admin/categories, admin/orders, admin/order?id, admin/contacts |
| Admin POST + CSRF | admin/product, admin/product/deactivate, admin/category, admin/upload, admin/order/status |

Catálogo aceita `q`, `category`, `brand`, `gender`, `size`, `sort`, `availability` e `page`, além de new=1, sale=1, featured=1 e test=1. Administração pagina produtos e variações; pedidos usam páginas de 20 registros, sem corte fixo do histórico. Contatos mostram as 100 mensagens mais recentes e estoque as 50 movimentações mais recentes, preservando as demais no banco.

## Operação e limites

SQL Server é padrão. SQLite exige DB_DRIVER=sqlite e é recusado com APP_ENV=production. Não existe fallback automático. `SESSION_SECURE=1` é necessário com HTTPS; `0` serve somente ao HTTP local. Use conta SQL dedicada com SELECT/INSERT/UPDATE/DELETE, sem privilégios de DDL. Mantenha logs e banco fora de publicação. Sessão tem timeout de inatividade de 2h; fechar/perder cookie encerra continuidade anônima; carrinho de conta permanece no banco.

Consultas de perfil podem atualizar nome/e-mail, mas não senha; não há recuperação de senha, nem retenção automática/expiração de estoque de pedidos pendentes. Administrador pode cancelá-los e devolver estoque. Essas regras são adequadas ao ambiente de teste, não uma promessa de operação comercial real.
