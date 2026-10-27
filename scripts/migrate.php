<?php
declare(strict_types=1);
require dirname(__DIR__).'/config/bootstrap.php';
if(PHP_SAPI!=='cli')exit(1);
\SportZone\Core\SchemaUpgrade::run();
echo "Atualização SportZone 3.0 concluída: catálogo, variações, estoque, entrega e configurações. Nenhuma cobrança foi criada.\n";
