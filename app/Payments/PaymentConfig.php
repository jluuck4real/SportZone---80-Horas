<?php
declare(strict_types=1);
namespace SportZone\Payments;
final class PaymentConfig {
    // Escopo revisto pelo usuário: somente demonstração; não há cliente HTTP de cobranças.
    public static function provider(): string { return 'simulation'; }
    public static function environment(): string { return 'simulation'; }
    public static function ready(): never { throw new \DomainException('Pagamentos reais estão desativados nesta demonstração.'); }
    public static function publicData(): array { return ['provider'=>'simulation','environment'=>'simulation','ready'=>true,'missing'=>[],'label'=>'Simulação local','real'=>false]; }
    public static function uuid(): string {
        $b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);$h=bin2hex($b);
        return substr($h,0,8).'-'.substr($h,8,4).'-'.substr($h,12,4).'-'.substr($h,16,4).'-'.substr($h,20);
    }
}
