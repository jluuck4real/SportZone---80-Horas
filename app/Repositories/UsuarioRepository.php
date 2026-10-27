<?php
declare(strict_types=1);
namespace SportZone\Repositories;
final class UsuarioRepository extends Repository {
    public function byEmail(string $email): ?array { return $this->query('SELECT * FROM users WHERE email=?',[$email])->fetch() ?: null; }
    public function publicById(int $id): ?array { return $this->query('SELECT id,name,email,role,created_at FROM users WHERE id=?',[$id])->fetch() ?: null; }
    public function create(string $name,string $email,string $password,string $role='customer'): int {
        return $this->insert('users',['name'=>$name,'email'=>$email,'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'role'=>$role]);
    }
    public function update(int $id,string $name,string $email): void { $this->query('UPDATE users SET name=?,email=? WHERE id=?',[$name,$email,$id]); }
    public function addresses(int $user): array { return $this->query('SELECT * FROM addresses WHERE user_id=? ORDER BY id DESC',[$user])->fetchAll(); }
    public function addAddress(int $user,array $address): int { return $this->insert('addresses',['user_id'=>$user,...$address]); }
}
