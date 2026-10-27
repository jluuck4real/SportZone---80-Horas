<?php
declare(strict_types=1);
namespace SportZone\Core;
use DomainException;
final class Validation {
    public static function text(array $data, string $key, int $min, int $max): string {
        $value = $data[$key] ?? '';
        if (!is_string($value)) throw new DomainException("Campo $key inválido.");
        $value = trim($value);
        if (mb_strlen($value) < $min || mb_strlen($value) > $max) throw new DomainException("Campo $key deve ter entre $min e $max caracteres.");
        return $value;
    }
    public static function email(array $data): string {
        $email = mb_strtolower(self::text($data, 'email', 5, 180));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new DomainException('E-mail inválido.');
        return $email;
    }
    public static function integer(mixed $value, int $min = 1, int $max = 999999): int {
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value < $min || (int)$value > $max) throw new DomainException('Quantidade ou identificador inválido.');
        return (int)$value;
    }
    public static function money(mixed $value): int {
        if (!is_scalar($value) || !preg_match('/^\d{1,7}(?:\.\d{1,2})?$/', (string)$value)) throw new DomainException('Valor monetário inválido. Use até duas casas decimais.');
        [$whole, $decimal] = array_pad(explode('.', (string)$value), 2, '');
        return (int)$whole * 100 + (int)str_pad($decimal, 2, '0');
    }
    public static function decimal(int $cents): string { return sprintf('%d.%02d', intdiv($cents,100), $cents % 100); }
    public static function address(array $d): array {
        $result = [];
        foreach (['recipient'=>[3,120], 'street'=>[3,180], 'number'=>[1,20], 'complement'=>[0,100], 'district'=>[2,100], 'city'=>[2,100], 'state'=>[2,2]] as $key=>$range) $result[$key] = self::text($d,$key,...$range);
        $result['state'] = strtoupper($result['state']);
        if (!in_array($result['state'], explode(' ', 'AC AL AP AM BA CE DF ES GO MA MT MS MG PA PB PR PE PI RJ RN RS RO RR SC SP SE TO'), true)) throw new DomainException('Estado inválido.');
        $result['cep'] = \SportZone\Services\FreteService::cep($d['cep'] ?? '');
        return $result;
    }
}
