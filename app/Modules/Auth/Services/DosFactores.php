<?php

namespace App\Modules\Auth\Services;

use App\Modules\Users\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;

/**
 * Doble factor de un usuario: activar con la clave que escaneó, verificar al
 * iniciar sesión y desactivar. Un código de 6 dígitos no se acepta dos veces
 * (se guarda el último paso usado); un código de recuperación sirve una sola vez.
 */
final class DosFactores
{
    public const CODIGOS_RECUPERACION = 8;

    /** Sesión: clave generada que aún no se confirma (página Mi cuenta). */
    public const SESION_PENDIENTE = 'dos_factores.pendiente';

    /** Sesión: usuario que ya dio su contraseña y falta su código ['id' => int, 'hasta' => timestamp]. */
    public const SESION_LOGIN = 'dos_factores.login';

    /** Minutos que se espera el código después de la contraseña. */
    public const MINUTOS_LOGIN = 10;

    /** Dirección otpauth:// que la app lee del QR. */
    public function uri(User $user, string $clave): string
    {
        $emisor = rawurlencode(config('app.name'));

        return "otpauth://totp/{$emisor}:".rawurlencode($user->email)
            ."?secret={$clave}&issuer={$emisor}&digits=".Totp::DIGITOS.'&period='.Totp::PERIODO;
    }

    public function qrSvg(string $uri): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle(200, 1), new SvgImageBackEnd)))->writeString($uri);

        // Sin la declaración XML para poder insertarlo dentro del HTML.
        return trim(preg_replace('/^<\?xml[^>]*\?>/', '', $svg));
    }

    /**
     * Activa el doble factor si el código corresponde a la clave.
     *
     * @return list<string>|null los códigos de recuperación en claro (solo se muestran esta vez), o null si el código no es válido
     */
    public function activar(User $user, string $clave, string $codigo): ?array
    {
        $paso = Totp::verificar($clave, $codigo, now()->getTimestamp());
        if ($paso === null) {
            return null;
        }

        $codigos = array_map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)), range(1, self::CODIGOS_RECUPERACION));

        $user->forceFill([
            'dos_factores_secreto' => $clave,
            'dos_factores_confirmado_en' => now(),
            'dos_factores_recuperacion' => array_map(self::huella(...), $codigos),
            'dos_factores_ultimo_paso' => $paso,
        ])->save();

        return $codigos;
    }

    public function desactivar(User $user): void
    {
        $user->forceFill([
            'dos_factores_secreto' => null,
            'dos_factores_confirmado_en' => null,
            'dos_factores_recuperacion' => null,
            'dos_factores_ultimo_paso' => null,
        ])->save();
    }

    /** Código de la app (6 dígitos, sin repetir) o código de recuperación (se consume). */
    public function verificar(User $user, string $entrada): bool
    {
        if (! $user->tieneDosFactores()) {
            return false;
        }

        $entrada = Str::lower(trim($entrada));
        $paso = Totp::verificar($user->dos_factores_secreto, $entrada, now()->getTimestamp());
        if ($paso !== null) {
            if ($paso <= (int) $user->dos_factores_ultimo_paso) {
                return false;
            }
            $user->forceFill(['dos_factores_ultimo_paso' => $paso])->save();

            return true;
        }

        $restantes = $user->dos_factores_recuperacion ?? [];
        $indice = array_search(self::huella($entrada), $restantes, true);
        if ($indice === false) {
            return false;
        }
        unset($restantes[$indice]);
        $user->forceFill(['dos_factores_recuperacion' => array_values($restantes)])->save();

        return true;
    }

    /** Los códigos de recuperación son aleatorios y largos: basta SHA-256 (no hace falta bcrypt). */
    private static function huella(string $codigo): string
    {
        return hash('sha256', $codigo);
    }
}
