import fs from 'node:fs';
import path from 'node:path';
const root=path.resolve(import.meta.dirname,'..');
const schema=fs.readFileSync(path.join(root,'sql/02-schema.sql'),'utf8');
let md='# Dicionário de dados — SQL Server\n\nGerado a partir de `sql/02-schema.sql`. `id` é PK IDENTITY; datas são UTC. Valores monetários são DECIMAL(12,2). As restrições abaixo são as efetivamente declaradas.\n\n';
const descriptions={users:'Contas, hash de senha e perfil de autorização.',addresses:'Endereços do usuário; cada consulta exige proprietário.',categories:'Modalidades e status de publicação.',products:'Catálogo central; stock é a soma sincronizada das variações ativas.',carts:'Carrinho anônimo por token ou de usuário por identificação.',cart_items:'Seleção de produto/tamanho e quantidade, sem preço confiado ao cliente.',coupons:'Desconto percentual por código, mínimo e validade UTC.',orders:'Cabeçalho com totais, situação geral, etapa logística e snapshots históricos.',order_items:'Itens históricos com preço congelado; variant_id é acrescentado pela migração ou pelo script SQL 06.',payments:'Somente pagamentos simulados ativos; campos externos reservados não habilitam cobranças.',contact_messages:'Mensagens recebidas pelo formulário, sem envio de e-mail.',order_events:'Auditoria de situação, etapa logística, estado anterior, autor e observação.',login_attempts:'Limite de tentativas por hash do IP+e-mail; não armazena senha.',payment_transactions:'Reserva histórica de análise de integração; não utilizada para cobranças nesta demonstração.',webhook_events:'Reserva de análise; não há webhook financeiro ativo.',product_variants:'Estoque individual por produto e tamanho; quantidade não negativa.',stock_movements:'Entradas, saídas, edição, venda e cancelamento, com saldo anterior/posterior e responsável.',product_images:'Galeria adicional validada; até seis imagens pela regra de aplicação.',store_settings:'Preferências públicas da loja e limiar de estoque baixo; não contém credenciais.'};
for(const m of schema.matchAll(/CREATE TABLE (\w+) \(\s*([\s\S]*?)\n\);/g)) {
 md+=`## ${m[1]}\n\n${descriptions[m[1]]}\n\n| Campo | Tipo | Nulabilidade e regras SQL |\n|---|---|---|\n`;
 const constraints=[];
 for(let line of m[2].split('\n')){line=line.trim().replace(/,$/,'');if(!line)continue;if(/^(CHECK|UNIQUE)/.test(line)){constraints.push(line);continue;}const c=line.match(/^(\w+)\s+((?:NVARCHAR|VARCHAR|CHAR|DECIMAL)\([\d,]+\)|\w+)(.*)$/);if(c)md+=`| ${c[1]} | ${c[2]} | ${c[3].trim().replaceAll('|','\\|')||'—'} |\n`;}
 if(constraints.length)md+='\nRestrições compostas: '+constraints.map(c=>'`'+c+'`').join('; ')+'.\n';md+='\n';
}
md+='## Índices adicionais\n\n```sql\n'+[...schema.matchAll(/CREATE (?:UNIQUE )?INDEX[^;]+;/g)].map(m=>m[0]).join('\n')+'\n```\n';
fs.writeFileSync(path.join(root,'docs/dicionario-de-dados.md'),md);
console.log('Dicionário sincronizado com o esquema.');
