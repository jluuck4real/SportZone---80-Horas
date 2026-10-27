<?php
declare(strict_types=1);
namespace SportZone\Services;
use SportZone\Core\Validation;
use SportZone\Payments\PaymentConfig;
use SportZone\Repositories\Repository;
final class PaymentService extends Repository {
    public function options(int $total): array {
        return ['config'=>PaymentConfig::publicData(),'methods'=>array_fill_keys(['pix','cartao','boleto'],['available'=>true,'minimum_cents'=>null,'ids'=>[]])];
    }
    public function view(int $order,?int $user=null): array {
        $p=$this->query('SELECT p.*,o.total FROM payments p JOIN orders o ON o.id=p.order_id WHERE p.order_id=?'.($user!==null?' AND o.user_id=?':''),$user!==null?[$order,$user]:[$order])->fetch();
        if(!$p)throw new \DomainException('Pedido não encontrado.');
        return ['provider'=>$p['provider'],'environment'=>$p['environment'],'method'=>$p['method'],'status'=>$p['status'],'reference'=>$p['reference'],'verified_at'=>null,'needs_review'=>false,'total_cents'=>Validation::money($p['total']),'transactions'=>[]];
    }
    public static function releaseStock(Repository $repo,array $order,?int $actor=null): void {
        if($order['stock_released'])return;
        foreach($repo->query('SELECT product_id,variant_id,quantity FROM order_items WHERE order_id=? ORDER BY product_id,variant_id',[$order['id']])->fetchAll() as $item){
            $repo->query('SELECT id FROM '.\SportZone\Core\Database::lock('products').' WHERE id=?',[(int)$item['product_id']])->fetch();
            (new StockService())->change((int)$item['product_id'],(int)$item['variant_id'],(int)$item['quantity'],'cancellation',$actor,(int)$order['id'],'Devolução única de estoque após cancelamento do pedido.');
        }
        $repo->query('UPDATE orders SET stock_released=1 WHERE id=?',[$order['id']]);
    }
}
