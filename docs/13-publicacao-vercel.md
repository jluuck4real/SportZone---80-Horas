# Publicação do SportZone na Vercel

Endereço público: https://sportzone-lac.vercel.app/

O texto das disciplinas foi removido do rodapé nas 14 páginas e no gerador
`scripts/build-pages.mjs`.

## Arquitetura publicada

As páginas, estilos, scripts e imagens SVG ficam na Vercel. O build
`node scripts/build-vercel.mjs` copia somente arquivos públicos para
`public-vercel`. Código PHP, bancos, credenciais e documentos privados não
fazem parte da saída publicada. O HTML administrativo é entregue pelo PHP,
após validar a sessão e o perfil.

As rotas `/api/*`, `/admin.html` e `/assets/uploads/*` são encaminhadas ao
backend por regras de `vercel.json`. Sessões e respostas administrativas não
são armazenadas em cache. O cookie da sessão usa Secure, HttpOnly e SameSite=Lax.

## Limitação atual: backend temporário

Nesta publicação, o backend PHP executa neste computador na porta 8081,
acessível por um Quick Tunnel HTTPS do Cloudflare. O banco continua sendo o
SQLite local já usado pelo projeto, sem migração ou exclusão dos registros.
Não foi provisionado um SQL Server remoto. Pagamentos continuam simulados.

O catálogo dinâmico, login, carrinho, checkout e painel dependem de o
computador estar ligado, conectado à internet e com PHP e túnel executando.
As páginas estáticas continuam hospedadas na Vercel se esses processos pararem,
mas a API fica indisponível. O túnel é adequado à apresentação e aos testes;
não constitui hospedagem permanente do backend.

Processos desta publicação:

- PHP: PID registrado em `storage/vercel-backend.pid`.
- Túnel: PID registrado em `storage/vercel-tunnel.pid`.
- Logs: `storage/vercel-backend-error.log` e `storage/vercel-tunnel.log`.

## Restaurar o backend após desligar o computador

No PowerShell, abra a pasta do projeto e execute o PHP em um terminal:

```powershell
cd "C:\Users\pepic\OneDrive\Área de Trabalho\SportZone"
$env:DB_DRIVER = 'sqlite'
$env:DB_SQLITE_PATH = 'storage/sportzone.sqlite'
$env:APP_ENV = 'development'
$env:SESSION_SECURE = '1'
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8081 router.php
```

Em outro terminal, na mesma pasta:

```powershell
& '.\storage\tools\cloudflared.exe' tunnel --url http://127.0.0.1:8081
```

Copie a nova origem `https://...trycloudflare.com` exibida pelo túnel. Como o
endereço muda quando ele reinicia, atualize as regras e publique novamente:

```powershell
node scripts/configure-vercel.mjs https://SEU-NOVO-ENDERECO.trycloudflare.com
npx.cmd --yes vercel@65.4.0 deploy --prod --yes
```

Mantenha os dois terminais abertos durante a apresentação.

## Hospedagem permanente

Para eliminar a dependência deste computador, instale o backend em um servidor
PHP 8.2+ com PDO_SQLSRV, ODBC e SQL Server acessível. Siga a instalação SQL Server
do README, configure as credenciais somente no servidor, use HTTPS,
`APP_ENV=production` e `SESSION_SECURE=1`, e configure uma pasta persistente
para sessões e uploads. A configuração do servidor deve preservar as proteções
do `router.php`/`.htaccess`, sem servir arquivos privados.

Depois substitua a origem do túnel pelo endereço HTTPS desse servidor:

```powershell
node scripts/configure-vercel.mjs https://backend.seu-dominio.com
npx.cmd --yes vercel@65.4.0 deploy --prod --yes
```

Não é necessário converter a aplicação para outro framework nem substituir
o banco por armazenamento no navegador.

## Validação executada no endereço público

```powershell
node tests/deployment.mjs https://sportzone-lac.vercel.app
```

Resultado: 6 grupos aprovados, registrados em `tests/results/deployment.json`:

1. Home pública, carrosséis, produtos e rodapé atualizado.
2. API HTTPS e cookie seguro.
3. Adição ao carrinho, persistência da sessão e cálculo do preço no servidor.
4. Rejeição de CSRF inválido, API administrativa sem login e redirecionamento
   do painel para autenticação.
5. Bloqueio de acesso público a banco, configurações, credenciais e código PHP.
6. Home e catálogo em 390 e 1440 pixels, sem rolagem horizontal nem erros JavaScript.

O teste do carrinho usa uma sessão temporária e limpa os próprios itens. Não
cria pedidos nem cobranças, e não altera estoque. Não foram verificados nesta
publicação pagamentos reais, SQL Server remoto ou disponibilidade após desligar
o computador.

Referências: [rewrites da Vercel](https://vercel.com/docs/routing/rewrites) e
[Quick Tunnels do Cloudflare](https://developers.cloudflare.com/tunnel/get-started/quick-tunnels/).
