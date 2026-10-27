<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';
if (PHP_SAPI !== 'cli') exit(1);
use SportZone\Core\Validation;
use SportZone\Repositories\UsuarioRepository;
$password = getenv('SPORTZONE_ADMIN_PASSWORD') ?: '';
if (strlen($password)<10 || strlen($password)>72) { fwrite(STDERR,"Use scripts/setup-admin.ps1 ou defina SPORTZONE_ADMIN_PASSWORD (10 a 72 bytes).\n"); exit(1); }
$name = readline('Nome do administrador: ');
$email = readline('E-mail: ');
try {
    $repo=new UsuarioRepository();$validated=Validation::email(['email'=>$email]);
    if($repo->byEmail($validated))throw new DomainException('Este e-mail já possui cadastro. Nenhuma senha ou permissão foi alterada.');
    $id=$repo->create(Validation::text(['name'=>$name],'name',3,120),$validated,$password,'admin');
    echo "Administrador $id criado com senha protegida por hash. Acesse login.html e depois admin.html.\n";
}catch(DomainException $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
catch(Throwable){fwrite(STDERR,"Não foi possível criar o administrador. Verifique a conexão e a instalação do banco.\n");exit(1);}
