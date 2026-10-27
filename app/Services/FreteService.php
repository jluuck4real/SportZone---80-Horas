<?php
declare(strict_types=1);
namespace SportZone\Services;
final class FreteService {
    public static function cep(mixed $cep): string {
        if (!is_string($cep) || !preg_match('/^\d{5}-?\d{3}$/',$cep) || preg_match('/^(\d)\1{7}$/',str_replace('-','',$cep))) throw new \DomainException('Informe um CEP válido no formato 00000-000.');
        return str_replace('-','',$cep);
    }
    public static function quote(string $cep,int $net): array {
        $cep=self::cep($cep); $zone=(int)$cep[0];
        $cost=$zone<=3 ? 1490 : ($zone<=6 ? 2490 : 3490);
        return ['cep'=>$cep,'cents'=>$net>=29900?0:$cost,'days'=>$zone<=3?'3 a 5 dias úteis':($zone<=6?'5 a 8 dias úteis':'8 a 12 dias úteis'),'simulated'=>true];
    }
}
