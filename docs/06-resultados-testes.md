# Resultados reais dos testes — SportZone 3.0

Execuções em America/Sao_Paulo: integração 26/10/2026, 23:20:27; administração/integridade 26/10/2026, 23:31:41; navegador 26/10/2026, 23:32:27.

| Verificação | Resultado executado | Ambiente |
|---|---|---|
| Regressão HTTP/CLI | **39/39 aprovados** | PHP 8.2.12 + PDO SQLite isolado |
| Administração, estoque, pedidos e migração V3 | **25/25 aprovados** | SQLite isolado, incluindo dois processos PHP concorrentes |
| Navegador | **25/25 aprovados** | Edge / Playwright, banco SQLite isolado |
| Sintaxe | **54/54 aprovadas** | php -l e node --check |
| SQL Server / PDO_SQLSRV | **Não executado — pendente** | Instância e driver ausentes |
| Pagamento real | **Não executado, fora do escopo** | Somente demonstração explícita |

Os 6 testes de largura passaram por 18 páginas/visões, totalizando 108 combinações. Isso verifica rolagem horizontal geral; tabelas e carrosséis têm rolagem própria. Não é teste de carga nem certificação WCAG.

Relatórios detalhados: tests/results/integration.json, v3.json, browser.json e static.json.

## Regressão HTTP/CLI

| Nº | Caso executado | Resultado |
|---|---|---|
| 1 | Sessão e CSRF: mutação sem token bloqueada | PASS |
| 2 | Cadastro válido e rejeição de confirmação incorreta | PASS |
| 3 | Cadastro duplicado não cria segundo usuário | PASS |
| 4 | Login com senha incorreta | PASS |
| 5 | Login correto regenera sessão e preserva carrinho anônimo | PASS |
| 6 | Busca e filtros reais, ordenação e resultado vazio | PASS |
| 7 | Detalhe identifica produto por ID | PASS |
| 8 | Injeção SQL na busca e ID não altera dados | PASS |
| 9 | Autorização administrativa no servidor | PASS |
| 10 | Carrinho aceita vários itens e ignora preços do navegador | PASS |
| 11 | Alterar quantidade e impedir zero, negativo e acima do estoque | PASS |
| 12 | Remover item e atualizar subtotal | PASS |
| 13 | Cupom inválido e cálculo de desconto/frete no servidor | PASS |
| 14 | CEP inválido rejeitado e regras regionais de frete | PASS |
| 15 | Atualizar perfil e cadastrar endereço | PASS |
| 16 | Checkout rejeita total adulterado sem reduzir estoque | PASS |
| 17 | Checkout grava pedido, itens, pagamento pendente e reduz estoque | PASS |
| 18 | Checkout idempotente não duplica pedido ou estoque | PASS |
| 19 | Histórico restringe pedido e endereço ao proprietário | PASS |
| 20 | Aprovação simulada apenas por ação explícita e transições válidas | PASS |
| 21 | Login administrativo, cadastro e edição de produto | PASS |
| 22 | CRUD de categoria | PASS |
| 23 | Gestão de status e cancelamento devolve estoque uma única vez | PASS |
| 24 | Contato gravado e conteúdo potencial XSS tratado como dados | PASS |
| 25 | Dashboard e filtros administrativos | PASS |
| 26 | Arquivos privados, ZIP, configuração e SQL não expostos | PASS |
| 27 | Logout encerra acesso e login recupera carrinho salvo | PASS |
| 28 | Hash seguro no banco (verificação por CLI) | PASS |
| 29 | Regras de frete: faixas e gratuidade após desconto | PASS |
| 30 | Cupom expirado é rejeitado | PASS |
| 31 | Estoque individual por tamanho impede excedente sem bloquear outros tamanhos | PASS |
| 32 | Falha durante gravação do pagamento faz rollback de pedido e estoque | PASS |
| 33 | Cartão e boleto simulados criam pedidos pendentes sem dados sensíveis | PASS |
| 34 | Concorrência lógica: estoque alterado após carrinho bloqueia checkout | PASS |
| 35 | Upload: extensão/MIME inválidos bloqueados no servidor | PASS |
| 36 | Upload PNG válido e associação ao produto | PASS |
| 37 | Cliente não pode promover o próprio perfil pelo cadastro | PASS |
| 38 | Login bloqueia após cinco falhas por IP/e-mail | PASS |
| 39 | Produtos de teste: preços reais no banco, sem frete e sem ativação externa | PASS |

## Administração e integridade V3

