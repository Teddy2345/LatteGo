<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Support;

use App\Infrastructure\Auth\Models\DispositivoDosFactoresModel;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\PersonalAccessToken;
use PragmaRX\Google2FA\Google2FA;

/**
 * Vinculacion del telefono que recibe el codigo de verificacion en dos
 * pasos.
 *
 * El telefono genera un codigo corto y su dueño lo escribe en la pantalla de
 * 2FA de la web, donde ya inicio sesion. Vincular exige, entonces, estar
 * dentro de la web con esa cuenta: quien entre a la app sabiendo unicamente
 * la contraseña puede generar codigos, pero no tiene donde canjearlos, y sin
 * vincular el telefono nunca recibe el segundo factor.
 */
final class VinculacionDosFactores
{
    /** Ventana corta: el codigo se lee del telefono y se escribe en la web en el momento. */
    public const VIGENCIA_MINUTOS = 10;

    /** Sin 0/O ni 1/I/L: se dicta y se copia a mano sin confundir caracteres. */
    private const ALFABETO = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * Codigo que muestra el telefono para que su dueño lo escriba en la web.
     * Pedir uno nuevo anula el anterior de ese mismo telefono.
     */
    public static function generarCodigoParaTelefono(User $user, int|string $tokenId, string $nombreTelefono, ?string $dispositivoId = null): string
    {
        $anterior = Cache::get(self::claveTelefono($tokenId));

        if (is_string($anterior)) {
            Cache::forget(self::claveCodigo($anterior));
        }

        do {
            $codigo = self::codigoAlAzar();
        } while (Cache::has(self::claveCodigo($codigo)));

        $vence = now()->addMinutes(self::VIGENCIA_MINUTOS);

        Cache::put(self::claveCodigo($codigo), [
            'user_id' => $user->id,
            'token_id' => $tokenId,
            'dispositivo_id' => $dispositivoId,
            'nombre' => $nombreTelefono,
        ], $vence);
        Cache::put(self::claveTelefono($tokenId), $codigo, $vence);

        return $codigo;
    }

    /**
     * Vincula el telefono que muestra ese codigo. Solo sirve para un telefono
     * con sesion de la misma cuenta que lo canjea, y una sola vez.
     */
    public static function vincularConCodigo(User $user, string $codigoEscrito): ?DispositivoDosFactoresModel
    {
        $codigo = self::normalizar($codigoEscrito);
        $pendiente = $codigo === null ? null : Cache::get(self::claveCodigo($codigo));

        if (! is_array($pendiente) || (int) $pendiente['user_id'] !== (int) $user->id) {
            return null;
        }

        // La sesion del telefono pudo cerrarse mientras tanto.
        $sesionViva = PersonalAccessToken::query()
            ->whereKey($pendiente['token_id'])
            ->where('tokenable_type', $user->getMorphClass())
            ->where('tokenable_id', $user->id)
            ->exists();

        Cache::forget(self::claveCodigo($codigo));
        Cache::forget(self::claveTelefono($pendiente['token_id']));

        if (! $sesionViva) {
            return null;
        }

        $dispositivoId = $pendiente['dispositivo_id'] ?? null;

        // Con identificador del telefono la vinculacion sobrevive a cerrar
        // sesion; sin el (app antigua) se conserva el comportamiento por token.
        $buscarPor = $dispositivoId === null
            ? ['user_id' => $user->id, 'token_id' => $pendiente['token_id']]
            : ['user_id' => $user->id, 'dispositivo_id' => $dispositivoId];

        return DispositivoDosFactoresModel::query()->updateOrCreate($buscarPor, [
            'token_id' => $pendiente['token_id'],
            'nombre' => $pendiente['nombre'],
            'vinculado_en' => now(),
        ]);
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

    /**
     * El telefono vinculado que esta haciendo la peticion. Se busca por su
     * identificador propio, que no cambia al volver a iniciar sesion; si la
     * app todavia no lo manda, se cae al token de la sesion actual.
     */
    public static function dispositivoVinculado(User $user, int|string $tokenId, ?string $dispositivoId = null): ?DispositivoDosFactoresModel
    {
        // Si la app se identifica, solo vale su identificador: buscar
        // ademas por token dejaria pasar a otro telefono que compartiera la
        // sesion sin haberse vinculado nunca.
        if ($dispositivoId !== null) {
            $porTelefono = DispositivoDosFactoresModel::query()
                ->where('user_id', $user->id)
                ->where('dispositivo_id', $dispositivoId)
                ->first();

            // La sesion cambio: se deja anotada la actual como referencia.
            if ($porTelefono !== null && (int) $porTelefono->token_id !== (int) $tokenId) {
                $porTelefono->update(['token_id' => $tokenId]);
            }

            return $porTelefono;
        }

        // App anterior, que todavia no manda identificador: se atiende por la
        // sesion, como antes, y solo si esa vinculacion tampoco lo tiene.
        return DispositivoDosFactoresModel::query()
            ->where('user_id', $user->id)
            ->where('token_id', $tokenId)
            ->whereNull('dispositivo_id')
            ->first();
    }

    private static function codigoAlAzar(): string
    {
        $caracteres = '';

        for ($i = 0; $i < 6; $i++) {
            $caracteres .= self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)];
        }

        return substr($caracteres, 0, 3).'-'.substr($caracteres, 3);
    }

    /** Acepta el codigo como se escriba: minusculas, sin guion o con espacios. */
    private static function normalizar(string $codigo): ?string
    {
        $limpio = preg_replace('/[^A-Z0-9]/', '', strtoupper($codigo));

        return strlen((string) $limpio) === 6 ? substr($limpio, 0, 3).'-'.substr($limpio, 3) : null;
    }

    private static function claveCodigo(string $codigo): string
    {
        return "vinculacion-2fa:codigo:{$codigo}";
    }

    private static function claveTelefono(int|string $tokenId): string
    {
        return "vinculacion-2fa:telefono:{$tokenId}";
    }
}
