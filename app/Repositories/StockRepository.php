<?php
declare(strict_types=1);
namespace SportZone\Repositories;
use SportZone\Core\Validation;
final class StockRepository extends Repository {
    public function page(array $filters=[]): array {
        foreach($filters as $v)if(!is_scalar($v))throw new \DomainException('Filtro de estoque inválido.');
        $threshold=(int)(new SettingsRepository())->get()['low_stock_threshold'];$where=['v.active=1'];$params=[];
        if(!empty($filters['q'])){$where[]='LOWER(p.name) LIKE ?';$params[]='%'.mb_strtolower(mb_substr((string)$filters['q'],0,160)).'%';}
        if(($filters['availability']??'')==='out')$where[]='v.stock=0';
        if(($filters['availability']??'')==='low'){$where[]='v.stock BETWEEN 1 AND ?';$params[]=$threshold;}
        if(($filters['availability']??'')==='available')$where[]='v.stock>0';
        $from=' FROM product_variants v JOIN products p ON p.id=v.product_id WHERE '.implode(' AND ',$where);
        $total=(int)$this->query('SELECT COUNT(*)'.$from,$params)->fetchColumn();$page=Validation::integer($filters['page']??1,1,100000);$offset=($page-1)*20;$paging=$this->db->getAttribute(\PDO::ATTR_DRIVER_NAME)==='sqlsrv'?" OFFSET $offset ROWS FETCH NEXT 20 ROWS ONLY":" LIMIT 20 OFFSET $offset";
        $items=$this->query('SELECT v.id,v.product_id,v.size,v.stock,p.name,p.active,p.image'.$from.' ORDER BY v.stock,p.id,v.id'.$paging,$params)->fetchAll();
        return ['items'=>$items,'total'=>$total,'page'=>$page,'pages'=>(int)ceil($total/20),'threshold'=>$threshold];
    }
    public function movements(array $filters=[]): array {
        $where=[];$params=[];foreach(['product_id','variant_id'] as $key)if(!empty($filters[$key])){$where[]="m.$key=?";$params[]=Validation::integer($filters[$key]);}
        $sql='SELECT m.*,p.name AS product_name,v.size,u.name AS actor_name,o.number AS order_number FROM stock_movements m JOIN products p ON p.id=m.product_id JOIN product_variants v ON v.id=m.variant_id LEFT JOIN users u ON u.id=m.actor_id LEFT JOIN orders o ON o.id=m.order_id'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY m.id DESC';
        $sql.=$this->db->getAttribute(\PDO::ATTR_DRIVER_NAME)==='sqlsrv'?' OFFSET 0 ROWS FETCH NEXT 50 ROWS ONLY':' LIMIT 50';return $this->query($sql,$params)->fetchAll();
    }
}
