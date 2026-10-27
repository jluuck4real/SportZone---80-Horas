<?php
declare(strict_types=1);
namespace SportZone\Repositories;
use SportZone\Core\Validation;
final class SettingsRepository extends Repository {
    public function get(): array { return $this->query('SELECT notice,support_email,low_stock_threshold FROM store_settings WHERE id=1')->fetch(); }
    public function save(array $data): void {
        $notice=Validation::text($data,'notice',0,140);$email=Validation::text($data,'support_email',0,180);
        if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new \DomainException('E-mail de atendimento inválido.');
        $threshold=Validation::integer($data['low_stock_threshold']??0,1,50);
        $this->query('UPDATE store_settings SET notice=?,support_email=?,low_stock_threshold=? WHERE id=1',[$notice,$email,$threshold]);
    }
}
