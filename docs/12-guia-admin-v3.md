# SportZone 3.0 — guia de administração e acompanhamento

## O que foi entregue

Painel com Visão geral, Produtos, Cadastrar produto, Estoque, Pedidos, Promoções e Configurações. Há também gestão de categorias e mensagens nas ferramentas de apoio. Os dados são persistidos no banco; o painel não utiliza arrays de produtos fictícios, pedidos inventados ou armazenamento local do navegador. Pagamentos são demonstrativos e continuam identificados dessa forma.

SQL Server é o alvo padrão do projeto. Nesta máquina, a execução disponível utiliza SQLite **explicitamente**; SQL Server/PDO_SQLSRV não estão instalados e não foram homologados. Essa limitação é independente do funcionamento testado das telas/regras locais.

## 1. Atualizar e executar

Em uma instalação existente, configure o banco no `.env`/ambiente, faça backup e execute:

```powershell
php scripts/migrate.php
php scripts/doctor.php
php -S localhost:8080 router.php
```

Alternativa local demonstrativa nesta máquina:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/start.ps1 -TestDatabase
```

Esse atalho seleciona SQLite, instala se necessário, aplica as migrações e inicia a loja. Não substitui a homologação SQL Server. Não abra HTML por file://.

Para SQL Server novo: crie o banco com `01-database.sql` e use `php scripts/install.php`. Se aplicar os arquivos pelo SSMS, execute `02-schema.sql`, `03-seed.sql` e `06-admin-v3.sql`; o comando PHP de migração também acrescenta os produtos de demonstração caso ainda não existam. Para a versão anterior do SportZone, prefira `php scripts/migrate.php`, que conhece também as migrações precedentes. `06-admin-v3.sql` oferece a evolução administrativa equivalente para a versão com o esquema de pagamentos anterior já instalado.

## 2. Criar o primeiro administrador

Não existe senha administrativa fixa ou credencial de produção no código. No terminal configurado para o banco da loja:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/setup-admin.ps1
```

