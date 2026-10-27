<?php
declare(strict_types=1);
namespace SportZone\Models;
final class Usuario {
    public function __construct(private array $data) {}
    public function verify(string $password): bool { return password_verify($password, $this->data['password_hash']); }
    public function publicData(): array { unset($this->data['password_hash']); return $this->data; }
}
