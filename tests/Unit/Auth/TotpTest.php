<?php

namespace Tests\Unit\Auth;

use App\Modules\Auth\Services\Totp;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/** Vectores oficiales del RFC 6238 (apéndice B, SHA1) y ventana de ±30 s. */
class TotpTest extends TestCase
{
    /** "12345678901234567890" en Base32. */
    private const CLAVE_RFC = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    #[TestWith([59, '94287082'])]
    #[TestWith([1111111109, '07081804'])]
    #[TestWith([1111111111, '14050471'])]
    #[TestWith([1234567890, '89005924'])]
    #[TestWith([2000000000, '69279037'])]
    #[TestWith([20000000000, '65353130'])]
    public function test_coincide_con_los_vectores_del_rfc(int $unix, string $esperado): void
    {
        $this->assertSame($esperado, Totp::codigo(self::CLAVE_RFC, Totp::paso($unix), 8));
    }

    public function test_con_seis_digitos_son_los_ultimos_seis(): void
    {
        $this->assertSame('287082', Totp::codigo(self::CLAVE_RFC, Totp::paso(59)));
    }

    public function test_base32_ida_y_vuelta(): void
    {
        $this->assertSame(self::CLAVE_RFC, Totp::aBase32('12345678901234567890'));
        $this->assertSame('12345678901234567890', Totp::desdeBase32(strtolower(self::CLAVE_RFC)));
        $this->assertSame(32, strlen(Totp::nuevaClave()));
    }

    public function test_acepta_el_paso_anterior_y_el_siguiente_y_nada_mas(): void
    {
        $ahora = 1_000_000_020; // paso 33,333,334
        $paso = Totp::paso($ahora);

        $this->assertSame($paso - 1, Totp::verificar(self::CLAVE_RFC, Totp::codigo(self::CLAVE_RFC, $paso - 1), $ahora));
        $this->assertSame($paso + 1, Totp::verificar(self::CLAVE_RFC, Totp::codigo(self::CLAVE_RFC, $paso + 1), $ahora));
        $this->assertNull(Totp::verificar(self::CLAVE_RFC, Totp::codigo(self::CLAVE_RFC, $paso - 2), $ahora));
        $this->assertNull(Totp::verificar(self::CLAVE_RFC, '12345a', $ahora));
    }
}
