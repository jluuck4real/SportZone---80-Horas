<?php
declare(strict_types=1);
// Development router: only explicit public assets can leave the document root.
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH));
if(str_starts_with($path,'/api/')||$path==='/admin.html'){require __DIR__.'/index.php';return;}
if($path==='/')$path='/index.html';
$pages=['index','produtos','produto','lancamentos','promocoes','carrinho','contato','login','cadastro','perfil','checkout','pedidos','pedido','admin'];
$allowed=in_array(ltrim($path,'/'),array_map(fn($p)=>$p.'.html',$pages),true) || in_array($path,['/style.css','/site.js','/storefront.js','/payments.js','/admin.js','/orders.js','/product.js'],true) || preg_match('~^/assets/(?:images/[a-z0-9-]+\.svg|uploads/[a-f0-9]{32}\.(?:jpg|png|webp))$~',$path);
if(!$allowed || !is_file(__DIR__.$path)){http_response_code(404);header('Content-Type: text/plain; charset=utf-8');echo 'Página não encontrada.';return;}
$ext=pathinfo($path,PATHINFO_EXTENSION);
$types=['html'=>'text/html; charset=utf-8','css'=>'text/css; charset=utf-8','js'=>'application/javascript; charset=utf-8','svg'=>'image/svg+xml','jpg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
header('Content-Type: '.$types[$ext]);header('X-Content-Type-Options: nosniff');header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:; connect-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
readfile(__DIR__.$path);
