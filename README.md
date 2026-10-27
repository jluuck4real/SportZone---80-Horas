# SportZone 3.0

**Site publicado:** https://sportzone-lac.vercel.app/ — páginas na Vercel e backend local conectado por túnel HTTPS temporário. As funcionalidades dinâmicas dependem deste computador ligado. Instalação permanente, reinício e resultados dos testes públicos: [guia de publicação](docs/13-publicacao-vercel.md).

**Versão atual:** painel com nove indicadores, CRUD com galeria, estoque por tamanho/auditoria, pedidos paginados com filtros, etapas internas de entrega e linha do tempo completa. Pagamentos continuam demonstrativos. Guia de operação e alterações: `docs/12-guia-admin-v3.md`.

Para criar o administrador no banco local já instalado:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/setup-admin.ps1 -TestDatabase
```

Escolha sua senha, nome e e-mail; depois entre na loja e abra `admin.html`. Para SQL Server configurado, use o mesmo comando sem `-TestDatabase`. Não há credencial administrativa fixa.

**Atualização de escopo:** pagamentos permanecem demonstrativos, conforme a última orientação do usuário. A Home ganhou carrosséis com compra rápida, escolha de tamanho e teclado/toque; foram revisados cards, textos, checkout e confirmação. Incluídos produtos de R$ 1 e R$ 2, sem entrega física. Veja `docs/10-melhorias-demonstracao.md`.

Para atualizar um banco já instalado, execute `php scripts/migrate.php` com a configuração desse banco. Novas instalações já recebem a evolução pelo instalador. O catálogo esportivo mantém 27 itens; os dois novos produtos de demonstração ficam separados em Testes de pagamento.

Evolução do projeto escolar original para uma loja esportiva com PHP orientado a objetos, API HTTP, sessões e banco relacional. As páginas originais continuam com seus nomes. O ZIP fornecido foi preservado e comparado por SHA-256: os nove arquivos eram idênticos. Veja `docs/01-analise-original.md`.

O sistema implementa cadastro/login/logout, perfil e endereços, catálogo dinâmico com filtros, carrinho persistido e mesclado ao entrar, cupons/frete, checkout em três etapas, pedidos transacionais, aprovação de pagamento **simulada por ação explícita**, histórico e painel administrativo com CRUD, upload, contatos e gestão de status.

**SQL Server é o banco padrão e o alvo acadêmico.** A máquina desta entrega tem PHP 8.2 e PDO SQLite, mas não SQL Server/ODBC moderno/PDO_SQLSRV. A suíte local usa SQLite explicitamente; ela não comprova execução no SQL Server. Consulte os resultados reais em `docs/06-resultados-testes.md`.

## Requisitos

- PHP 8.2+ com PDO, mbstring, fileinfo, openssl e sessão. Para o alvo escolar: PDO_SQLSRV e Microsoft ODBC Driver 18.
- SQL Server 2019+ (edição compatível com uso escolar, por exemplo Express/Developer), banco vazio e conta de instalação.
- Para os assets CDN Bootstrap 5.3.3/Bootstrap Icons 1.11.3: internet no navegador. A aplicação e imagens locais não dependem de APIs externas.
- Node.js somente para regenerar páginas/seeds e executar testes. Não é necessário para servir a loja. Composer não é necessário.

## Configurar SQL Server

1. Instale e inicie SQL Server. Habilite TCP/IP/porta apropriada se usar `localhost,1433`. Crie uma conta dedicada. Não use `sa` no runtime.
2. Instale ODBC Driver 18 e a DLL PDO_SQLSRV compatível com **PHP, arquitetura e TS/NTS**. O PHP detectado nesta máquina é 8.2 x64 Thread Safe; drivers 5.12 incluem `php_pdo_sqlsrv_82_ts.dll`. Versões mais recentes do driver podem exigir PHP mais recente: confira a matriz oficial antes de instalar. Copie a DLL correspondente para a pasta `ext` e habilite-a no `php.ini` ativo:

```ini
extension=php_pdo_sqlsrv_82_ts.dll
```

```powershell
php --ini
php -m
php -r "print_r(PDO::getAvailableDrivers());"
```

3. Pelo SSMS ou sqlcmd, execute `sql/01-database.sql`. Exemplo com autenticação integrada e instância configurada:

```powershell
sqlcmd -S localhost -E -i sql/01-database.sql
```

4. Copie a configuração e edite **somente sua cópia local**:

```powershell
Copy-Item .env.example .env
notepad .env
```

```dotenv
APP_ENV=development
DB_DRIVER=sqlsrv
DB_SERVER=localhost,1433
DB_DATABASE=SportZone
DB_USERNAME=sportzone_app
DB_PASSWORD=sua-senha-local
DB_ENCRYPT=yes
DB_TRUST_CERTIFICATE=no
SESSION_SECURE=0
```

Para certificado autoassinado **somente no desenvolvimento local**, use `DB_TRUST_CERTIFICATE=yes`. Em HTTPS, `SESSION_SECURE=1`. Variáveis do processo têm precedência sobre `.env`. Com autenticação integrada compatível, deixe usuário/senha vazios e execute com a identidade Windows autorizada. Nenhuma credencial real acompanha o projeto.

5. Com uma conta de instalação autorizada a criar tabelas no banco vazio:

```powershell
php scripts/install.php
php scripts/doctor.php
```

Alternativamente, execute em ordem `02-schema.sql` e `03-seed.sql` pelo SSMS/sqlcmd. **Escolha uma opção; não instale duas vezes no mesmo banco.** O instalador não apaga dados e não é uma migração incremental. Depois da instalação, use a conta de runtime com permissões de `sql/05-permissoes-exemplo.sql`. Não conceda DDL/db_owner para uso cotidiano. Execute `04-consultas-e-crud.sql` para demonstrar consultas e CRUD com rollback.

## Iniciar localmente

Na pasta SportZone:

```powershell
php -S localhost:8080 router.php
```

Abra [SportZone local](http://localhost:8080). Encerre com Ctrl+C. O router é obrigatório para impedir acesso a fontes privadas e direcionar `/api/*`. Não abra o HTML por file:// nem use servidor estático.

Atalho equivalente após configurar SQL Server:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/start.ps1
```

### Executar o teste local explícito sem SQL Server

Esta alternativa permite estudar/apresentar os fluxos enquanto a instância SQL Server é preparada; **não substitui o requisito de homologação no SQL Server**:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/start.ps1 -TestDatabase
```

O comando seleciona SQLite de modo explícito, instala `storage/sportzone.sqlite` se ausente e inicia a loja. A mesma aplicação/regras são usadas; não há persistência em localStorage nem fallback automático. APP_ENV=production recusa SQLite.

Opção manual, em uma sessão PowerShell de desenvolvimento:

```powershell
$env:DB_DRIVER='sqlite'
$env:DB_SQLITE_PATH='storage/sportzone.sqlite'
$env:APP_ENV='development'
# Execute o instalador apenas se esse arquivo de banco ainda não existir:
php scripts/install.php
php scripts/doctor.php
php -S localhost:8080 router.php
```

## Criar administrador

Não existe administrador padrão no banco normal. No terminal configurado para o **mesmo banco** da loja:

```powershell
$adminSecret=Read-Host 'Senha do administrador (10 a 72 bytes)' -AsSecureString
$adminCredential=New-Object System.Management.Automation.PSCredential('local',$adminSecret)
$env:SPORTZONE_ADMIN_PASSWORD=$adminCredential.GetNetworkCredential().Password
php scripts/create-admin.php
Remove-Item Env:SPORTZONE_ADMIN_PASSWORD
Remove-Variable adminSecret,adminCredential
```

O script pede nome/e-mail e grava hash com password_hash. A senha é passada pelo ambiente, não por argumento ou arquivo. Use login normal e abra `admin.html` ou o botão no perfil. Contas públicas sempre recebem perfil customer, mesmo se uma requisição enviar role=admin. As credenciais de teste em `tests/fixture.php` existem apenas em bancos isolados criados pela suíte.

## Fluxo e regras rápidas

- 27 produtos iniciais: 25 extraídos dos cards do ZIP e dois itens didáticos SportZone para Academia/Corrida. Categorias originais Futsal/Vôlei também foram mantidas.
- Fotos remotas do original ficam registradas em `data/products.json` como referência; o site usa ilustrações locais próprias. Use upload administrativo para fotos com direito de uso. Não são imagens exatas dos modelos de marca.
- Estoque por tamanho, com total sincronizado por produto e histórico de movimentações. Quantidade por linha 1–99; consulta/carrinho não reservam estoque.
- SPORT10: 10% sobre subtotal de pelo menos R$ 100, ativo e válido até 31/12/2099 no seed. Recalculado no servidor.
- CEP 0–3: R$ 14,90 / 3–5 dias; 4–6: R$ 24,90 / 5–8 dias; 7–9: R$ 34,90 / 8–12 dias. Frete grátis se valor após descontos ≥ R$ 299. **Simulação sem transportadora ou consulta de CEP real.**
- Pedido nasce pendente; cliente pode acionar “Simular aprovação de teste” ou cancelar enquanto pendente. Administração processa, envia e entrega; cancelamento anterior ao envio devolve estoque uma única vez.
- PIX/cartão/boleto são escolhas simuladas; não há QR pagável ou dados de cartão. Mercado Pago não foi integrado por falta de credenciais/autorização sandbox.
- Contato grava mensagem, sem enviar e-mail. Administração consulta em Mensagens.
- Datas persistidas em UTC e apresentadas em America/Sao_Paulo. Valores monetários do negócio usam centavos inteiros e DECIMAL no SQL.

## Testes

```powershell
# HTTP/CLI; cria banco SQLite isolado e servidor próprio em 8091:
node tests/integration.mjs

# Navegador Edge já instalado; dependências somente de desenvolvimento:
npm.cmd ci
node tests/browser.mjs

# Verificação estática:
node --check site.js
Get-ChildItem app,config,scripts -Recurse -Filter '*.php' | ForEach-Object { php -l $_.FullName }
php -l index.php
php -l router.php
```

Relatórios JSON e capturas ficam em `tests/results/`; bancos isolados em `storage/`. Portas 8091/8092 precisam estar livres. O teste de navegador utiliza Edge instalado, sem baixar navegador. O relatório de resultados distingue testes executados de pendências.

Para homologar o SQL Server, siga `docs/08-homologacao-sqlserver.md` em uma instância dedicada. Nunca use banco com dados reais para testes de integração.

## Estrutura

```text
*.html, style.css, site.js   apresentação e API client
assets/images               SVGs próprios
assets/uploads              imagens validadas do administrador
index.php, router.php       front controller e router seguro local
app/Core                    conexão, HTTP, validação e sessão
app/Models                  Usuario, Produto, Pedido
app/Repositories            acesso parametrizado a dados
app/Services                autenticação, carrinho, frete, produto e pedido
app/Controllers             API e autorização
config                      bootstrap/autoload/env
sql                         banco, esquema, seed, consultas, permissões
scripts                     instalação, diagnóstico, admin e geração
tests                       suítes HTTP/CLI e navegador
docs                        análise, requisitos, regras, UML, banco e testes
docs/original               ZIP e nove arquivos preservados
storage                     bancos locais ignorados pelo Git
```

## Apache / XAMPP

O caminho mais simples é o servidor PHP com router. Para Apache, configure VirtualHost com **DocumentRoot na pasta SportZone**, `AllowOverride All`, `mod_rewrite`, `mod_headers` e PHP habilitados. A `.htaccess` permite somente páginas/assets e API. Não coloque a aplicação em subpasta sem adaptar as URLs absolutas `/api/`. Uploads são bytes de imagem sob nomes aleatórios; não configure handlers PHP nessa pasta. Não publique storage/docs/scripts/config/app. Apache/IIS requerem homologação própria além dos testes built-in PHP executados.

## Documentação para as disciplinas

Comece por `docs/01-analise-original.md`, `02-projeto-e-requisitos.md` e `03-regras-e-arquitetura.md`. `04-banco-e-uml.md`, `dicionario-de-dados.md` e `uml/` descrevem o sistema real. `05-metodologia-e-testes.md` apresenta viabilidade, comparação Cascata/Ágil, organização de 80h e plano de testes; `06-resultados-testes.md` tem evidências. `07-roteiro-apresentacao.md` prepara a demonstração.

## Referências oficiais de configuração

- [Microsoft: requisitos e matriz de drivers PHP/SQL Server](https://learn.microsoft.com/en-us/sql/connect/php/system-requirements-for-the-php-sql-driver)
- [Microsoft: carregar os drivers no PHP](https://learn.microsoft.com/en-us/sql/connect/php/loading-the-php-sql-driver)
- [Microsoft: opções de conexão — CharacterSet não é uma opção de PDO_SQLSRV](https://learn.microsoft.com/en-us/sql/connect/php/connection-options)
- [Microsoft: locks UPDLOCK/HOLDLOCK](https://learn.microsoft.com/en-us/sql/t-sql/queries/hints-transact-sql-table)
- [PHP: PDO_SQLSRV](https://www.php.net/pdo-sqlsrv)

Sem pagamento real, garantia de logística ou operação comercial. Essa delimitação aparece na interface e nas regras do projeto.
