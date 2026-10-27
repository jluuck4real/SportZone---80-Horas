<?php
declare(strict_types=1);
namespace SportZone\Services;
use SportZone\Core\Database;
use SportZone\Core\Validation;
use SportZone\Models\Pedido;
use SportZone\Repositories\Repository;
use SportZone\Repositories\CarrinhoRepository;
use SportZone\Repositories\ProdutoRepository;
use SportZone\Payments\PaymentConfig;
final class PedidoService extends Repository {
    public function checkout(int $user,array $d): int {
        $realGateway=PaymentConfig::provider()==='mercadopago';
        if($realGateway)PaymentConfig::ready();
        if($realGateway && PaymentConfig::environment()==='production' && ($d['confirm_live_payment']??false)!==true)throw new \DomainException('Confirme explicitamente a compra em produção.');
        if(!is_string($d['checkout_key']??null) || !hash_equals($_SESSION['checkout_key']??'', $d['checkout_key'])) throw new \DomainException('Recarregue o checkout para iniciar um pedido.');
        $address=Validation::address($d['address']??[]);
        if(!in_array($d['method']??'', ['pix','cartao','boleto'],true)) throw new \DomainException('Escolha uma forma de pagamento.');
        return Database::transaction(function()use($user,$d,$address){
            $prior=$this->query('SELECT id FROM orders WHERE checkout_key=? AND user_id=?',[$d['checkout_key'],$user])->fetchColumn();
            if($prior)return (int)$prior;
            $repo=new CarrinhoRepository();$cart=$repo->current();$rows=$repo->rows($cart);
            if(!$rows)throw new \DomainException('Seu carrinho está vazio.');
            foreach($rows as $row)(new ProdutoRepository())->find((int)$row['product_id'],true);
            // Product locks cover price/stock until commit. Coupon is revalidated in this transaction.
            $coupon=$_SESSION['coupon']??'';
            if($coupon!=='')$this->query('SELECT id FROM '.Database::lock('coupons').' WHERE code=?',[$coupon])->fetch();
            $summary=(new CarrinhoService())->summary($coupon,$address['cep'],true);
            foreach($summary['items'] as $item)if(!$item['available'])throw new \DomainException('Há itens indisponíveis. Revise o carrinho.');
            if(($d['expected_total_cents']??null)!==$summary['total_cents'])throw new \DomainException('O total mudou. Revise o pedido antes de confirmar.');
            $number='SZ-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(5)));
            $id=$this->insert('orders',['user_id'=>$user,'number'=>$number,'checkout_key'=>$d['checkout_key'],'status'=>'aguardando_pagamento','subtotal'=>Validation::decimal($summary['subtotal_cents']),'discount'=>Validation::decimal($summary['discount_cents']),'shipping'=>Validation::decimal($summary['shipping']['cents']),'total'=>Validation::decimal($summary['total_cents']),'coupon_code'=>$summary['coupon']?:null,'address_json'=>json_encode($address,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'delivery_days'=>$summary['shipping']['days']]);
            foreach($summary['items'] as $item){
                (new StockService())->change((int)$item['product_id'],(int)$item['variant_id'],-(int)$item['quantity'],'sale',$user,$id,'Baixa na confirmação do pedido demonstrativo.');
                $this->insert('order_items',['order_id'=>$id,'product_id'=>$item['product_id'],'variant_id'=>$item['variant_id'],'name'=>$item['name'],'size'=>$item['size'],'quantity'=>$item['quantity'],'unit_price'=>Validation::decimal($item['unit_cents'])]);
            }
            $provider=PaymentConfig::provider();$simulated=$provider==='simulation';
            $this->insert('payments',['order_id'=>$id,'method'=>$d['method'],'status'=>$simulated?'pendente':'not_created','simulated'=>$simulated?1:0,'provider'=>$provider,'environment'=>PaymentConfig::environment(),'idempotency_key'=>PaymentConfig::uuid(),'reference'=>($simulated?'SIMULACAO-':'MP-').bin2hex(random_bytes(16))]);
            $this->insert('order_events',['order_id'=>$id,'actor_id'=>$user,'status'=>'aguardando_pagamento','delivery_status'=>'pedido_realizado','note'=>$simulated?'Pedido realizado na demonstração; aguardando aprovação simulada.':'Pedido realizado; aguardando confirmação financeira.']);
            $repo->clear($cart);unset($_SESSION['coupon'],$_SESSION['cep'],$_SESSION['cart_notice']);
            return $id;
        });
    }
    public function transition(int $id,int $actor,string $target,bool $admin=false,string $note=''): void {
        if($target==='em_separacao')$target='em_processamento';
        if(mb_strlen($note)>200)throw new \DomainException('Observação deve ter até 200 caracteres.');
        Database::transaction(function()use($id,$actor,$target,$admin,$note){
            $o=$this->query('SELECT * FROM '.Database::lock('orders').' WHERE id=?',[$id])->fetch();
            if(!$o || (!$admin && (int)$o['user_id']!==$actor)) throw new \DomainException('Pedido não encontrado.');
            $payment=$this->query('SELECT * FROM payments WHERE order_id=?',[$id])->fetch();
            if(!$payment['simulated']){
                if(in_array($target,['pagamento_aprovado','cancelado','reembolsado'],true))throw new \DomainException('O estado financeiro só pode ser confirmado pela API do Mercado Pago. Use a consulta ou o cancelamento de cobrança.');
                if($payment['status']!=='approved' || !$payment['verified_at'] || $payment['needs_review'])throw new \DomainException('Pagamento sem aprovação verificável ou aguardando revisão.');
            }
            if(!$admin && !($o['status']==='aguardando_pagamento' && in_array($target,['pagamento_aprovado','cancelado'],true))) throw new \DomainException('Esta alteração exige administração.');
            if(!in_array($target,Pedido::next($o),true))throw new \DomainException('Transição de status não permitida. Siga a próxima etapa disponível.');
            if($target==='problema_entrega'&&mb_strlen(trim($note))<5)throw new \DomainException('Descreva o problema na entrega (mínimo de 5 caracteres).');
            if(in_array($target,['em_processamento','preparando_envio','enviado','saiu_para_entrega','entregue','problema_entrega'],true)&&!in_array($payment['status'],['aprovado_teste','approved'],true))throw new \DomainException('Confirme o pagamento antes de iniciar a entrega.');
            if($target==='cancelado') {
                PaymentService::releaseStock($this,$o,$actor);
                $this->query("UPDATE payments SET status='cancelado_teste' WHERE order_id=?",[$id]);
            }
            if($target==='pagamento_aprovado')$this->query("UPDATE payments SET status='aprovado_teste' WHERE order_id=? AND simulated=1",[$id]);
            if($target==='pagamento_recusado')$this->query("UPDATE payments SET status='rejected' WHERE order_id=? AND simulated=1",[$id]);
            if($target==='aguardando_pagamento')$this->query("UPDATE payments SET status='pendente' WHERE order_id=? AND simulated=1",[$id]);
            $delivery=Pedido::delivery($target,$o['delivery_status']);$previous=$target==='problema_entrega'?$o['delivery_status']:$o['delivery_previous'];
            $this->query('UPDATE orders SET status=?,delivery_status=?,delivery_previous=? WHERE id=?',[$target,$delivery,$previous,$id]);
            $message=trim($note)?:($target==='pagamento_aprovado'?'Aprovação simulada acionada explicitamente; nenhuma cobrança real.':'Acompanhamento interno atualizado pelo SportZone.');
            $this->insert('order_events',['order_id'=>$id,'actor_id'=>$actor,'status'=>$target,'previous_status'=>$o['status'],'delivery_status'=>$delivery,'note'=>$message]);
        });
    }
}
