<?php
declare(strict_types=1);
namespace SportZone\Services;
use SportZone\Core\{Database,Validation};
use SportZone\Repositories\Repository;
final class StockService extends Repository {
    public function variants(int $product,bool $all=false): array { return $this->query('SELECT id,product_id,size,stock,active FROM product_variants WHERE product_id=?'.($all?'':' AND active=1').' ORDER BY id',[$product])->fetchAll(); }
    public function synchronize(int $product,array $variants,?int $actor=null): void {
        // Caller holds the product lock and transaction. Removed stocked variants are never discarded.
        $current=$this->variants($product,true);$bySize=array_column($current,null,'size');$sizes=array_column($variants,'size');
        foreach($current as $v)if($v['active']&&!in_array($v['size'],$sizes,true)){
            if((int)$v['stock']>0)throw new \DomainException('Zere o estoque de '.$v['size'].' antes de remover esse tamanho.');
            $this->query('UPDATE product_variants SET active=0 WHERE id=?',[$v['id']]);
        }
        foreach($variants as $v){$old=$bySize[$v['size']]??null;$before=$old?(int)$old['stock']:0;
            if($old){$id=(int)$old['id'];$this->query('UPDATE product_variants SET stock=?,active=1 WHERE id=?',[$v['stock'],$id]);}
            else $id=$this->insert('product_variants',['product_id'=>$product,'size'=>$v['size'],'stock'=>$v['stock'],'active'=>1]);
            if($before!==$v['stock'] || !$old)$this->movement($product,$id,$v['stock']-$before,$before,$v['stock'],'product_edit',$actor,null,'Estoque definido no cadastro/edição do produto.');
        }
        $this->refreshTotal($product);
    }
    public function refreshTotal(int $product): void { $total=(int)$this->query('SELECT COALESCE(SUM(stock),0) FROM product_variants WHERE product_id=? AND active=1',[$product])->fetchColumn();$this->query('UPDATE products SET stock=? WHERE id=?',[$total,$product]); }
    private function movement(int $product,int $variant,int $delta,int $before,int $after,string $type,?int $actor,?int $order,string $note): void {
        $this->insert('stock_movements',['product_id'=>$product,'variant_id'=>$variant,'order_id'=>$order,'actor_id'=>$actor,'movement_type'=>$type,'delta'=>$delta,'stock_before'=>$before,'stock_after'=>$after,'note'=>$note]);
    }
    public function change(int $product,int $variant,int $delta,string $type,?int $actor=null,?int $order=null,string $note=''): void {
        $v=$this->query('SELECT * FROM '.Database::lock('product_variants').' WHERE id=? AND product_id=?',[$variant,$product])->fetch();
        if(!$v || ($delta<0 && !$v['active']))throw new \DomainException('Variação de produto indisponível.');
        $before=(int)$v['stock'];$after=$before+$delta;
        if($after<0 || $after>100000)throw new \DomainException('Estoque insuficiente ou acima do limite permitido.');
        if($type==='cancellation'&&!$v['active']){
            $this->query('UPDATE product_variants SET active=1 WHERE id=?',[$variant]);$sizes=(string)$this->query('SELECT sizes FROM products WHERE id=?',[$product])->fetchColumn();$list=explode(',',$sizes);if(!in_array($v['size'],$list,true)){$list[]=$v['size'];$this->query('UPDATE products SET sizes=? WHERE id=?',[implode(',',$list),$product]);}
        }
        $this->query('UPDATE product_variants SET stock=? WHERE id=?',[$after,$variant]);
        if($delta!==0)$this->movement($product,$variant,$delta,$before,$after,$type,$actor,$order,$note);
        $this->refreshTotal($product);
    }
    public function adjust(array $d,int $actor): void {
        $product=Validation::integer($d['product_id']??0);$variant=Validation::integer($d['variant_id']??0);$delta=Validation::integer($d['delta']??null,-100000,100000);$expected=Validation::integer($d['expected_stock']??null,0,100000);$note=Validation::text($d,'note',3,250);
        if($delta===0)throw new \DomainException('Informe uma entrada ou saída diferente de zero.');
        Database::transaction(function()use($product,$variant,$delta,$expected,$actor,$note){
            if(!$this->query('SELECT id FROM '.Database::lock('products').' WHERE id=?',[$product])->fetchColumn())throw new \DomainException('Produto não encontrado.');
            $stock=$this->query('SELECT stock FROM product_variants WHERE id=? AND product_id=? AND active=1',[$variant,$product])->fetchColumn();
            if($stock===false || (int)$stock!==$expected)throw new \DomainException('O estoque mudou. Recarregue antes de ajustar.');
            $this->change($product,$variant,$delta,'adjustment',$actor,null,$note);
        });
    }
}
