<?php
declare(strict_types=1);
namespace SportZone\Models;
use SportZone\Core\Validation;
final class Produto {
    public function __construct(private array $data) {}
    public function price(): int { return Validation::money($this->data['sale_price'] ?? $this->data['price']); }
    public function available(int $quantity): bool { return (bool)$this->data['active'] && $quantity > 0 && $quantity <= (int)$this->data['stock']; }
    public function sizes(): array { return array_values(array_filter(explode(',', $this->data['sizes']))); }
    public function data(): array { return $this->data; }
}