| Nº | Caso executado | Resultado |
|---|---|---|
| 1 | Página admin e todas as APIs de gestão negam acesso a cliente | PASS |
| 2 | CSRF obrigatório em cadastro e ajuste administrativo | PASS |
| 3 | Cadastrar produto com variantes, galeria e destaque reflete no catálogo | PASS |
| 4 | Preço promocional e edição persistem após nova leitura | PASS |
| 5 | Tamanho esgotado e quantidade acima da variação são bloqueados | PASS |
| 6 | Ajustar estoque exige saldo atual, registra ator/motivo e impede negativo | PASS |
| 7 | Não é permitido descartar tamanho com estoque durante edição | PASS |
| 8 | Pedido baixa somente o tamanho comprado e registra saída | PASS |
| 9 | Listagem de pedidos inclui cliente, itens e filtros persistidos no banco | PASS |
| 10 | Pagamento e entrega separados: não é possível enviar antes da aprovação | PASS |
| 11 | Separação e preparação são etapas obrigatórias, sem saltos | PASS |
| 12 | Problema de entrega exige motivo e retorna somente à etapa anterior | PASS |
| 13 | Pedido entregue não pode ser cancelado nem regredir | PASS |
| 14 | Isolamento do pedido e histórico por usuário | PASS |
| 15 | Cancelamento devolve a variação exatamente uma vez e registra autor | PASS |
| 16 | Recusa de demonstração fica separada da logística e pode ser retomada | PASS |
| 17 | Configuração salva aviso e limiar real, com validação | PASS |
| 18 | Dashboard usa contagens reais e distingue total demonstrativo do financeiro | PASS |
| 19 | Desativação bloqueia compra e preserva detalhe administrativo | PASS |
| 20 | Paginação de todos os pedidos além de uma página | PASS |
| 21 | SQL Injection, XSS e filtros de gestão continuam tratados como dados | PASS |
| 22 | Migração repetida preserva pedidos, usuários, descrição editada e estoque | PASS |
| 23 | Migração de banco legado preserva conta, pedido enviado e total de unidades | PASS |
| 24 | Dois processos PHP disputam a última unidade sem duplicar pedido ou estoque | PASS |
| 25 | Script CLI cria administrador com senha fornecida e não sobrescreve conta existente | PASS |

## Navegador

| Nº | Caso executado | Resultado |
|---|---|---|
| 1 | Home desktop carrega API, imagens locais e identidade SportZone | PASS |
| 2 | Catálogo: filtro por modalidade, nome e detalhe correto | PASS |
| 3 | Selecionar tamanho, adicionar produto e carrinho real | PASS |
| 4 | Cadastro e login pelo navegador preservam carrinho | PASS |
| 5 | Checkout: entrega, revisão, pagamento e confirmação pendente | PASS |
| 6 | Pagamento só muda após clicar em simular aprovação | PASS |
| 7 | Perfil e histórico acessíveis | PASS |
| 8 | XSS armazenado não executa na área administrativa | PASS |
| 9 | Administração: cadastrar, editar produto e atualizar status | PASS |
| 10 | Carrossel: setas, teclado e adição com tamanho | PASS |
| 11 | Produtos R$ 1/R$ 2 aparecem e teste isolado não cobra frete | PASS |
| 12 | Capitalização: gêneros legíveis e sigla PIX preservada | PASS |
| 13 | Estoque administrativo: ajuste por tamanho persiste ao recarregar | PASS |
| 14 | Promoção e configurações administrativas persistem | PASS |
| 15 | Entrega administrativa: etapas, problema e linha do tempo do cliente | PASS |
| 16 | Galeria e prévia do produto funcionam com imagem adicional enviada | PASS |
| 17 | Desativação exige confirmação e persiste após atualização | PASS |
| 18 | Responsividade 1440px sem rolagem horizontal | PASS |
| 19 | Responsividade 1024px sem rolagem horizontal | PASS |
| 20 | Responsividade 768px sem rolagem horizontal | PASS |
| 21 | Responsividade 390px sem rolagem horizontal | PASS |
| 22 | Responsividade 360px sem rolagem horizontal | PASS |
| 23 | Responsividade 320px sem rolagem horizontal | PASS |
| 24 | Menu mobile expande com aria-expanded | PASS |
| 25 | Sem exceções de JavaScript | PASS |

## Evidências visuais

- tests/results/home-desktop.png
- tests/results/home-mobile.png
- tests/results/admin-desktop.png
- tests/results/estoque-desktop.png
- tests/results/entrega-admin-desktop.png
- tests/results/entrega-cliente-mobile.png
- tests/results/pedido-desktop.png

## Integridade e limitações

- O teste de falha no INSERT de pagamento confirma rollback de pedido, estoque e movimentações; o erro SQLSTATE 23000/409 é esperado nesse caso SQLite.
- Dois processos PHP iniciados no mesmo instante disputaram a última unidade: exatamente um pedido foi criado e não houve estoque negativo. Este resultado é de SQLite; não comprova os locks do SQL Server.
- A migração foi executada sobre banco legado com usuário/pedido enviado e repetida sobre banco atual. Contas, pedidos, estoque, descrição editada e histórico foram preservados; foreign_key_check não apontou falhas.
- Foram verificados isolamento entre clientes, acesso 403 ao HTML/admin APIs, CSRF, SQL Injection, XSS armazenado, aprovação demonstrativa, recusa, cancelamento único e transições logísticas inválidas.
- Diagramas e documentação descrevem os dados e as classes presentes; os arquivos editáveis foram entregues, sem alegar renderização visual de UML não executada.
- Pendentes: SQL Server (esquema/migração/Unicode/DECIMAL/locks), Apache/IIS, aparelho físico, medição de desempenho e auditoria com leitor de tela.
- Pagamentos e entregas são demonstrativos; não houve cobrança, aprovação financeira ou rastreamento real de transportadora.
