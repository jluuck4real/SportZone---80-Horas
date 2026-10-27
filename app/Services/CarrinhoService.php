<?php
declare(strict_types=1);
namespace SportZone\Services;
use SportZone\Repositories\CarrinhoRepository;
use SportZone\Repositories\Repository;
use SportZone\Core\Validation;
final class CarrinhoService extends Repository {
    public function summary(string $coupon='',string $cep='',bool $strict=false): array {
        $repo=new CarrinhoRepository(); $items=$repo->rows($repo->current()); $subtotal=0; $count=0; $totals=[];
        foreach ($items as &$item) {
            $qty=(int)$item['quantity']; $unit=Validation::money($item['sale_price']??$item['price']);
            $item['unit_cents']=$unit; $item['line_cents']=$unit*$qty;
            $totals[$item['product_id']]=($totals[$item['product_id']]??0)+$qty;
            $item['available']=(bool)$item['active'] && (bool)$item['category_active'] && (bool)$item['variant_active'] && $qty<=(int)$item['variant_stock'] && $totals[$item['product_id']]<=(int)$item['stock'] && in_array($item['size'],explode(',',$item['sizes']),true);
            $subtotal+=$item['line_cents']; $count+=$qty;
        } unset($item);
        foreach ($items as &$item) $item['available']=$item['available'] && $totals[$item['product_id']]<=(int)$item['stock']; unset($item);
        $discount=0; $coupon=strtoupper(trim($coupon));
        if ($coupon!=='') {
            $c=$this->query('SELECT * FROM coupons WHERE code=? AND active=1 AND expires_at>?',[$coupon,gmdate('Y-m-d H:i:s')])->fetch();
            if (!$c || $subtotal<Validation::money($c['minimum'])) {
                if ($strict) throw new \DomainException('Cupom inválido, expirado ou valor mínimo não atingido. SPORT10: mínimo R$ 100.');
                $coupon='';
            } else $discount=intdiv($subtotal*(int)$c['percent_off']+50,100);
        }
        $shipping=$cep!==''?FreteService::quote($cep,$subtotal-$discount):null;
        if($shipping && $items && !array_filter($items,fn($i)=>!$i['is_test'])){$shipping['cents']=0;$shipping['days']='Sem entrega física · produto de teste';$shipping['test_only']=true;}
        return ['items'=>$items,'count'=>$count,'subtotal_cents'=>$subtotal,'discount_cents'=>$discount,'shipping'=>$shipping,'total_cents'=>$subtotal-$discount+($shipping['cents']??0),'coupon'=>$coupon,'notice'=>$_SESSION['cart_notice']??null];
    }
}
