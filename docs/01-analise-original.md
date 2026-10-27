# Análise do SportZone original — etapa 1

Referência obrigatória: `original/SportZone.zip`, cópia do ZIP fornecido. Os nove arquivos da pasta foram comparados por SHA-256 com os nove do ZIP: todos idênticos (relatório `original/integridade.txt`). Leitura integral realizada antes de alterar os arquivos de aplicação.

| Arquivo | Diagnóstico | Evolução planejada |
|---|---|---|
| index.html | Cabeçalho preto, busca, carrossel Bootstrap, faixa horizontal de produtos e texto institucional. Banners externos sem licença explicitada. | Preservar navegação, faixa e proposta institucional; criar composição própria, consultar lançamentos reais. |
| produtos.html | Dez cards estáticos, metadados para filtros; gênero, esporte e marca; tamanho/ofertas incompletos; links sem identificador. | Migrar produtos para seed SQL; consultas parametrizadas; filtros combináveis, paginação e preço. |
| produto.html | Sabrina fixa, carrossel, tamanhos sem persistência; adicionar apenas abre carrinho. | Identificação por ID, tamanho validado no servidor, adição real. |
| lancamentos.html | Doze cards, chips por modalidade, ausência de cadastro central. | Indicador de lançamento no banco, manter Futebol/Futsal/Casual. |
| promocoes.html | Oito cards com desconto de 5% meramente escrito. | Preço promocional explícito e desconto matemático real; preservar produtos. |
| carrinho.html | Apenas Sabrina fixa, quantidade local, exclusão por navegação, cupom SPORT10 demonstrativo; finalizar abre contato. | Carrinho persistido por sessão/usuário, múltiplos itens, estoque, cupom/frete calculados pelo servidor, checkout próprio. |
| contato.html | Validações de nome/e-mail/mensagem; sucesso sem requisição. | Reaproveitar validações e gravar mensagem, sem alegar envio de e-mail. |
| site.js | IIFE, normalização de acentos, filtros AND entre grupos/OR dentro do grupo, chips, legenda de carrossel, scroll da faixa e seleção de tamanho funcionais. Quantidade/preço fixos e frete incompleto. | Reutilizar normalização, chips, scroll, estados ativos; trocar cálculo local por API HTTP e renderização segura. |
| style.css | Identidade branco/preto/cinza, header, img-slot, grid-3/4, cards, sidebar, resumo. Larguras e gaps fixos; media query de 900px insuficiente para celular. | Manter CSS como base, acrescentar evolução com grid fluido, tipografia, contraste, estados, foco e movimento reduzido. |

Outros problemas: marcas inconsistentes (LeBron classificado Adidas; Phoenix como Jordan), nomes invertidos de camisetas de treino, links sem ID, imagens remotas frágeis, ausência de estados de erro/loading, rodapé e acessibilidade limitada. A importação corrige a marca dos produtos Nike e preserva categorias incluindo Vôlei e Futsal; acrescenta Corrida e Academia com itens didáticos claramente descritos.

## Arquitetura e plano

PHP 8.2 + PDO, controladores HTTP JSON, serviços transacionais, repositórios e modelos. Front-end mantém HTML/CSS/JS, Bootstrap 5 e Bootstrap Icons; páginas originais continuam com os mesmos nomes. SQL Server é o banco padrão e o alvo dos scripts. SQLite é somente alternativa explícita para testes locais; não há fallback silencioso nem localStorage. MVC com apresentação em HTML/JS consumindo API.

Ordem: (1) análise e cópia íntegra; (2) esquema e configuração; (3) modelos/repositórios/controladores; (4) autenticação; (5) catálogo; (6) carrinho; (7) frete/checkout/pagamento acadêmico; (8) pedidos; (9) administração; (10) interface responsiva; (11) documentação; (12) testes e apresentação.

Ambiente detectado: PHP 8.2.12 no XAMPP com PDO SQLite; ausência de PDO_SQLSRV, serviço SQL Server e ODBC Driver 18. Essa limitação deve constar nos resultados, sem declarar SQL Server testado até executar contra uma instância. Hospedagem Sites/Workers não executa PHP e não atende ao banco exigido; por isso o projeto será entregue para execução local PHP/SQL Server. Aplicadas as orientações de interface e prévia local da skill Sites.
