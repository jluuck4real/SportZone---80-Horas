<?php
declare(strict_types=1);
namespace SportZone\Services;
use SportZone\Core\Validation;
use SportZone\Core\Database;
use SportZone\Repositories\ProdutoRepository;
final class ProdutoService {
    public function save(array $d,?int $actor=null): int {
        $price=Validation::money($d['price']??'');
        $sale=($d['sale_price']??'')!==''?Validation::money($d['sale_price']):null;
        if($price<1 || ($sale!==null && ($sale<1 || $sale>=$price)))throw new \DomainException('Preço promocional deve ser positivo e menor que o original.');
        $image=Validation::text($d,'image',3,300);
        if(!preg_match('~^assets/(?:images/[a-z0-9-]+\.svg|uploads/[a-f0-9]{32}\.(?:jpg|png|webp))$~',$image) || !is_file(ROOT.'/'.$image))throw new \DomainException('Use uma imagem local da biblioteca ou envie uma imagem válida.');
        $repo=new ProdutoRepository();$category=Validation::integer($d['category_id']??0);
        if(!in_array($category,array_map(fn($c)=>(int)$c['id'],$repo->categories()),true))throw new \DomainException('Categoria inativa ou inexistente.');
        $sizes=Validation::text($d,'sizes',1,120);
        $sizeList=array_map('trim',explode(',',$sizes));
        $sizes=implode(',',$sizeList);
        if(count($sizeList)>20 || count(array_unique($sizeList))!==count($sizeList))throw new \DomainException('Tamanhos inválidos.');
        foreach($sizeList as $size)if(!preg_match('/^[\p{L}\p{N}. -]{1,20}$/u',$size))throw new \DomainException('Separe tamanhos por vírgula, sem espaços nas extremidades.');
        $gender=$d['gender']??'unissex';
        if(!in_array($gender,['masculino','feminino','unissex'],true))throw new \DomainException('Gênero inválido.');
        $stock=Validation::integer($d['stock']??0,0,2000000);
        $variants=[];
        if(isset($d['variants'])){
            if(!is_array($d['variants']) || count($d['variants'])!==count($sizeList))throw new \DomainException('Informe estoque para cada tamanho.');
            foreach($d['variants'] as $v){if(!is_array($v))throw new \DomainException('Variação inválida.');$size=Validation::text($v,'size',1,20);if(!in_array($size,$sizeList,true)||in_array($size,array_column($variants,'size'),true))throw new \DomainException('Tamanhos duplicados ou inconsistentes.');$variants[]=['size'=>$size,'stock'=>Validation::integer($v['stock']??-1,0,100000)];}
            $stock=array_sum(array_column($variants,'stock'));
        }
        $gallery=$d['gallery']??[];if(!is_array($gallery)||count($gallery)>6)throw new \DomainException('Envie no máximo seis imagens adicionais.');
        $gallery=array_values(array_unique(array_filter($gallery,fn($path)=>$path!==$image)));
        foreach($gallery as $path)if(!is_string($path)||!preg_match('~^assets/(?:images/[a-z0-9-]+\.svg|uploads/[a-f0-9]{32}\.(?:jpg|png|webp))$~',$path)||!is_file(ROOT.'/'.$path))throw new \DomainException('Imagem adicional inválida.');
        $data=['category_id'=>$category,'name'=>Validation::text($d,'name',3,160),'description'=>Validation::text($d,'description',10,3000),'brand'=>Validation::text($d,'brand',2,80),'gender'=>$gender,'price'=>Validation::decimal($price),'sale_price'=>$sale===null?null:Validation::decimal($sale),'image'=>$image,'stock'=>$stock,'sizes'=>$sizes,'is_new'=>!empty($d['is_new'])?1:0,'active'=>!empty($d['active'])?1:0];
        $data['featured']=!empty($d['featured'])?1:0;
        return Database::transaction(function()use($repo,$d,$data,$variants,$sizeList,$stock,$actor,$gallery){
            $id=isset($d['id'])?Validation::integer($d['id']):null;$old=$id?$repo->find($id,true):null;
            if($id&&!$old)throw new \DomainException('Produto não encontrado.');
            $rows=$variants;
            if(!$rows){
                if($old && (int)$old['stock']===$stock && $old['sizes']===$data['sizes'])$rows=array_map(fn($v)=>['size'=>$v['size'],'stock'=>(int)$v['stock']],array_values(array_filter($old['variants'],fn($v)=>$v['active'])));
                else foreach($sizeList as $i=>$size)$rows[]=['size'=>$size,'stock'=>intdiv($stock,count($sizeList))+($i<$stock%count($sizeList)?1:0)];
            }
            $id=$repo->save($data,$id);(new StockService())->synchronize($id,$rows,$actor);
            if(isset($d['gallery'])||!$old){$repo->query('DELETE FROM product_images WHERE product_id=?',[$id]);foreach($gallery as $i=>$path)$repo->insert('product_images',['product_id'=>$id,'path'=>$path,'sort_order'=>$i]);}
            return $id;
        });
    }
    public function promotion(int $id,mixed $sale): void {
        Database::transaction(function()use($id,$sale){$r=new ProdutoRepository();$p=$r->find($id,true);if(!$p)throw new \DomainException('Produto não encontrado.');$value=$sale===''||$sale===null?null:Validation::money($sale);if($value!==null&&($value<1||$value>=Validation::money($p['price'])))throw new \DomainException('Preço promocional deve ser menor que o preço original e maior que zero.');$r->query('UPDATE products SET sale_price=? WHERE id=?',[$value===null?null:Validation::decimal($value),$id]);});
    }
    public function upload(array $file): string {
        if(($file['error']??1)!==UPLOAD_ERR_OK || ($file['size']??0)>2*1024*1024 || !is_uploaded_file($file['tmp_name']??''))throw new \DomainException('Envie JPG, PNG ou WebP com até 2 MB.');
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extension=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
        if(!$extension)throw new \DomainException('Formato de imagem não permitido.');
        $info=@getimagesize($file['tmp_name']);
        if(!$extension || !$info || $info[0]>4096 || $info[1]>4096 || $info[0]<1 || $info[1]<1)throw new \DomainException('Imagem inválida ou maior que 4096px.');
        // Images are served by the allowlisted router as bytes with nosniff; never executed.
        $path='assets/uploads/'.bin2hex(random_bytes(16)).'.'.$extension;
        if(!is_dir(ROOT.'/assets/uploads'))mkdir(ROOT.'/assets/uploads',0755,true);
        if(!move_uploaded_file($file['tmp_name'],ROOT.'/'.$path))throw new \RuntimeException('Falha ao armazenar imagem.');
        return $path;
    }
}
