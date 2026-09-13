<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Support;

use App\Infrastructure\Auth\Models\DispositivoDosFactoresModel;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Vinculacion del telefono que recibe el codigo de verificacion en dos
 * pasos.
 *
 * El codigo de vinculacion se muestra en la pantalla de 2FA de la web, que
 * es la misma donde ya aparece el secreto: vincular no expone nada que no
 * estuviera ahi. Solo el telefono vinculado puede pedir despues el codigo,
 * asi que quien entre a la app movil sabiendo unicamente la contraseña no
 * obtiene el segundo factor.
 */
final class VinculacionDosFactores
{
    /** Ventana corta: el codigo se dicta del navegador al telefono en el momento. */
    private const VIGENCIA_MINUTOS = 10;

    public static function generarCodigo(User $user): string
    {
        $codigo = Str::upper(Str::random(3).'-'.Str::random(3));

        Cache::put(self::clave($user->id), $codigo, now()->addMinutes(self::VIGENCIA_MINUTOS));

        return $codigo;
    }

    public static function codigoVigente(User $user): ?string
    {
        return Cache::get(self::clave($user->id));
    }

    /**
     * Un codigo sirve una sola vez: se consume al vincular para que no quede
     * anotado y reutilizable por otro telefono.
     */
    public static function consumir(User $user, string $codigo): bool
    {
        $esperado = Cache::get(self::clave($user->id));

        if ($esperado === null || ! hash_equals($esperado, Str::upper(trim($codigo)))) {
            return false;
        }

        Cache::forget(self::clave($user->id));

        return true;
    }

    /** El codigo de 6 digitos que toca ahora mismo para ese usuario. */
    public static function codigoActual(User $user): ?string
    {
        if ($user->two_factor_secret === null) {
            return null;
        }

        return (new Google2FA())->getCurrentOtp(Crypt::decrypt($user->two_factor_secret));
    }

    /** Segundos que le quedan de vida al codigo actual antes de rotar. */
    public static function segundosRestantes(): int
    {
        return 30 - (now()->getTimestamp() % 30);
    }

    public static function dispositivoDelToken(User $user, int|string $tokenId): ?DispositivoDosFactoresModel
    {
        return DispositivoDosFactoresModel::query()
            ->where('user_id', $user->id)
            ->where('token_id', $tokenId)
            ->first();
    }

    private static function clave(int $userId): string
    {
        return "vinculacion-2fa:{$userId}";
    }
}
