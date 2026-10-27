import { writeFile } from 'node:fs/promises';

const backend = new URL(process.argv[2] || process.env.SPORTZONE_BACKEND_URL);
if (backend.protocol !== 'https:' || backend.username || backend.password ||
    backend.pathname !== '/' || backend.search || backend.hash) {
  throw new Error('Informe a origem HTTPS do backend, sem caminho ou credenciais.');
}
const config = {
  $schema: 'https://openapi.vercel.sh/vercel.json',
  framework: null,
  buildCommand: 'node scripts/build-vercel.mjs',
  installCommand: 'node --version',
  outputDirectory: 'public-vercel',
  rewrites: [
    { source: '/api/:path*', destination: `${backend.origin}/api/:path*` },
    { source: '/admin.html', destination: `${backend.origin}/admin.html` },
    { source: '/assets/uploads/:path*', destination: `${backend.origin}/assets/uploads/:path*` }
  ],
  headers: [
    { source: '/(.*)', headers: [
      { key: 'X-Content-Type-Options', value: 'nosniff' },
      { key: 'X-Frame-Options', value: 'DENY' },
      { key: 'Referrer-Policy', value: 'same-origin' },
      { key: 'Content-Security-Policy', value: "default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:; connect-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'" }
    ] },
    { source: '/api/:path*', headers: [{ key: 'Cache-Control', value: 'private, no-store' }] },
    { source: '/admin.html', headers: [{ key: 'Cache-Control', value: 'private, no-store' }] }
  ]
};
await writeFile(new URL('../vercel.json', import.meta.url), JSON.stringify(config, null, 2) + '\n');
console.log('Origem HTTPS configurada em vercel.json.');
