<?php
declare(strict_types=1);
namespace SportZone\Repositories;
use SportZone\Core\Validation;
use SportZone\Models\Pedido;
final class PedidoRepository extends Repository {
    public function page(array $filters=[],?int $user=null): array {
        $where=['1=1'];$params=[];
        foreach($filters as $value)if(!is_scalar($value))throw new \DomainException('Filtro de pedido inválido.');
        if($user!==null){$where[]='o.user_id=?';$params[]=$user;}
        foreach(['status'=>'o.status','payment_status'=>'p.status','delivery_status'=>'o.delivery_status'] as $key=>$column)if(!empty($filters[$key])){$where[]="$column=?";$params[]=mb_substr((string)$filters[$key],0,40);}
        if(!empty($filters['q'])){$where[]='(LOWER(o.number) LIKE ? OR LOWER(u.name) LIKE ? OR LOWER(u.email) LIKE ?)';$q='%'.mb_strtolower(mb_substr((string)$filters['q'],0,180)).'%';array_push($params,$q,$q,$q);}
        foreach(['date_from'=>'>=','date_to'=>'<'] as $key=>$operator)if(!empty($filters[$key])){
            $value=(string)$filters[$key];$dt=\DateTimeImmutable::createFromFormat('!Y-m-d',$value,new \DateTimeZone('America/Sao_Paulo'));
            if(!$dt||$dt->format('Y-m-d')!==$value)throw new \DomainException('Data de filtro inválida.');
            if($key==='date_to')$dt=$dt->modify('+1 day');$where[]="o.created_at $operator ?";$params[]=$dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        }
        $from=' FROM orders o JOIN payments p ON p.order_id=o.id JOIN users u ON u.id=o.user_id WHERE '.implode(' AND ',$where);
        $total=(int)$this->query('SELECT COUNT(*)'.$from,$params)->fetchColumn();$page=Validation::integer($filters['page']??1,1,100000);$offset=($page-1)*20;
        $paging=$this->db->getAttribute(\PDO::ATTR_DRIVER_NAME)==='sqlsrv'?" OFFSET $offset ROWS FETCH NEXT 20 ROWS ONLY":" LIMIT 20 OFFSET $offset";
        $items=$this->query('SELECT o.id,o.number,o.user_id,o.status,o.delivery_status,o.created_at,o.total,u.name AS customer_name,u.email AS customer_email,p.method,p.status AS payment_status,p.simulated,p.environment,(SELECT COALESCE(SUM(i.quantity),0) FROM order_items i WHERE i.order_id=o.id) AS item_count'.$from.' ORDER BY o.id DESC'.$paging,$params)->fetchAll();
        return ['items'=>$items,'total'=>$total,'page'=>$page,'pages'=>(int)ceil($total/20)];
    }
    public function list(?int $user=null,string $status=''): array { return $this->page($status!==''?['status'=>$status]:[],$user)['items']; }
    public function find(int $id,?int $user=null): ?array {
        $params=[$id];$where='o.id=?';if($user!==null){$where.=' AND o.user_id=?';$params[]=$user;}
        $order=$this->query("SELECT o.*,u.name AS customer_name,u.email AS customer_email,p.method,p.status AS payment_status,p.simulated,p.reference,p.provider,p.environment,p.verified_at,p.remote_id,p.needs_review FROM orders o JOIN payments p ON p.order_id=o.id JOIN users u ON u.id=o.user_id WHERE $where",$params)->fetch();
        if(!$order)return null;
        $order['items']=$this->query('SELECT * FROM order_items WHERE order_id=? ORDER BY id',[$id])->fetchAll();
        $order['events']=$this->query('SELECT e.status,e.previous_status,e.delivery_status,e.note,e.created_at,u.name AS actor_name,u.role AS actor_role FROM order_events e JOIN users u ON u.id=e.actor_id WHERE e.order_id=? ORDER BY e.id',[$id])->fetchAll();
        $order['address']=json_decode($order['address_json'],true);
        $order['payment']=(new \SportZone\Services\PaymentService())->view($id,$user);
        $order['next_statuses']=Pedido::next($order);
        unset($order['checkout_key']);return $order;
    }
}
