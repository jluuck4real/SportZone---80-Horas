# Revisão da versão atual e plano de evolução

**Atualização de escopo solicitada pelo usuário:** não é mais necessário realizar pagamentos reais. Esta entrega mantém somente simulação. PaymentConfig::provider retorna simulation de forma explícita, mesmo que existam variáveis de produção. Não há cliente HTTP de cobrança, webhook ativo ou necessidade de credenciais/HTTPS para a demonstração. A implementação externa iniciada foi retirada da entrega; apenas campos preparatórios no esquema foram preservados na migração. O plano abaixo registra a análise anterior à mudança de escopo e não representa funcionalidades financeiras entregues.

Referência principal mantida: `docs/original/SportZone.zip` e análise dos nove arquivos originais. A versão atual foi revisada por camadas (HTML, JavaScript, CSS, PHP, SQL, scripts, documentação e testes); dependências instaladas, bancos locais e artefatos binários não são código de aplicação.

## Diagnóstico antes das alterações

- Todos os pagamentos são simulados. Não existe cliente HTTP do Mercado Pago, credencial, webhook ou validação de transação externa.
- O esquema impõe simulated=1 e só aceita status de simulação; exige migração preservando dados.
- PedidoService permite aprovação local, comportamento que precisa ficar restrito a pagamentos simulados.
- A faixa de lançamentos original evoluída tem scroll/setas, mas não possui ação de compra, estado de navegação ou múltiplas coleções.
- Não existe text-transform:lowercase no CSS. Gêneros aparecem em minúsculas porque os valores técnicos são usados como rótulos; status externos também precisam de tradução e capitalização. Não se deve usar capitalize indiscriminadamente em nomes/marcas.
- As páginas repetem “sem cobrança real”, informação que precisa refletir o ambiente selecionado.
- Frete de teste não pode elevar produtos de R$ 1/R$ 2 artificialmente: serão itens de teste sem entrega física, em seção separada. Carrinho misto continua usando frete normal.

## Solução

1. Manter PHP/OO/PDO e SQL Server como alvo, com adaptador SQLite explícito para testes.
2. Acrescentar configurações privadas e cliente HTTP de origem fixa para API oficial.
3. PIX via Payments API com chave idempotente persistida; cartão/boleto via Checkout Pro hospedado.
4. Confirmar exclusivamente consultando a API e conferindo referência externa, valor, BRL, vendedor e live_mode. Webhook HMAC não confia no status do corpo.
5. Persistir tentativas/transações, eventos de webhook, preferência, QR Code, sincronização e auditoria. Repetições e eventos fora de ordem não duplicam estoque/aprovação.
6. Proibir aprovação/cancelamento financeiro manual de transações reais; tratar recusa, devolução e pagamento após cancelamento.
7. Melhorar carrosséis nativos sem dependências novas, cards, textos e feedback, preservando identidade/navegação/modalidades.
8. Validar com testes HTTP, gateway controlado e navegador; distinguir esses testes de homologação real Mercado Pago.

## Configuração detectada

Nenhum `.env` ou variável MP_/PAYMENT_ configurado no início desta evolução. Não existe URL pública HTTPS conhecida. O servidor local usa SQLite explicitamente. Instância SQL Server/PDO_SQLSRV continuam pendentes. Não criar cobranças na implementação ou nas suítes automatizadas.

## Fontes oficiais consultadas

- [PIX: criação e idempotência](https://www.mercadopago.com.br/developers/pt/docs/checkout-api-payments/integration-configuration/integrate-pix)
- [Meios disponíveis e min_allowed_amount/max_allowed_amount](https://www.mercadopago.com.br/developers/pt/reference/online-payments/checkout-api-payments/payment-methods/get)
- [Checkout Pro](https://www.mercadopago.com.br/developers/pt/docs/checkout-pro-preferences/overview)
- [Webhooks e assinatura](https://www.mercadopago.com.br/developers/pt/docs/subscriptions/additional-content/your-integrations/notifications/webhooks)
- [Validação de webhooks no SDK oficial PHP](https://mercadopago.github.io/sdk-php/docs/webhooks.html)

Não foi confirmado um mínimo universal PIX/boletos nas fontes públicas para esta conta. Valores ilustrativos da documentação ou mínimos de plugins terceiros não serão tratados como limites contratuais. A confirmação dos valores será feita pela API autenticada e pela resposta de criação no ambiente correto.
