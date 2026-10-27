<?php
declare(strict_types=1);
namespace SportZone\Repositories;
use SportZone\Core\Database;
final class CarrinhoRepository extends Repository {
    public function current(): int {
        $user=$_SESSION['user_id']??null;
        $cart=$user ? $this->query('SELECT id FROM carts WHERE user_id=?',[$user])->fetchColumn() : $this->query('SELECT id FROM carts WHERE token=? AND user_id IS NULL',[$_SESSION['cart_token']])->fetchColumn();
        return $cart ? (int)$cart : $this->insert('carts',['token'=>$_SESSION['cart_token'],'user_id'=>$user]);
    }
    public function rows(int $cart): array {
        return $this->query('SELECT i.id,i.product_id,i.quantity,i.size,p.name,p.price,p.sale_price,p.image,p.stock,p.active,p.sizes,p.is_test,c.active AS category_active,v.id AS variant_id,COALESCE(v.stock,0) AS variant_stock,COALESCE(v.active,0) AS variant_active FROM cart_items i JOIN products p ON p.id=i.product_id JOIN categories c ON c.id=p.category_id LEFT JOIN product_variants v ON v.product_id=i.product_id AND v.size=i.size WHERE i.cart_id=? ORDER BY p.id,v.id,i.id',[$cart])->fetchAll();
    }
    public function set(int $cart,int $product,string $size,int $quantity,bool $add=false): void {
        $p=(new ProdutoRepository())->find($product,true);
        if (!$p) throw new \DomainException('Produto não encontrado.');
        $model=new \SportZone\Models\Produto($p);
        if (!in_array($size,$model->sizes(),true)) throw new \DomainException('Escolha um tamanho disponível.');
        $category=$this->query('SELECT active FROM categories WHERE id=?',[$p['category_id']])->fetchColumn();
        $item=$this->query('SELECT * FROM cart_items WHERE cart_id=? AND product_id=? AND size=?',[$cart,$product,$size])->fetch();
        $quantity+=($add && $item)?(int)$item['quantity']:0;
        $other=(int)$this->query('SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE cart_id=? AND product_id=? AND size<>?',[$cart,$product,$size])->fetchColumn();
        $variant=$this->query('SELECT stock FROM product_variants WHERE product_id=? AND size=? AND active=1',[$product,$size])->fetchColumn();
        if (!$category || !$model->available($quantity+$other) || $quantity>99 || $variant===false || $quantity>(int)$variant) throw new \DomainException('Quantidade indisponível para o tamanho selecionado.');
        if ($item) $this->query('UPDATE cart_items SET quantity=? WHERE id=?',[$quantity,$item['id']]);
        else $this->insert('cart_items',['cart_id'=>$cart,'product_id'=>$product,'size'=>$size,'quantity'=>$quantity]);
    }
    public function remove(int $cart,int $item): void { $this->query('DELETE FROM cart_items WHERE cart_id=? AND id=?',[$cart,$item]); }
    public function clear(int $cart): void { $this->query('DELETE FROM cart_items WHERE cart_id=?',[$cart]); }
    public function merge(int $user): void {
        Database::transaction(function()use($user){
            $guest=$this->query('SELECT id FROM carts WHERE token=? AND user_id IS NULL',[$_SESSION['cart_token']])->fetchColumn();
            $saved=$this->query('SELECT id FROM carts WHERE user_id=?',[$user])->fetchColumn();
            if (!$guest) return;
            if (!$saved) { $this->query('UPDATE carts SET user_id=? WHERE id=?',[$user,$guest]); return; }
            foreach ($this->rows((int)$guest) as $row) {
                try { $this->set((int)$saved,(int)$row['product_id'],$row['size'],(int)$row['quantity'],true); }
                catch (\DomainException) { // Preserve guest items on merge conflict, remove only after successful login resolution.
                    $p=(new ProdutoRepository())->find((int)$row['product_id'],true);
                    if ($p && $p['active'] && (int)$p['stock']>0) {
                        $existing=(int)$this->query('SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE cart_id=? AND product_id=? AND size=?',[$saved,$row['product_id'],$row['size']])->fetchColumn();
                        $v=$this->query('SELECT stock FROM product_variants WHERE product_id=? AND size=? AND active=1',[$row['product_id'],$row['size']])->fetchColumn();
                        $available=min(99-$existing,(int)$v-$existing);
                        if ($available>0) { try { $this->set((int)$saved,(int)$row['product_id'],$row['size'],min($available,(int)$row['quantity']),true); } catch (\DomainException) {} }
                    }
                    $_SESSION['cart_notice']='Carrinhos reunidos; quantidades ajustadas ao estoque disponível. Confira os itens.';
                }
            }
            $this->clear((int)$guest); $this->query('DELETE FROM carts WHERE id=?',[$guest]);
        });
    }
}
