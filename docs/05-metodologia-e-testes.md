# Análise, desenvolvimento e plano de testes

## Viabilidade

**Técnica:** o PHP 8.2 local executa o back-end sem framework; o front original fornece base compatível com os conteúdos. SQL Server exige instalação adicional: instância, ODBC e PDO_SQLSRV compatível com versão/arquitetura/thread safety do PHP. O ambiente desta entrega não possui esses componentes; os testes executáveis usam SQLite explicitamente, sem alegar homologação SQL Server.

**Econômica:** não há compra de gateway, fotos ou serviços. Ferramentas de desenvolvimento e banco em edição de desenvolvimento/escolar podem ser utilizados respeitando suas licenças. **Operacional:** professor consegue reproduzir a execução via README e demonstrar pedidos sem cobrança. **Prazo:** o escopo tem foco nas três disciplinas; logística real, cobrança e e-mails foram excluídos para concentrar a atividade.

## Cascata e Ágil

| Abordagem | Aplicação ao SportZone |
|---|---|
| Cascata | Especificação e modelo relacional antes da implementação ajudam a atender rubrica escolar; mudanças de tela/regras tardias custam mais. |
| Ágil incremental | Entregar catálogo, identidade, carrinho e checkout em incrementos permite verificar o fluxo no navegador e ajustar problemas cedo. |

Escolhida abordagem incremental com documentação inicial e rastreabilidade: análise do ZIP → modelo/contratos → serviços → interface → testes de fluxo → correções. A documentação final descreve o comportamento implementado, não um escopo hipotético.

## Organização sugerida da atividade de 80h

Esta é uma distribuição pedagógica planejada, não um registro de horas efetivamente gastas pelo agente.

| Etapa solicitada | Horas | Entrega |
|---|---:|---|
| 1 Análise e planejamento | 6 | comparação ZIP, inventário e diagnóstico |
| 2 Arquitetura e banco | 8 | modelo relacional, esquema e seed |
| 3 Modelos/repositórios/controladores | 8 | camadas e contrato HTTP |
| 4 Usuários/autenticação | 7 | cadastro/login/perfil/endereço |
| 5 Catálogo | 6 | filtros, busca, detalhe e promoções |
| 6 Carrinho | 7 | persistência, mesclagem e cálculos |
| 7 Checkout/frete/pagamento | 9 | etapas e transação |
| 8 Gestão de pedidos | 4 | histórico e estados |
| 9 Administração | 6 | CRUD e dashboard |
| 10 Interface/responsividade | 7 | CSS evoluído e estados acessíveis |
| 11 Documentação/UML | 6 | requisitos, regras e diagramas |
| 12 Testes/apresentação | 6 | evidências e roteiro |
| **Total** | **80** | |

## Estratégia e casos

As suítes criam um banco próprio com nome timestamp, não usam dados pessoais e não limpam o banco normal. Os bancos de testes ficam em storage para inspeção. O teste SQL Server requer banco vazio dedicado; ver README. `tests/integration.mjs` testa HTTP/CLI sem dependências npm. `tests/browser.mjs` usa Edge instalado com Playwright como dependência de desenvolvimento.

| Grupo | Casos de aceitação |
|---|---|
| Identidade | cadastro válido/inválido/duplicado, senha errada, login e regeneração, logout, hash no banco |
| Catálogo | busca, categoria/marca/tamanho, ordem de preços, ID de detalhe, vazio |
| Carrinho | vários itens, adição/alteração/remoção, subtotal, quantidade inválida, estoque agregado, preço adulterado ignorado |
| Desconto/frete | cupom inválido/mínimo/validade, arredondamento, CEP inválido, faixas e gratuidade após desconto |
| Checkout | entrega válida, total divergente recusado, estado pendente, itens persistidos, baixa de estoque e idempotência |
| Pedidos | histórico próprio, isolamento de segundo usuário, aprovação explícita de teste, transições e devolução única |
| Admin | acesso 401/403, produto criar/editar/desativar, categoria, filtro de pedidos, dashboard e contato |
| Segurança | CSRF ausente, SQL Injection em busca/ID, XSS armazenado escapado, paths privados bloqueados, upload com MIME inválido |
| Navegador | catálogo→produto→carrinho→cadastro/login→checkout→pedido; painel CRUD/status; mobile menu |
| Layout | ausência de scroll horizontal geral em 320, 360, 390, 768, 1024 e 1440px; imagens locais |

## Limites de verificação

Testes SQLite verificam aplicação e invariantes mas não demonstram sintaxe, locks ou concorrência no SQL Server. Os scripts SQL e a conexão precisam ser executados na instância da escola. A automação do navegador verifica dimensões e alguns fluxos, mas não substitui inspeção em aparelho físico, leitores de tela ou auditoria WCAG completa. Apache/IIS não foram executados se o relatório indicar somente PHP built-in. Não foram feitos testes de carga, pentest externo ou pagamento real.

Resultados reais e evidências estão em `06-resultados-testes.md` e `tests/results/`. Nunca considerar “PASS” um caso pendente só porque existe código para ele.