Para o banco SQLite local usado pelo atalho de teste:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/setup-admin.ps1 -TestDatabase
```

O script verifica o banco, aplica migrações, solicita senha sem exibi-la e pede nome/e-mail. A senha vai ao PHP pelo ambiente temporário e é armazenada com `password_hash`. A variável é removida ao terminar. E-mail já existente é recusado: não há sobrescrita de senha ou promoção automática de um cliente.

Entre em `login.html` com os dados escolhidos. No perfil, clique em **Painel administrativo**, ou acesse `admin.html`. Visitantes são redirecionados ao login. Clientes recebem 403 ao acessar o painel diretamente ou qualquer API administrativa; esconder menus não é a proteção de acesso.

## 3. Dashboard

- Produtos cadastrados, disponíveis, esgotados e com estoque baixo.
- Pedidos totais, aguardando pagamento, em separação, enviados/em trânsito e entregues.
- Quantidade de variações com estoque baixo e total de promoções.
- Gráfico simples das situações efetivamente registradas e tabela de pedidos recentes.
- Faturamento financeiro verificável separado do total aprovado na demonstração. Sem pagamentos reais, o primeiro indicador fica em R$ 0,00; não são inventadas vendas.

## 4. Cadastrar e editar produtos

Abra **Cadastrar produto** ou **Produtos → Novo produto**. Preencha nome, marca, categoria, público, preço, promoção opcional e descrição. Informe tamanhos separados por vírgula e confira a quantidade de cada um.

O campo de total ajuda a distribuir unidades entre os tamanhos; alterações nele redistribuem o total. Ajuste depois os campos individuais para representar a quantidade desejada. O servidor exige tamanhos únicos, estoque inteiro não negativo e valores monetários válidos. Preço promocional deve ser positivo e menor que o original.

Escolha uma ilustração local ou envie JPG/PNG/WebP até 2 MB e 4096 px. A galeria aceita seis imagens adicionais. MIME e dimensões são validados; o nome do arquivo é aleatório. A prévia do formulário não publica o produto: é necessário salvar.

Marque **Produto ativo**, **Lançamento** e/ou **Destaque na Home**. Depois de salvar, o catálogo, detalhe, preços, carrosséis e disponibilidade usam os novos dados do banco. A listagem permite pesquisar, filtrar categoria/estoque/status e ordenar. A prévia administrativa também funciona para produtos inativos.

**Desativar** pede confirmação e realiza exclusão lógica. Novas compras são bloqueadas e pedidos antigos conservam nome/preço/tamanho históricos. Um produto com estoque zero aparece como esgotado. Para retirar um tamanho, primeiro zere suas unidades; a edição não descarta estoque silenciosamente.

## 5. Controlar estoque

Em **Estoque**, filtre pelo produto ou por variações disponíveis, esgotadas e com estoque baixo. Clique em **Ajustar estoque**, escolha entrada/saída, quantidade e motivo. O saldo mostrado é enviado para conferência; se outro processo o modificar, o ajuste é recusado e a tela precisa ser recarregada.

Cada movimentação guarda produto, tamanho, tipo, quantidade, saldo anterior/posterior, data, responsável, pedido relacionado e observação. A tela exibe as 50 movimentações mais recentes; o banco conserva as demais. O limiar de estoque baixo pode ser alterado em Configurações.

A origem do saldo é `product_variants.stock`; `products.stock` é uma soma sincronizada das variações ativas. Carrinhos não reservam unidades. O checkout bloqueia produtos, verifica tamanho/quantidade e registra baixa/movimentação junto com o pedido em uma única transação.

Na migração, o estoque agregado original foi dividido deterministicamente entre os tamanhos existentes, mantendo o total exato. Isso é uma organização do cadastro legado, **não uma conferência física de cada tamanho**. A equipe pode corrigir a distribuição no painel.

## 6. Gerenciar todos os pedidos

Em **Pedidos**, pesquise número, nome ou e-mail do cliente. Combine intervalo de datas, pagamento, entrega e situação geral. A listagem é paginada e não corta o histórico nos primeiros 200 registros. Datas filtradas representam dias de São Paulo e são comparadas com os registros UTC.

Cada linha mostra número, cliente, data, quantidade de unidades, valor, método de pagamento, situação financeira, etapa de entrega e detalhe. A página completa reúne dados do cliente, endereço, itens/tamanhos, valores, descontos, frete, total e histórico com ator/data/observação.

O cliente consulta somente seus próprios pedidos. A API aplica essa restrição mesmo que alguém altere o identificador na URL.

## 7. Atualizar entrega

O caminho normal é:

```text
Pedido realizado / Aguardando pagamento
→ Pagamento confirmado na demonstração
→ Em separação
→ Preparando envio
→ Enviado
→ Saiu para entrega
→ Entregue
```

No detalhe administrativo, o campo **Próxima etapa** contém apenas transições permitidas pelo servidor. Informe uma observação, se necessário, e salve. Etapas não podem ser saltadas. Não é possível enviar sem aprovação do pagamento nem regredir um pedido entregue.

**Problema na entrega:** permitido após envio ou saída para entrega; exige descrição do problema. A retomada ocorre somente a partir da etapa anterior registrada, sem pular direto para entregue.

**Pagamento recusado:** é uma situação financeira demonstrativa, sem alterar automaticamente o envio. A administração pode retornar a aguardando pagamento ou cancelar.

**Cancelamento:** cliente cancela enquanto aguarda pagamento; administração pode cancelar antes do envio, incluindo separação/preparação. O tamanho comprado recebe as unidades de volta em transação. A marca `stock_released` e as transições impedem uma segunda devolução. Pedidos enviados/entregues não podem ser cancelados pelo fluxo atual.

## 8. Acompanhamento do cliente

Em **Minha conta → Meus pedidos → detalhe**, a linha do tempo exibe as sete etapas completas: concluídas, atual e pendentes. Datas correspondem aos eventos existentes; etapas históricas sem evento individual não recebem datas inventadas. Cancelamento/problema interrompem o caminho visualmente. O histórico registra quem atualizou, quando e a observação.

O acompanhamento é interno. Não há localização em tempo real, código de rastreamento de transportadora ou promessa de integração logística externa.

## 9. Visual e carrossel

A Home mantém três vitrines principais: Destaques SportZone, Lançamentos e Ofertas especiais. Produtos de demonstração permanecem acessíveis por um banner dedicado, reduzindo a repetição de carrosséis. Mais vendidos só aparece quando houver dados financeiros verificáveis suficientes; fica oculto no modo atual.

Os cards usam imagens, nome, marca/categoria, preço, promoção, selos, detalhes e compra rápida. As setas, indicador de posição, teclado, arraste com mouse e toque são nativos; não há autoplay ou biblioteca extra. Compra rápida consulta novamente o produto no banco antes de abrir a seleção de tamanho e desabilita opções esgotadas.

O refinamento inclui sidebar administrativa responsiva, cards de indicadores, tabelas com scroll próprio, diálogo de confirmação, skeleton de carregamento, contador de carrinho animado, seleção de tamanho, galeria, transições e destaque de confirmação. `prefers-reduced-motion` desliga as animações relevantes.

## Arquivos principais desta evolução

**Novos:** `admin.js`, `orders.js`, `product.js`; `app/Core/AdminUpgrade.php`; `StockService.php`; `StockRepository.php`, `DashboardRepository.php`, `SettingsRepository.php`; `scripts/setup-admin.ps1`, `generate-admin-sql.mjs`; `sql/06-admin-v3.sql`; testes V3, concorrência e migração; documentação 11/12.

**Alterados:** `site.js`, `storefront.js`, `style.css`, páginas HTML e gerador; controlador/API, modelos e repositórios de produto/carrinho/pedido; serviços de produto/pedido/pagamento/carrinho; esquema SQL/migração, roteamento e proteção de `admin.html`; criação do administrador; suítes de regressão, relatórios e diagramas. Scripts pontuais de transformação da interface da versão anterior foram retirados; não são necessários para executar ou manter o site.

Resultados executados e capturas: `docs/06-resultados-testes.md` e `tests/results/`. SQL Server e Apache/IIS continuam como homologações externas pendentes; não confundir os testes locais com aprovação nesses ambientes.
