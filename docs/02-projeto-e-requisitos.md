# SportZone — projeto e requisitos

## Descrição

**Tema:** comércio eletrônico de artigos esportivos. **Problema:** o protótipo original possuía catálogo em HTML, produto/carrinho fixos, contato sem servidor e ausência de gestão. **Objetivo geral:** evoluir esse protótipo para um sistema em camadas com catálogo persistido, identidade, carrinho, checkout acadêmico e administração. **Público:** praticantes e interessados em futebol, basquete, futsal, vôlei, corrida, academia e casual; professor e estudantes que avaliam o sistema.

**Justificativa:** uma compra reúne formulários, HTTP, sessões, OO, SQL, integridade e análise de sistemas em um fluxo demonstrável. **Escopo:** preservar nome, propósito, páginas e produtos do ZIP; acrescentar gestão e persistência. **Fora do escopo:** cobranças reais, transportadoras, emissão fiscal, recuperação de senha por e-mail, marketplace e logística real. Não há credenciais de gateway; Mercado Pago não foi conectado. A separação `PedidoService`/pagamentos permite implementação posterior com autorização, credenciais sandbox e webhooks autenticados/idempotentes.

**Tecnologias:** HTML5, CSS3, JavaScript, Bootstrap 5.3.3, Bootstrap Icons 1.11.3, PHP 8.2+, PDO_SQLSRV, SQL Server 2019+. SQLite somente em modo explícito de teste local. Playwright/Edge é ferramenta de desenvolvimento, sem dependência Node para servir a loja. As ilustrações SVG foram desenhadas no projeto; nenhuma autorização de uso das fotos externas do ZIP foi presumida.

**Resultados esperados:** uma pessoa encontra um produto pelo banco, adiciona tamanhos e quantidades válidas, autentica-se mantendo seu carrinho, revisa entrega e total, registra pedido pendente, aciona aprovação simulada e acompanha o status. Um administrador gerencia produtos/categorias, visualiza contatos e conduz pedidos até entrega ou cancelamento.

## Requisitos funcionais e rastreabilidade

| ID | Requisito implementado | Evidência principal |
|---|---|---|
| RF01 | Cadastrar nome, e-mail único e senha confirmada | cadastro.html, UsuarioService |
| RF02 | Autenticar com hash, regenerar sessão e limitar tentativas | login.html, UsuarioService |
| RF03 | Encerrar sessão autenticada | perfil.html, API logout |
| RF04 | Listar catálogo ativo com paginação | produtos.html, ProdutoRepository |
| RF05 | Buscar nome e combinar filtros | API catalog, formulário de filtros |
| RF06 | Filtrar categoria, marca, gênero e tamanho | ProdutoRepository::catalog |
| RF07 | Ordenar por preço crescente/decrescente ou nome | ProdutoRepository::catalog |
| RF08 | Identificar produto por ID e selecionar tamanho | produto.html, API product |
| RF09 | Consultar lançamentos e promoções reais | lancamentos.html, promocoes.html |
| RF10 | Informar ausência de resultados | site.js::listing |
| RF11 | Adicionar múltiplos produtos/tamanhos ao carrinho | CarrinhoRepository |
| RF12 | Aumentar/diminuir, remover e esvaziar itens | carrinho.html, API cart |
| RF13 | Calcular subtotais e atualizar indicador do carrinho | CarrinhoService e badge |
| RF14 | Aplicar/remover cupom válido com valor mínimo e validade | API cart/quote |
| RF15 | Estimar frete/prُazo por CEP e incluir no total | FreteService |
| RF16 | Reunir carrinho anônimo e autenticado no login | CarrinhoRepository::merge |
| RF17 | Visualizar e alterar nome/e-mail do perfil | API profile |
| RF18 | Cadastrar, consultar e remover próprios endereços | API addresses |
| RF19 | Informar entrega, revisar e escolher pagamento | checkout.html |
| RF20 | Criar pedido e baixar estoque em uma transação | PedidoService::checkout |
| RF21 | Confirmar pedido pendente com número único | pedido.html |
| RF22 | Simular explicitamente aprovação PIX/cartão/boleto | API order/status |
| RF23 | Consultar histórico e detalhes dos próprios pedidos | PedidoRepository |
| RF24 | Cancelar pedido pendente e devolver estoque | PedidoService::transition |
| RF25 | Proteger painel e cada operação administrativa | Security::admin |
| RF26 | Exibir indicadores e agregação por status | API admin/dashboard |
| RF27 | Cadastrar, listar, editar e desativar produtos | ProdutoService, painel |
| RF28 | Atualizar estoque, preços, tamanhos e lançamento | ProdutoService |
| RF29 | Gerenciar categorias e status ativo/inativo | API admin/category |
| RF30 | Listar/filtrar pedidos e consultar endereço/itens | API admin/orders e admin/order |
| RF31 | Administrar transições de status e histórico | PedidoService, order_events |
| RF32 | Enviar imagens validadas para produtos | API admin/upload |
| RF33 | Registrar contato e consultar no painel | contact_messages |
| RF34 | Retornar erros/loading/feedback e menu mobile | site.js, style.css |
| RF35 | Impedir duplicação ao reenviar confirmação | checkout_key único |

