<?php
declare(strict_types=1);
require dirname(__DIR__).'/config/bootstrap.php';
if(PHP_SAPI!=='cli'||getenv('SPORTZONE_TEST_MODE')!=='1')exit(1);
$d=json_decode(stream_get_contents(STDIN),true,32,JSON_THROW_ON_ERROR);
$_SESSION=['user_id'=>$d['user'],'checkout_key'=>$d['key'],'cart_token'=>bin2hex(random_bytes(32))];
// The parent starts both CLI workers with the same start instant. No external payment is involved.
$deadline=(float)$d['start'];while(microtime(true)<$deadline)usleep(1000);
try{$id=(new \SportZone\Services\PedidoService())->checkout((int)$d['user'],['checkout_key'=>$d['key'],'address'=>$d['address'],'method'=>'pix','expected_total_cents'=>$d['total']]);echo json_encode(['ok'=>true,'id'=>$id]);}
catch(Throwable $e){echo json_encode(['ok'=>false,'kind'=>get_class($e)]);}
