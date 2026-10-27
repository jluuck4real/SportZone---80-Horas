<?php
declare(strict_types=1);
namespace SportZone\Models;
final class Pedido {
    public const LABELS=['aguardando_pagamento'=>'Aguardando pagamento','pagamento_aprovado'=>'Pagamento confirmado','pagamento_recusado'=>'Pagamento recusado','em_processamento'=>'Em separação','preparando_envio'=>'Preparando envio','enviado'=>'Enviado','saiu_para_entrega'=>'Saiu para entrega','entregue'=>'Entregue','cancelado'=>'Pedido cancelado','problema_entrega'=>'Problema na entrega','revisao_pagamento'=>'Pagamento em revisão','reembolsado'=>'Reembolsado'];
    public const DELIVERY_LABELS=['pedido_realizado'=>'Pedido realizado','em_separacao'=>'Em separação','preparando_envio'=>'Preparando envio','enviado'=>'Enviado','saiu_para_entrega'=>'Saiu para entrega','entregue'=>'Entregue','cancelado'=>'Pedido cancelado','problema_entrega'=>'Problema na entrega'];
    public const TRANSITIONS = ['aguardando_pagamento'=>['pagamento_aprovado','pagamento_recusado','cancelado'], 'pagamento_aprovado'=>['em_processamento','cancelado'], 'em_processamento'=>['preparando_envio','cancelado'], 'preparando_envio'=>['enviado','cancelado'], 'enviado'=>['saiu_para_entrega','problema_entrega'], 'saiu_para_entrega'=>['entregue','problema_entrega'], 'problema_entrega'=>[], 'entregue'=>[], 'cancelado'=>[], 'pagamento_recusado'=>['aguardando_pagamento','cancelado'], 'revisao_pagamento'=>[], 'reembolsado'=>[]];
    public static function canTransition(string $from, string $to): bool { return in_array($to, self::TRANSITIONS[$from] ?? [], true); }
    public static function next(array $order): array { return $order['status']==='problema_entrega' && in_array($order['delivery_previous'],['enviado','saiu_para_entrega'],true)?[$order['delivery_previous']]:(self::TRANSITIONS[$order['status']]??[]); }
    public static function delivery(string $status,string $current): string { return ['em_processamento'=>'em_separacao','preparando_envio'=>'preparando_envio','enviado'=>'enviado','saiu_para_entrega'=>'saiu_para_entrega','entregue'=>'entregue','cancelado'=>'cancelado','problema_entrega'=>'problema_entrega'][$status]??$current; }
}