## Requisitos não funcionais verificáveis

| ID | Critério de aceitação | Verificação |
|---|---|---|
| RNF01 Segurança SQL | Nenhum valor HTTP interpolado em SQL; parâmetros posicionais; sort em lista permitida | revisão + payload de SQL Injection |
| RNF02 Segurança de identidade | Hash de senha, cookie HttpOnly/SameSite, troca de ID após login, expiração após 2h inativas | integração HTTP/CLI |
| RNF03 Autorização | Cliente recebe 403 em APIs admin e 404 para pedido alheio | testes de dois usuários |
| RNF04 CSRF/XSS | POST sem token retorna 419; dados renderizados escapados; CSP bloqueia scripts inline | HTTP + navegador com payload |
| RNF05 Responsividade | Sem rolagem horizontal da página em 320/360/390/768/1024/1440px; tabelas e faixa têm scroll próprio | teste de documentElement.scrollWidth |
| RNF06 Usabilidade | Erros próximos ao formulário, status de carregamento e retorno de ações | teste navegador |
| RNF07 Acessibilidade | Labels, nome acessível nos botões, foco visível, skip link, aria-live e redução de movimento | inspeção e navegador; auditoria WCAG completa não alegada |
| RNF08 Desempenho | Listagem limitada a 24 itens; imagens locais/lazy; sem animação 3D; sem framework JS de runtime | inspeção e paginação HTTP |
| RNF09 Manutenibilidade | SQL em repositórios/serviços, apresentação isolada, namespace/autoload | lint e estrutura |
| RNF10 Integridade | FK/CHECK/UNIQUE, DECIMAL e transação; não vender além do estoque nem duplicar checkout | testes de invariantes e banco |
| RNF11 Compatibilidade | PHP 8.2+, SQL Server 2019+, navegador moderno com fetch | doctor e suíte; SQL Server depende de instalação |
| RNF12 Disponibilidade | Falha no banco retorna 503 genérico; arquivos de configuração não são públicos | testes HTTP e inspeção; sem promessa de SLA em servidor escolar |
| RNF13 Privacidade | Não coletar PAN/CVV; nenhum preço do navegador define o pedido | checkout e teste de adulteração |
| RNF14 Dependências | Site inicia sem npm/composer; Bootstrap/ícones via CDN versionada | execução PHP; CDN depende de internet |

As metas de tempo de resposta em celular e disponibilidade contínua precisam ser medidas na máquina de apresentação; não são declaradas aprovadas sem execução nesse equipamento.

## Requisitos acrescentados no SportZone 3.0

| ID | Requisito | Implementação |
|---|---|---|
| RF36 | Consultar indicadores reais, contagens e aprovações demonstrativas separadas de faturamento | DashboardRepository / admin.js |
| RF37 | Controlar estoque por tamanho e validar saldo atual nos ajustes | product_variants / StockService |
| RF38 | Auditar entradas, saídas, edições, compras e cancelamentos | stock_movements |
| RF39 | Pesquisar todos os pedidos com paginação e filtros por cliente, número, data, pagamento e entrega | PedidoRepository::page |
| RF40 | Gerenciar etapas logísticas com transições válidas e observação | PedidoService / Pedido |
| RF41 | Apresentar percurso completo, etapas pendentes e histórico ao proprietário | orders.js |
| RF42 | Cadastrar e consultar galeria de até seis imagens adicionais | product_images / ProdutoService |
| RF43 | Configurar aviso, atendimento e limite de estoque baixo | store_settings |
| RF44 | Confirmar desativação e visualizar prévia administrativa | admin.js |
| RF45 | Proteger também a URL HTML administrativa e criar administrador sem senha fixa | index.php / setup-admin.ps1 |
