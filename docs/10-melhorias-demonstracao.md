# Evolução visual e demonstrativa — entrega atual

O usuário retirou a necessidade de pagamento real durante o desenvolvimento. O resultado mantém pagamentos exclusivamente simulados e concentra a evolução na apresentação, navegação e demonstração do fluxo. Não existem cobranças externas, QR Code pagável ou confirmação real do Mercado Pago. Não são necessárias credenciais.

## Melhorias entregues

- SportZone, navegação, modalidades e produtos do ZIP continuam como referência. O CSS original permanece como base, com camadas de refinamento ao final.
- Cards com preço/desconto, disponibilidade, imagem sem corte, link de detalhes e adição ao carrinho. Produtos com tamanhos abrem um diálogo de escolha; tamanho único permite adição direta.
- Carrosséis para Destaques SportZone, Lançamentos, Ofertas especiais e Produtos de teste. Setas desabilitam nas extremidades; indicador informa posições; suporte a teclado, rolagem com toque e arraste com mouse. Não há autoplay nem biblioteca de carrossel.
- Destaques são curadoria do catálogo e podem ser alterados no painel. Mais vendidos só aparece quando existem vendas reais verificadas; no modo demonstrativo fica oculto. Nenhuma estatística foi inventada.
- Página de produto apresenta produtos relacionados da mesma modalidade.
- Rótulos Masculino/Feminino/Unissex corrigidos sem mudar os valores técnicos. PIX preserva a sigla. Status de pagamento têm nomes legíveis, separados das chaves internas.
- Produtos de R$ 1 e R$ 2 cadastrados em Testes de pagamento. São itens sem entrega física; carrinho formado somente por esses itens tem frete zero. Carrinho misto usa a regra de frete dos artigos esportivos.
- Checkout e pedido destacam “Simulação local”, sem pedir CPF, cartão ou dados bancários. Aprovação continua dependendo do botão explícito de simulação e fica registrada no histórico.
- Melhorias de contraste, hierarquia, espaçamento, bordas, sombras e estados de foco/hover; efeitos leves respeitam preferência de redução de movimento.

## Arquivos alterados

| Grupo | Arquivos |
|---|---|
| Interface | `site.js`, `style.css`, `storefront.js`, `payments.js`, todas as páginas HTML geradas por `scripts/build-pages.mjs` |
| Catálogo/pedido | `app/Repositories/ProdutoRepository.php`, `CarrinhoRepository.php`, `PedidoRepository.php`; `app/Services/ProdutoService.php`, `CarrinhoService.php`, `PedidoService.php`, `PaymentService.php`; `app/Models/Pedido.php` |
| API/configuração | `app/Controllers/ApiController.php`, `app/Payments/PaymentConfig.php`, `index.php`, `router.php`, `.htaccess`, `.env.example` |
| Banco | `sql/02-schema.sql`, `app/Core/SchemaUpgrade.php`, `scripts/migrate.php`, `scripts/install.php` |
| Ferramentas | `scripts/build-pages.mjs`, `scripts/refine-ui.mjs`, `scripts/check.mjs`, `scripts/report-tests.mjs` |
| Testes/documentação | `tests/integration.mjs`, `tests/browser.mjs`, relatórios/capturas, README, documentação 09/10 e dicionário atualizado |

## Atualizar uma instalação existente

Com as variáveis apontando para o banco existente:

```powershell
php scripts/migrate.php
```

A migração preserva usuários, pedidos e carrinhos; acrescenta campos de destaque/teste e itens de demonstração. No SQLite, tabelas com restrições antigas são reconstruídas em transação e verificadas por foreign_key_check. Faça backup antes de aplicar em um banco com dados importantes. SQL Server continua sendo alvo acadêmico, mas sua migração requer homologação na instância da escola.

Para executar localmente:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/start.ps1 -TestDatabase
```

Abra `http://localhost:8080` e atualize a página. Não é necessário refazer o cadastro para ver os novos produtos.

## Limites

Pagamentos são demonstração, por solicitação do usuário. Esquema contém alguns campos reservados à análise de integração, mas eles não habilitam chamadas ao Mercado Pago. SQL Server/PDO_SQLSRV não estavam disponíveis no ambiente; os resultados executados utilizam SQLite. O relatório atualizado descreve testes reais do modo demonstrativo.
