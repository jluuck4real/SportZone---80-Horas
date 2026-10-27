import {spawnSync} from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
const root=path.resolve(import.meta.dirname,'..');
const walk=dir=>fs.readdirSync(dir,{withFileTypes:true}).flatMap(e=>e.isDirectory()?walk(path.join(dir,e.name)):[path.join(dir,e.name)]);
const php=['index.php','router.php',...['app','config','scripts','tests'].flatMap(d=>walk(path.join(root,d)).filter(f=>f.endsWith('.php')))];
const js=['site.js','storefront.js','payments.js','admin.js','orders.js','product.js',...['scripts','tests'].flatMap(d=>walk(path.join(root,d)).filter(f=>f.endsWith('.mjs')))];
const results=[];
for(const file of php){const p=spawnSync('php',['-l',file],{cwd:root,encoding:'utf8'});results.push({file:path.isAbsolute(file)?path.relative(root,file):file,check:'php -l',passed:p.status===0,output:(p.stdout+p.stderr).trim()});}
for(const file of js){const p=spawnSync(process.execPath,['--check',file],{cwd:root,encoding:'utf8'});results.push({file:path.isAbsolute(file)?path.relative(root,file):file,check:'node --check',passed:p.status===0,output:(p.stdout+p.stderr).trim()});}
fs.mkdirSync(path.join(root,'tests/results'),{recursive:true});fs.writeFileSync(path.join(root,'tests/results/static.json'),JSON.stringify({at:new Date().toISOString(),results},null,2));
console.log(`${results.filter(r=>r.passed).length}/${results.length} verificações de sintaxe aprovadas (${php.length} PHP, ${js.length} JavaScript).`);
for(const r of results.filter(r=>!r.passed))console.error(r.file,r.output);
if(results.some(r=>!r.passed))process.exitCode=1;
