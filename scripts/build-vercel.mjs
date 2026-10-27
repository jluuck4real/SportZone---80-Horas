import { mkdir, copyFile, readdir } from 'node:fs/promises';
import { resolve } from 'node:path';

// Only public resources are copied. PHP, databases, credentials and admin.html
// stay on the backend; administrative HTML is served after authorization.
const root = resolve(import.meta.dirname, '..');
const output = resolve(root, 'public-vercel');
await mkdir(output, { recursive: true });
const pages = ['index', 'produtos', 'produto', 'lancamentos', 'promocoes',
  'carrinho', 'contato', 'login', 'cadastro', 'perfil', 'checkout', 'pedidos', 'pedido'];
const files = [...pages.map(page => `${page}.html`), 'style.css', 'site.js',
  'storefront.js', 'payments.js', 'orders.js', 'admin.js', 'product.js'];
for (const file of files) await copyFile(resolve(root, file), resolve(output, file));
await mkdir(resolve(output, 'assets/images'), { recursive: true });
for (const file of await readdir(resolve(root, 'assets/images'))) {
  if (/^[a-z0-9-]+\.svg$/.test(file)) {
    await copyFile(resolve(root, 'assets/images', file), resolve(output, 'assets/images', file));
  }
}
console.log('SportZone: páginas e imagens públicas preparadas para a Vercel.');
