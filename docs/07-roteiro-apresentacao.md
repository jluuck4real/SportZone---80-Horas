# Roteiro para apresentação ao professor

1. **Origem (2 min):** mostrar `docs/original/SportZone.zip`, hashes iguais e análise dos nove arquivos. Explicar preservação de SportZone, navegação, categorias e produtos.
2. **Arquitetura (2 min):** abrir ApiController, UsuarioService, ProdutoRepository e Database. Mostrar query parametrizada sem SQL no HTML.
3. **Banco (3 min):** abrir DER/dicionário, executar consultas de `04-consultas-e-crud.sql` no SQL Server configurado. Explicar FKs, DECIMAL, índices e snapshot do pedido. Se executar em SQLite, declarar explicitamente a limitação e não dizer que SQL Server foi testado.
4. **Compra (5 min):** filtrar Basquete, buscar Sabrina, selecionar tamanho, adicionar produto e outro item. Alterar quantidade, aplicar SPORT10, calcular CEP. Cadastrar/entrar para mostrar continuidade do carrinho. Informar endereço, revisar total e criar pedido de teste.
5. **Pagamento/estoque (2 min):** mostrar que nasce pendente. Consultar estoque e clicar em simular aprovação. Nenhum dado de cartão é pedido. Mostrar histórico.
6. **Admin (3 min):** entrar com administrador criado por CLI; cadastrar/editar/desativar produto; mostrar categoria, dashboard, pedido e status em processamento → enviado → entregue. Usar outro pedido para mostrar cancelamento e devolução de estoque.
7. **Segurança/testes (3 min):** cliente recebe 403 na API admin; segundo cliente não vê pedido alheio. Mostrar relatório real, screenshots e comandos de reprodução.

## Preparação

- Configurar SQL Server com antecedência, validar `php scripts/doctor.php`, criar administrador sem publicar senha.
- Ter dois usuários clientes distintos e um administrador. Não reutilizar credenciais pessoais.
- Garantir internet para Bootstrap/ícones da CDN, ou preparar cópias locais autorizadas antes da aula.
- Iniciar `php -S localhost:8080 router.php`. Nunca abrir HTML com file://.
- Testar tamanho mobile no navegador e mostrar redução de movimento quando ativada no sistema.
- Não efetuar pagamentos reais e não inserir dados reais de cartão.

## Relação com as disciplinas

**Programação Web II:** classes com responsabilidades, encapsulamento, HTTP JSON, sessões/cookies, autenticação, CRUD e regras no servidor. **Análise e Projeto:** diagnóstico do legado, escopo, RF/RNF, regras, casos de uso, classes, atividade, sequência e metodologia. **Banco de Dados I:** entidades/relacionamentos, PK/FK/UNIQUE/CHECK, DECIMAL, índices, SQL parametrizado e transações.
