<?php
declare(strict_types=1);
// Called only by the automated suite, against its explicitly isolated database.
require dirname(__DIR__).'/config/bootstrap.php';
if(PHP_SAPI!=='cli' || getenv('SPORTZONE_TEST_MODE')!=='1')exit(1);
use SportZone\Repositories\UsuarioRepository;
$r=new UsuarioRepository();
$r->create('Admin Teste','admin@test.sportzone.local','TesteSeguro123!','admin');
echo "Administrador isolado criado.\n";
