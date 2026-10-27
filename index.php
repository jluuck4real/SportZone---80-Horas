<?php
declare(strict_types=1);
require __DIR__.'/config/bootstrap.php';
use SportZone\Core\{Http,Security};
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:; connect-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
try {
    if($path==='/admin.html'){
        Security::start();
        if(empty($_SESSION['user_id'])){header('Location: /login.html?next=admin.html');exit;}
        Security::admin();header('Content-Type: text/html; charset=utf-8');header('Cache-Control: no-store');readfile(__DIR__.'/admin.html');exit;
    }
    if(!str_starts_with($path,'/api/')){header('Location: /index.html');exit;}
    if((int)($_SERVER['CONTENT_LENGTH']??0)>3*1024*1024)Http::fail('Requisição muito grande.',413);
    Security::start();
    (new \SportZone\Controllers\ApiController())->handle(substr($path,4),$_SERVER['REQUEST_METHOD']);
} catch(\DomainException $e){Http::fail($e->getMessage());}
catch(\PDOException $e){
    error_log('[SportZone database] '.$e->getCode());
    if(in_array((string)$e->getCode(),['23000','23505'],true))Http::fail('Operação conflita com um registro existente. Revise os dados.',409);
    Http::fail('Não foi possível acessar o banco. Verifique a configuração local e tente novamente.',503);
} catch(\Throwable $e){error_log('[SportZone] '.get_class($e).': '.$e->getMessage());Http::fail('Serviço indisponível. Consulte as instruções de instalação ou tente novamente.',503);}
