<?php
declare(strict_types=1);
namespace SportZone\Services;
use SportZone\Core\Validation;
use SportZone\Models\Usuario;
use SportZone\Repositories\UsuarioRepository;
use SportZone\Repositories\CarrinhoRepository;
use SportZone\Repositories\Repository;
final class UsuarioService extends Repository {
    public function register(array $d): array {
        $name=Validation::text($d,'name',3,120); $email=Validation::email($d);
        $password=Validation::text($d,'password',10,72);
        if (strlen($password)>72) throw new \DomainException('Senha deve ter no máximo 72 bytes.');
        if ($password!==($d['confirm_password']??'')) throw new \DomainException('As senhas não coincidem.');
        $repo=new UsuarioRepository();
        if ($repo->byEmail($email)) throw new \DomainException('Não foi possível cadastrar este e-mail.');
        $id=$repo->create($name,$email,$password); return $repo->publicById($id);
    }
    public function login(array $d): array {
        $email=Validation::email($d); $password=Validation::text($d,'password',1,72);
        $fingerprint=hash('sha256',($_SERVER['REMOTE_ADDR']??'cli').'|'.$email);
        $since=gmdate('Y-m-d H:i:s',time()-900);
        $attempts=(int)$this->query('SELECT COUNT(*) FROM login_attempts WHERE fingerprint=? AND occurred_at>?',[$fingerprint,$since])->fetchColumn();
        if ($attempts>=5) throw new \DomainException('Muitas tentativas. Aguarde 15 minutos.');
        $data=(new UsuarioRepository())->byEmail($email);
        $valid=$data ? (new Usuario($data))->verify($password) : password_verify($password,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi');
        if (!$data || !$valid) { $this->insert('login_attempts',['fingerprint'=>$fingerprint]); throw new \DomainException('E-mail ou senha incorretos.'); }
        (new CarrinhoRepository())->merge((int)$data['id']);
        session_regenerate_id(true); $_SESSION['user_id']=(int)$data['id']; $_SESSION['csrf']=bin2hex(random_bytes(32));
        $this->query('DELETE FROM login_attempts WHERE fingerprint=?',[$fingerprint]);
        return (new Usuario($data))->publicData();
    }
}
