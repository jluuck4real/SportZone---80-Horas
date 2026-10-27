import fs from 'node:fs';
import path from 'node:path';
const root=path.resolve(import.meta.dirname,'..');
const catalog=new Map();
for(const file of ['produtos.html','lancamentos.html','promocoes.html']) {
 const html=fs.readFileSync(path.join(root,'docs/original',file),'utf8');
 for(const match of html.matchAll(/<article class="product-card"([^>]*)>([\s\S]*?)<\/article>/g)) {
  const attrs=Object.fromEntries([...match[1].matchAll(/data-([\w-]+)="([^"]*)"/g)].map(m=>[m[1],m[2]]));
  const image=match[2].match(/<img src="([^"]+)"/)[1];
  const price=match[2].match(/R\$([\d.,]+)/)[1].replaceAll('.','').replace(',','.');
  const existing=catalog.get(attrs.name);
  const item=existing||{name:attrs.name,category:attrs.category,brand:attrs.name.includes('Jordan')?'Jordan':'Nike',gender:attrs.gender,price,image_original:image,stock:20,sizes:/Camis|Jersey|Shorts|Calça/.test(attrs.name)?'P,M,G,GG':attrs.name.startsWith('Bola')?'Único':'34,35,36,37,38,39,40,41,42,43,44',is_new:0,active:1};
  if(file==='lancamentos.html')item.is_new=1;
  if(file==='promocoes.html') item.sale_price=(Math.round(Number(item.price)*95)/100).toFixed(2);
  catalog.set(attrs.name,item);
 }
}
for(const item of [{name:'Camiseta SportZone Training',category:'academia',price:'89.90',sizes:'P,M,G,GG'},{name:'Tênis SportZone Run',category:'corrida',price:'249.90',sizes:'36,37,38,39,40,41,42,43,44'}]) catalog.set(item.name,{...item,brand:'SportZone',gender:'unissex',stock:30,is_new:1,active:1});
const products=[...catalog.values()].map(p=>({...p,image:`assets/images/${p.name.startsWith('Bola')?'bola':p.name.startsWith('Shorts')?'shorts':p.name.startsWith('Calça')?'calca':p.category}.svg`,description:`${p.name}. Produto do catálogo acadêmico SportZone para ${p.category}. Estoque e valores ilustrativos para atividades escolares. Confira o tamanho antes de adicionar ao carrinho.`}));
fs.mkdirSync(path.join(root,'data'),{recursive:true});
fs.writeFileSync(path.join(root,'data/products.json'),JSON.stringify(products,null,2)+'\n');
const categories=[['Futebol','futebol'],['Basquete','basquete'],['Futsal','futsal'],['Vôlei','volei'],['Casual','casual'],['Corrida','corrida'],['Academia','academia']];
const quote=v=>v===null?'NULL':typeof v==='number'?String(v):"N'"+String(v).replaceAll("'","''")+"'";
let sql='-- Gerado a partir do ZIP original; imagens locais próprias. Sem usuários/senhas padrão.\n';
for(const [name,slug]of categories)sql+=`INSERT INTO categories(name,slug) VALUES (${quote(name)},${quote(slug)});\n`;
for(const p of products){const cols=['name','description','brand','gender','price','sale_price','image','stock','sizes','is_new','active'];sql+=`INSERT INTO products(category_id,${cols.join(',')}) SELECT id,${cols.map(k=>quote(p[k]??null)).join(',')} FROM categories WHERE slug=${quote(p.category)};\n`;}
sql+="INSERT INTO coupons(code,percent_off,minimum,expires_at,active) VALUES ('SPORT10',10,100.00,'2099-12-31T23:59:59',1);\n";
fs.writeFileSync(path.join(root,'sql/03-seed.sql'),sql);
console.log(`Importados ${products.length} produtos; categorias originais preservadas.`);
