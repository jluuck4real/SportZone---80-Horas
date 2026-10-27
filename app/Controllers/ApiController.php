<?php
declare(strict_types=1);
namespace SportZone\Controllers;
use SportZone\Core\{Http,Security,Validation,Database};
use SportZone\Repositories\{UsuarioRepository,ProdutoRepository,CarrinhoRepository,PedidoRepository,Repository,StockRepository,DashboardRepository,SettingsRepository};
use SportZone\Services\{UsuarioService,CarrinhoService,PedidoService,ProdutoService,PaymentService};
final class ApiController extends Repository {
    public function handle(string $path,string $method): never {
        if($method!=='GET'){ Security::csrf(); $d=Http::input(); } else $d=[];
        if($path==='/session' && $method==='GET') {
            $_SESSION['checkout_key']??=bin2hex(random_bytes(32));
            Http::json(['csrf'=>$_SESSION['csrf'],'user'=>isset($_SESSION['user_id'])?(new UsuarioRepository())->publicById((int)$_SESSION['user_id']):null,'checkout_key'=>$_SESSION['checkout_key'],'database'=>Database::connection()->getAttribute(\PDO::ATTR_DRIVER_NAME),'simulated_payments'=>\SportZone\Payments\PaymentConfig::provider()==='simulation','payments'=>\SportZone\Payments\PaymentConfig::publicData()]);
        }
        if($path==='/register' && $method==='POST') Http::json(['user'=>(new UsuarioService())->register($d)],201);
        if($path==='/login' && $method==='POST')Http::json(['user'=>(new UsuarioService())->login($d),'csrf'=>$_SESSION['csrf']]);
        if($path==='/logout' && $method==='POST') {
            $_SESSION=[];session_destroy();setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>'/','secure'=>getenv('SESSION_SECURE')==='1','httponly'=>true,'samesite'=>'Lax']);Http::json(['ok'=>true]);
        }
        if($path==='/catalog' && $method==='GET') Http::json((new ProdutoRepository())->catalog($_GET));
        if($path==='/metadata' && $method==='GET')Http::json(['categories'=>(new ProdutoRepository())->categories(),'brands'=>(new ProdutoRepository())->brands(),'settings'=>(new SettingsRepository())->get(),'order_labels'=>\SportZone\Models\Pedido::LABELS,'delivery_labels'=>\SportZone\Models\Pedido::DELIVERY_LABELS]);
        if($path==='/bestsellers' && $method==='GET'){
            $sql="SELECT p.id,p.name,p.image,p.price,p.sale_price,p.brand,p.stock,p.is_new,p.sizes,c.name AS category,c.slug AS category_slug,SUM(i.quantity) AS sold FROM order_items i JOIN orders o ON o.id=i.order_id JOIN payments pay ON pay.order_id=o.id JOIN products p ON p.id=i.product_id JOIN categories c ON c.id=p.category_id WHERE pay.provider='mercadopago' AND pay.environment='production' AND pay.status='approved' AND pay.verified_at IS NOT NULL AND pay.needs_review=0 AND o.status IN ('pagamento_aprovado','em_processamento','enviado','entregue') AND p.active=1 AND c.active=1 AND p.is_test=0 GROUP BY p.id,p.name,p.image,p.price,p.sale_price,p.brand,p.stock,p.is_new,p.sizes,c.name,c.slug ORDER BY sold DESC,p.id";
            $sql.=Database::connection()->getAttribute(\PDO::ATTR_DRIVER_NAME)==='sqlsrv'?' OFFSET 0 ROWS FETCH NEXT 8 ROWS ONLY':' LIMIT 8';Http::json(['items'=>$this->query($sql)->fetchAll()]);
        }
        if($path==='/product' && $method==='GET') {
            $p=(new ProdutoRepository())->find(Validation::integer($_GET['id']??0));
            $activeCategories=array_column((new ProdutoRepository())->categories(),'id');
            if(!$p || !$p['active'] || !in_array($p['category_id'],$activeCategories))Http::fail('Produto não encontrado.',404);
            Http::json($p);
        }
        if($path==='/cart' && $method==='GET')Http::json((new CarrinhoService())->summary($_SESSION['coupon']??'',$_SESSION['cep']??''));
        if($path==='/cart/item' && $method==='POST') {
            Database::transaction(function()use($d){$r=new CarrinhoRepository();$r->set($r->current(),Validation::integer($d['product_id']??0),Validation::text($d,'size',1,20),Validation::integer($d['quantity']??0,1,99),($d['mode']??'')==='add');});
            Http::json((new CarrinhoService())->summary($_SESSION['coupon']??'',$_SESSION['cep']??''));
        }
        if($path==='/cart/remove' && $method==='POST'){$r=new CarrinhoRepository();$r->remove($r->current(),Validation::integer($d['id']??0));Http::json(['ok'=>true]);}
        if($path==='/cart/clear' && $method==='POST'){$r=new CarrinhoRepository();$r->clear($r->current());unset($_SESSION['coupon'],$_SESSION['cep']);Http::json(['ok'=>true]);}
        if($path==='/cart/quote' && $method==='POST') {
            $coupon=Validation::text($d,'coupon',0,30);$cep=Validation::text($d,'cep',0,9);
            $result=(new CarrinhoService())->summary($coupon,$cep,true);$_SESSION['coupon']=$result['coupon'];$_SESSION['cep']=$cep;Http::json($result);
        }
        if($path==='/contact' && $method==='POST') {
            if(time()-($_SESSION['contact_at']??0)<30)Http::fail('Aguarde 30 segundos para enviar outra mensagem.',429);
            $id=$this->insert('contact_messages',['name'=>Validation::text($d,'name',3,120),'email'=>Validation::email($d),'message'=>Validation::text($d,'message',10,3000)]);$_SESSION['contact_at']=time();Http::json(['id'=>$id,'message'=>'Mensagem registrada. Obrigado por falar com a SportZone.'],201);
        }
        if(str_starts_with($path,'/admin'))$this->admin($path,$method,$d);
        $user=Security::user();$id=(int)$user['id'];
        if($path==='/payment/options' && $method==='GET'){
            $s=(new CarrinhoService())->summary($_SESSION['coupon']??'',$_SESSION['cep']??'');Http::json((new PaymentService())->options($s['total_cents']));
        }
        if(in_array($path,['/payment/start','/payment/refresh','/payment/cancel'],true))Http::fail('Pagamentos externos estão desativados. Utilize os controles de simulação do pedido.');
        if($path==='/profile' && $method==='GET')Http::json(['user'=>$user,'addresses'=>(new UsuarioRepository())->addresses($id)]);
        if($path==='/profile' && $method==='POST') {
            $repo=new UsuarioRepository();$email=Validation::email($d);$other=$repo->byEmail($email);
            if($other && (int)$other['id']!==$id)throw new \DomainException('E-mail indisponível.');
            $repo->update($id,Validation::text($d,'name',3,120),$email);Http::json(['user'=>$repo->publicById($id)]);
        }
        if($path==='/addresses' && $method==='POST')Http::json(['id'=>(new UsuarioRepository())->addAddress($id,Validation::address($d))],201);
        if($path==='/addresses/remove' && $method==='POST'){$this->query('DELETE FROM addresses WHERE id=? AND user_id=?',[Validation::integer($d['id']??0),$id]);Http::json(['ok'=>true]);}
        if($path==='/checkout/key' && $method==='POST'){$_SESSION['checkout_key']=bin2hex(random_bytes(32));Http::json(['checkout_key'=>$_SESSION['checkout_key']]);}
        if($path==='/checkout' && $method==='POST')Http::json(['id'=>(new PedidoService())->checkout($id,$d)],201);
        if($path==='/orders' && $method==='GET')Http::json((new PedidoRepository())->page($_GET,$id));
        if($path==='/order' && $method==='GET'){$o=(new PedidoRepository())->find(Validation::integer($_GET['id']??0),$id);if(!$o)Http::fail('Pedido não encontrado.',404);Http::json($o);}
        if($path==='/order/status' && $method==='POST'){(new PedidoService())->transition(Validation::integer($d['id']??0),$id,Validation::text($d,'status',3,30));Http::json(['ok'=>true]);}
        Http::fail('Rota não encontrada.',404);
    }
    private function admin(string $path,string $method,array $d): never {
        $admin=Security::admin();
        if($path==='/admin/dashboard' && $method==='GET')Http::json((new DashboardRepository())->summary());
        if($path==='/admin/settings' && $method==='GET')Http::json((new SettingsRepository())->get());
        if($path==='/admin/settings' && $method==='POST'){(new SettingsRepository())->save($d);Http::json(['ok'=>true]);}
        if($path==='/admin/stock' && $method==='GET')Http::json((new StockRepository())->page($_GET));
        if($path==='/admin/stock/movements' && $method==='GET')Http::json(['items'=>(new StockRepository())->movements($_GET)]);
        if($path==='/admin/stock/adjust' && $method==='POST'){(new \SportZone\Services\StockService())->adjust($d,(int)$admin['id']);Http::json(['ok'=>true]);}
        if($path==='/admin/promotion' && $method==='POST'){(new ProdutoService())->promotion(Validation::integer($d['id']??0),$d['sale_price']??'');Http::json(['ok'=>true]);}
        if($path==='/admin/products' && $method==='GET')Http::json((new ProdutoRepository())->catalog($_GET,true));
        if($path==='/admin/product' && $method==='GET'){$p=(new ProdutoRepository())->find(Validation::integer($_GET['id']??0));if(!$p)Http::fail('Produto não encontrado.',404);Http::json($p);}
        if($path==='/admin/product' && $method==='POST')Http::json(['id'=>(new ProdutoService())->save($d,(int)$admin['id'])]);
        if($path==='/admin/product/deactivate' && $method==='POST'){(new ProdutoRepository())->deactivate(Validation::integer($d['id']??0));Http::json(['ok'=>true]);}
        if($path==='/admin/upload' && $method==='POST')Http::json(['path'=>(new ProdutoService())->upload($_FILES['image']??[])]);
        if($path==='/admin/categories' && $method==='GET')Http::json(['items'=>(new ProdutoRepository())->categories(true)]);
        if($path==='/admin/category' && $method==='POST') {
            $name=Validation::text($d,'name',2,80);$slug=Validation::text($d,'slug',2,80);
            if(!preg_match('/^[a-z0-9-]+$/',$slug))throw new \DomainException('Slug deve conter letras minúsculas, números ou hífen.');
            $active=!empty($d['active'])?1:0;
            if(isset($d['id']))$this->query('UPDATE categories SET name=?,slug=?,active=? WHERE id=?',[$name,$slug,$active,Validation::integer($d['id'])]);
            else $this->insert('categories',['name'=>$name,'slug'=>$slug,'active'=>$active]);
            Http::json(['ok'=>true]);
        }
        if($path==='/admin/orders' && $method==='GET')Http::json((new PedidoRepository())->page($_GET));
        if($path==='/admin/order' && $method==='GET'){$o=(new PedidoRepository())->find(Validation::integer($_GET['id']??0));if(!$o)Http::fail('Pedido não encontrado.',404);Http::json($o);}
        if($path==='/admin/order/status' && $method==='POST'){(new PedidoService())->transition(Validation::integer($d['id']??0),(int)$admin['id'],Validation::text($d,'status',3,30),true,Validation::text($d,'note',0,200));Http::json(['ok'=>true]);}
        if($path==='/admin/contacts' && $method==='GET') {
            $sql='SELECT * FROM contact_messages ORDER BY id DESC';$sql.=$this->db->getAttribute(\PDO::ATTR_DRIVER_NAME)==='sqlsrv'?' OFFSET 0 ROWS FETCH NEXT 100 ROWS ONLY':' LIMIT 100';Http::json(['items'=>$this->query($sql)->fetchAll()]);
        }
        Http::fail('Rota administrativa não encontrada.',404);
    }
}
