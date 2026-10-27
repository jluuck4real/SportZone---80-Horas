import fs from 'node:fs';
import path from 'node:path';
const root=path.resolve(import.meta.dirname,'..');
const read=name=>JSON.parse(fs.readFileSync(path.join(root,'tests/results',name+'.json'),'utf8'));
const integration=read('integration'),v3=read('v3'),browser=read('browser'),statics=read('static');
const count=s=>`${s.results.filter(x=>x.passed).length}/${s.results.length}`;
const local=d=>new Date(d).toLocaleString('pt-BR',{timeZone:'America/Sao_Paulo'});
const browserSource=fs.readFileSync(path.join(root,'tests/browser.mjs'),'utf8');
const urls=[...(browserSource.match(/for\(const url of \[([^\]]+)\]/)?.[1]||'').matchAll(/'([^']+)'/g)].map(m=>m[1]);
const widths=browser.results.filter(r=>r.name.startsWith('Responsividade')&&r.passed).length;
let lines=['# Resultados reais dos testes — SportZone 3.0','',`Execuções em America/Sao_Paulo: integração ${local(integration.at)}; administração/integridade ${local(v3.at)}; navegador ${local(browser.at)}.`,'','| Verificação | Resultado executado | Ambiente |','|---|---|---|',`| Regressão HTTP/CLI | **${count(integration)} aprovados** | PHP 8.2.12 + PDO SQLite isolado |`,`| Administração, estoque, pedidos e migração V3 | **${count(v3)} aprovados** | SQLite isolado, incluindo dois processos PHP concorrentes |`,`| Navegador | **${count(browser)} aprovados** | Edge / Playwright, banco SQLite isolado |`,`| Sintaxe | **${count(statics)} aprovadas** | php -l e node --check |`,'| SQL Server / PDO_SQLSRV | **Não executado — pendente** | Instância e driver ausentes |','| Pagamento real | **Não executado, fora do escopo** | Somente demonstração explícita |','',`Os ${widths} testes de largura passaram por ${urls.length} páginas/visões, totalizando ${widths*urls.length} combinações. Isso verifica rolagem horizontal geral; tabelas e carrosséis têm rolagem própria. Não é teste de carga nem certificação WCAG.`,'','Relatórios detalhados: tests/results/integration.json, v3.json, browser.json e static.json.'];
for(const[title,suite]of [['Regressão HTTP/CLI',integration],['Administração e integridade V3',v3],['Navegador',browser]]){lines.push('','## '+title,'','| Nº | Caso executado | Resultado |','|---|---|---|');suite.results.forEach((r,i)=>lines.push(`| ${i+1} | ${r.name} | ${r.passed?'PASS':'FAIL'} |`));}
lines.push('','## Evidências visuais','',...['home-desktop.png','home-mobile.png','admin-desktop.png','estoque-desktop.png','entrega-admin-desktop.png','entrega-cliente-mobile.png','pedido-desktop.png'].map(name=>'- tests/results/'+name),'','## Integridade e limitações','',
'- O teste de falha no INSERT de pagamento confirma rollback de pedido, estoque e movimentações; o erro SQLSTATE 23000/409 é esperado nesse caso SQLite.',
'- Dois processos PHP iniciados no mesmo instante disputaram a última unidade: exatamente um pedido foi criado e não houve estoque negativo. Este resultado é de SQLite; não comprova os locks do SQL Server.',
'- A migração foi executada sobre banco legado com usuário/pedido enviado e repetida sobre banco atual. Contas, pedidos, estoque, descrição editada e histórico foram preservados; foreign_key_check não apontou falhas.',
'- Foram verificados isolamento entre clientes, acesso 403 ao HTML/admin APIs, CSRF, SQL Injection, XSS armazenado, aprovação demonstrativa, recusa, cancelamento único e transições logísticas inválidas.',
'- Diagramas e documentação descrevem os dados e as classes presentes; os arquivos editáveis foram entregues, sem alegar renderização visual de UML não executada.',
'- Pendentes: SQL Server (esquema/migração/Unicode/DECIMAL/locks), Apache/IIS, aparelho físico, medição de desempenho e auditoria com leitor de tela.',
'- Pagamentos e entregas são demonstrativos; não houve cobrança, aprovação financeira ou rastreamento real de transportadora.');
fs.writeFileSync(path.join(root,'docs/06-resultados-testes.md'),lines.join('\n')+'\n');
if(browser.results.every(r=>r.passed)){const fail=path.join(root,'tests/results/browser-failure.png');if(fs.existsSync(fail))fs.unlinkSync(fail);}
console.log(`Relatório: regressão ${count(integration)}, V3 ${count(v3)}, navegador ${count(browser)}, sintaxe ${count(statics)}. SQL Server pendente.`);
