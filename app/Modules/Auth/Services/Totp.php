<?php

namespace App\Modules\Auth\Services;

/**
 * Códigos de un solo uso por tiempo (TOTP, RFC 6238), los mismos que muestran
 * Google Authenticator, Microsoft Authenticator o Authy:
 *
 *   paso   = segundos desde 1970 ÷ 30 (sin decimales)
 *   código = HMAC-SHA1(clave, paso) recortado a 6 dígitos (RFC 4226, "dynamic truncation")
 *
 * Ejemplo del RFC: clave "12345678901234567890" y t = 59 s → paso 1 → 94287082
 * con 8 dígitos; con 6 dígitos, 287082. La clave se guarda y se comparte con
 * la app en Base32 (A–Z y 2–7).
 */
final class Totp
{
    public const PERIODO = 30;

    public const DIGITOS = 6;

    /** Pasos aceptados antes y después del actual, por si el reloj del teléfono va desfasado. */
    public const VENTANA = 1;

    private const ALFABETO = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** Clave nueva de 160 bits (el tamaño de SHA1) en Base32: 32 caracteres. */
    public static function nuevaClave(): string
    {
        return self::aBase32(random_bytes(20));
    }

    public static function paso(int $unix): int
    {
        return intdiv($unix, self::PERIODO);
    }

    public static function codigo(string $claveBase32, int $paso, int $digitos = self::DIGITOS): string
    {
        $hmac = hash_hmac('sha1', pack('J', $paso), self::desdeBase32($claveBase32), true);
        $inicio = ord($hmac[19]) & 0x0F;
        $numero = ((ord($hmac[$inicio]) & 0x7F) << 24)
            | (ord($hmac[$inicio + 1]) << 16)
            | (ord($hmac[$inicio + 2]) << 8)
            | ord($hmac[$inicio + 3]);

        return str_pad((string) ($numero % 10 ** $digitos), $digitos, '0', STR_PAD_LEFT);
    }

    /** Paso en el que el código es válido (el actual ± VENTANA), o null si no lo es. */
    public static function verificar(string $claveBase32, string $codigo, int $unix): ?int
    {
        if (! preg_match('/^\d{'.self::DIGITOS.'}$/', $codigo)) {
            return null;
        }

        $actual = self::paso($unix);
        for ($paso = $actual - self::VENTANA; $paso <= $actual + self::VENTANA; $paso++) {
            if (hash_equals(self::codigo($claveBase32, $paso), $codigo)) {
                return $paso;
            }
        }

        return null;
    }

    public static function aBase32(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $texto = '';
        foreach (str_split($bits, 5) as $grupo) {
            $texto .= self::ALFABETO[bindec(str_pad($grupo, 5, '0'))];
        }

        return $texto;
    }

    public static function desdeBase32(string $texto): string
    {
        $bits = '';
        foreach (str_split(strtoupper(str_replace(['=', ' '], '', $texto))) as $caracter) {
            $valor = strpos(self::ALFABETO, $caracter);
            if ($valor !== false) {
                $bits .= str_pad(decbin($valor), 5, '0', STR_PAD_LEFT);
            }
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $grupo) {
            if (strlen($grupo) === 8) {
                $bytes .= chr(bindec($grupo));
            }
        }

        return $bytes;
    }
}
