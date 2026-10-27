import { chromium } from '@playwright/test';
import assert from 'node:assert/strict';
import { mkdir, writeFile } from 'node:fs/promises';

const base = process.argv[2] || 'https://sportzone-lac.vercel.app';
const results = [];
const browser = await chromium.launch({ channel: 'msedge', headless: true });
const context = await browser.newContext();
const page = await context.newPage();
const errors = [];
page.on('pageerror', error => errors.push(error.message));
async function test(name, action) {
  try { await action(); results.push({ name, passed: true }); console.log('PASS', name); }
  catch (error) { results.push({ name, passed: false, error: error.message }); console.error('FAIL', name, error.message); }
}
try {
  await test('Home pública, produtos e rodapé atualizado', async () => {
    const response = await page.goto(base);
    assert.equal(response.status(), 200);
    await page.locator('.product-card').first().waitFor({ timeout: 30000 });
    assert(!(await page.locator('footer').textContent()).includes('Programação Web II'));
    assert(await page.locator('.carousel-track').count() > 0);
  });
  await test('API HTTPS e cookie de sessão seguro', async () => {
    const response = await context.request.get(base + '/api/session');
    assert.equal(response.status(), 200);
    const session = await response.json();
    assert(session.csrf);
    assert.equal(session.simulated_payments, true);
    const cookies = await context.cookies();
    const cookie = cookies.find(cookie => cookie.name === 'sportzone_session');
    assert(cookie?.secure && cookie?.httpOnly);
  });
  await test('Carrinho persiste na mesma sessão; preço calculado no servidor', async () => {
    const session = await (await context.request.get(base + '/api/session')).json();
    const catalog = await (await context.request.get(base + '/api/catalog')).json();
    const item = catalog.items.find(item => Number(item.stock) > 0);
    assert(item, 'Produto disponível não encontrado');
    const product = await (await context.request.get(base + '/api/product?id=' + item.id)).json();
    const variant = product.variants.find(variant => Number(variant.active) === 1 && Number(variant.stock) > 0);
    assert(variant);
    const response = await context.request.post(base + '/api/cart/item', {
      headers: { 'X-CSRF-Token': session.csrf },
      data: { product_id: product.id, size: variant.size, quantity: 1, mode: 'add', price: 0.01 }
    });
    assert.equal(response.status(), 200);
    const cart = await (await context.request.get(base + '/api/cart')).json();
    assert(cart.items.some(item => Number(item.product_id) === Number(product.id)));
    assert(cart.total_cents >= 100);
    const clear = await context.request.post(base + '/api/cart/clear', {
      headers: { 'X-CSRF-Token': session.csrf }, data: {}
    });
    assert.equal(clear.status(), 200);
  });
  await test('CSRF e autorização administrativa', async () => {
    const response = await context.request.post(base + '/api/cart/clear', { data: {} });
    assert.equal(response.status(), 419);
    const dashboard = await context.request.get(base + '/api/admin/dashboard');
    assert.equal(dashboard.status(), 401);
    const admin = await context.request.get(base + '/admin.html', { maxRedirects: 0 });
    assert.equal(admin.status(), 302);
    assert(admin.headers().location.includes('login.html'));
  });
  await test('Arquivos privados não são publicados', async () => {
    for (const path of ['/storage/sportzone.sqlite', '/config/bootstrap.php', '/.env', '/app/Core/Database.php']) {
      const response = await context.request.get(base + path);
      assert([403, 404].includes(response.status()), path + ': ' + response.status());
    }
  });
  await test('Home e catálogo no celular e desktop', async () => {
    for (const width of [390, 1440]) {
      await page.setViewportSize({ width, height: 900 });
      for (const path of ['/', '/produtos.html']) {
        await page.goto(base + path);
        await page.locator('.product-card').first().waitFor();
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
      }
    }
    assert.deepEqual(errors, []);
  });
} finally {
  await browser.close();
  await mkdir('tests/results', { recursive: true });
  await writeFile('tests/results/deployment.json', JSON.stringify({ base, at: new Date().toISOString(), results }, null, 2));
}
if (results.some(result => !result.passed)) process.exitCode = 1;
