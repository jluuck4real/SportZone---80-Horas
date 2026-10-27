# Homologação SQL Server — pendente no ambiente desta entrega

O código e o esquema têm SQL Server como alvo padrão. Não havia instância/driver instalados nesta máquina, portanto nenhum resultado SQLite é apresentado como resultado SQL Server.

## Preparação segura

1. Instalar SQL Server 2019+ e driver PHP/ODBC compatíveis conforme README.
2. Criar **banco vazio dedicado**, por exemplo `SportZoneTest_Aula01`, e credencial de instalação autorizada. A suíte insere dados e cria/remove trigger de falha; não usar credencial limitada de runtime para essa homologação.
3. Configurar variáveis em um terminal, sem expor a senha em relatório/commits:

```powershell
$env:DB_DRIVER='sqlsrv'
$env:DB_SERVER='localhost,1433'
$env:DB_DATABASE='SportZoneTest_Aula01'
$env:DB_USERNAME='seu_usuario_de_instalacao'
# Defina DB_PASSWORD de forma protegida no terminal, como no exemplo do README.
$env:DB_ENCRYPT='yes'
$env:DB_TRUST_CERTIFICATE='yes' # somente certificado autoassinado no teste local
node tests/integration.mjs --sqlserver
```

A suíte exige prefixo `SportZoneTest_`, instala esquema/seed em banco vazio e não possui reset destrutivo. Gera `tests/results/sqlserver.json` com o resultado **somente após execução real**. Para repetir, crie outro banco vazio. `tests/verify-database.php` verifica hash e contagem no banco selecionado; consultas/serviços executam por PDO_SQLSRV. Os nomes de contas/credenciais de fixture não entram no banco normal.

## Casos adicionais na instância

- Executar `sql/04-consultas-e-crud.sql` e conferir invariantes vazias.
- Dois clientes com sessões distintas disputam o último item: preparar produto com estoque 1, colocar uma unidade em ambos os carrinhos e confirmar simultaneamente. Esperado: exatamente um pedido, estoque zero e erro de disponibilidade para o outro; nunca estoque negativo.
- Forçar erro em payments com a trigger da suíte e confirmar que pedido/itens/baixa são revertidos.
- Cancelar duas vezes o mesmo pedido: a segunda tentativa é recusada e estoque não duplica.
- Conferir Unicode (Vôlei, São Paulo), DECIMAL, paginação OFFSET e locks com UPDLOCK/HOLDLOCK.
- Alterar preço/estoque entre revisão e confirmação; recusar total divergente/indisponível.
- Repetir o fluxo no Apache/IIS escolhido pela escola, verificando bloqueio de `.env`, `app/`, `docs/`, `storage/` e `sql/`.

Registrar versão do servidor, versão de PDO_SQLSRV/ODBC, comandos, data e resultados. Até essa execução, esses itens permanecem **pendentes**, sem declaração de aprovação.
