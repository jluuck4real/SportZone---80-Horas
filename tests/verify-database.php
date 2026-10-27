<?php
declare(strict_types=1);
require dirname(__DIR__).'/config/bootstrap.php';
if(PHP_SAPI!=='cli' || getenv('SPORTZONE_TEST_MODE')!=='1')exit(1);
$db=\SportZone\Core\Database::connection();
$s=$db->prepare('SELECT password_hash FROM users WHERE email=?');$s->execute(['cliente@test.sportzone.local']);$hash=$s->fetchColumn();
if(!$hash || $hash==='TesteSeguro123!' || !password_verify('TesteSeguro123!',$hash))exit(1);
$count=(int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn();if($count!==1)exit(1);
echo "Hash e persistência verificados.\n";
