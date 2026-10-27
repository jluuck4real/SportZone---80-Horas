<?php
declare(strict_types=1);
namespace SportZone\Repositories;
use SportZone\Core\Database;
final class ProdutoRepository extends Repository {
    public function find(int $id,bool $lock=false): ?array {
        $table = $lock ? Database::lock('products p') : 'products p';
        $product=$this->query("SELECT p.*,c.name AS category,c.slug AS category_slug FROM $table JOIN categories c ON c.id=p.category_id WHERE p.id=?",[$id])->fetch();
        if(!$product)return null;
        $product['variants']=(new \SportZone\Services\StockService())->variants($id,true);
        $product['gallery']=$this->query('SELECT path FROM product_images WHERE product_id=? ORDER BY sort_order,id',[$id])->fetchAll(\PDO::FETCH_COLUMN);
        return $product;
    }
    public function catalog(array $f,bool $admin=false): array {
        foreach (['q','category','brand','gender','size','new','sale','sort','page','test','featured','availability','active'] as $key) {
            if (isset($f[$key]) && !is_scalar($f[$key])) throw new \DomainException('Filtro inválido.');
        }
        $where = [$admin?'1=1':'p.active=1 AND c.active=1']; $params=[];
        if(!$admin)$where[]=!empty($f['test'])?'p.is_test=1':'p.is_test=0';
        if(!empty($f['featured']))$where[]='p.featured=1';
        if(($f['availability']??'')==='out')$where[]='p.stock=0';
        if(($f['availability']??'')==='available')$where[]='p.stock>0';
        if(($f['availability']??'')==='low'){$where[]='p.stock BETWEEN 1 AND ?';$params[]=(int)(new SettingsRepository())->get()['low_stock_threshold'];}
        if($admin && isset($f['active']) && in_array((string)$f['active'],['0','1'],true)){$where[]='p.active=?';$params[]=(int)$f['active'];}
        if (!empty($f['q'])) { $where[]='LOWER(p.name) LIKE ?'; $params[]='%'.mb_strtolower(mb_substr((string)$f['q'],0,160)).'%'; }
        foreach (['category'=>'c.slug','brand'=>'p.brand','gender'=>'p.gender'] as $key=>$column) {
            if (!empty($f[$key])) { $values=explode(',',mb_substr((string)$f[$key],0,300)); $where[]="$column IN (".implode(',',array_fill(0,count($values),'?')).')'; array_push($params,...$values); }
        }
        if (!empty($f['size'])) { $where[]="(',' + p.sizes + ',') LIKE ?"; if ($this->db->getAttribute(\PDO::ATTR_DRIVER_NAME)==='sqlite') $where[count($where)-1]="(',' || p.sizes || ',') LIKE ?"; $params[]='%,'.mb_substr((string)$f['size'],0,20).',%'; }
        if (!empty($f['new'])) $where[]='p.is_new=1';
        if (!empty($f['sale'])) $where[]='p.sale_price IS NOT NULL';
        $sort = ['price_asc'=>'COALESCE(p.sale_price,p.price) ASC,p.id ASC','price_desc'=>'COALESCE(p.sale_price,p.price) DESC,p.id DESC','name'=>'p.name ASC,p.id ASC'][$f['sort']??''] ?? 'p.is_new DESC,p.id DESC';
        $condition=implode(' AND ',$where);
        $total=(int)$this->query("SELECT COUNT(*) FROM products p JOIN categories c ON c.id=p.category_id WHERE $condition",$params)->fetchColumn();
        $page=max(1,min(10000,(int)($f['page']??1))); $offset=($page-1)*24;
        $paging=$this->db->getAttribute(\PDO::ATTR_DRIVER_NAME)==='sqlsrv' ? " OFFSET $offset ROWS FETCH NEXT 24 ROWS ONLY" : " LIMIT 24 OFFSET $offset";
        $items=$this->query("SELECT p.*,c.name AS category,c.slug AS category_slug FROM products p JOIN categories c ON c.id=p.category_id WHERE $condition ORDER BY $sort$paging",$params)->fetchAll();
        if($items){$ids=array_map(fn($p)=>(int)$p['id'],$items);$rows=$this->query('SELECT id,product_id,size,stock,active FROM product_variants WHERE product_id IN ('.implode(',',array_fill(0,count($ids),'?')).') ORDER BY id',$ids)->fetchAll();foreach($items as &$p)$p['variants']=array_values(array_filter($rows,fn($v)=>(int)$v['product_id']===(int)$p['id']));unset($p);}
        return ['items'=>$items,'total'=>$total,'page'=>$page,'pages'=>(int)ceil($total/24)];
    }
    public function categories(bool $all=false): array { return $this->query('SELECT * FROM categories'.($all?'':' WHERE active=1').' ORDER BY name')->fetchAll(); }
    public function brands(): array { return $this->query('SELECT DISTINCT brand FROM products WHERE active=1 ORDER BY brand')->fetchAll(\PDO::FETCH_COLUMN); }
    public function save(array $data,?int $id): int {
        if (!$id) return $this->insert('products',$data);
        if (!$this->find($id)) throw new \DomainException('Produto não encontrado.');
        $sets=implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));
        $this->query("UPDATE products SET $sets WHERE id=?",[...array_values($data),$id]); return $id;
    }
    public function deactivate(int $id): void { $this->query('UPDATE products SET active=0 WHERE id=?',[$id]); }
}
