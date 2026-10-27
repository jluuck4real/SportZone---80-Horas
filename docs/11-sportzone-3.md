# SportZone 3.0 — análise e implementação

Referências mantidas: o ZIP original preservado em `docs/original/`, a análise inicial e a versão atual. Não há recriação do site ou troca da stack. Pagamentos continuam exclusivamente demonstrativos.

## Diagnóstico

Existem login com sessão/hash, autorização por papel no servidor, script CLI para criar administrador, CRUD básico, carrinho e checkout transacional. Faltam navegação administrativa completa, filtros/paginação de todos os pedidos, estoque por tamanho/auditoria e etapas logísticas detalhadas. A linha do tempo antiga mostra somente eventos ocorridos; não apresenta o percurso completo nem as etapas pendentes. A Home tem carrosséis nativos, mas precisa de maior destaque e menos coleções repetidas.

## Decisões

- Preservar dados e adicionar migração idempotente. Na ausência de contagens históricas por tamanho, distribuir o total original entre os tamanhos cadastrados, mantendo exatamente o total e registrando a inicialização; a administração pode corrigir a distribuição. Não alegar inventário físico conferido.
- Usar variação de tamanho como origem do estoque; `products.stock` passa a ser um total sincronizado. Toda entrada, saída, venda, edição ou cancelamento registra movimentação.
- Separar situação do pagamento da etapa logística. Confirmação de pagamento não significa envio. Histórico registra ator, instante, etapa anterior e observação.
- Manter o identificador legado `em_processamento` compatível e apresentá-lo como “Em separação”. Novas etapas: preparando envio, saiu para entrega e problema na entrega.
- Expor todos os pedidos por paginação e filtros; cliente continua restrito ao próprio usuário.
- Dashboard apresenta contagens do banco, valores demonstrativos separados de faturamento financeiro verificável e nenhuma venda inventada.
- Administração terá seções Visão geral, Produtos, Cadastrar produto, Estoque, Pedidos, Promoções e Configurações, além de categorias/mensagens existentes.

## Ambiente e validação

PHP/PDO SQLite disponíveis; SQL Server/PDO_SQLSRV ausentes. SQL Server continua como driver padrão e alvo dos scripts; teste local permanece explicitamente SQLite. Não declarar homologação SQL Server sem executá-la. As evidências desta versão serão registradas após os testes, incluindo preservação dos dados, variações, cancelamento único, transições e navegador.
