<?php
declare(strict_types=1);
require dirname(__DIR__).'/config/bootstrap.php';
if(PHP_SAPI!=='cli')exit(1);
echo 'PHP: '.PHP_VERSION.PHP_EOL;
echo 'Drivers PDO: '.implode(', ',PDO::getAvailableDrivers()).PHP_EOL;
echo 'Banco solicitado: '.(getenv('DB_DRIVER')?:'sqlsrv').PHP_EOL;
try{$db=\SportZone\Core\Database::connection();$count=$db->query('SELECT COUNT(*) FROM products')->fetchColumn();echo "Conexão e esquema OK. Produtos: $count\n";}
catch(Throwable $e){fwrite(STDERR,"Configuração pendente: ".$e->getMessage()."\n");exit(1);}
