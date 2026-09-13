<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Infrastructure\Auth\Models\DispositivoDosFactoresModel;
use App\Presentation\Api\V1\Support\VinculacionDosFactores;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Campana de verificacion en dos pasos de la app movil.
 *
 * El telefono solo entrega el codigo si su dueño lo vinculo antes desde la
 * pantalla de 2FA de la web. Sin esa vinculacion se responde 403 aunque la
 * sesion movil sea valida: de lo contrario bastaria la contraseña para
 * obtener el segundo factor y el 2FA no protegeria nada.
 */
final class DosFactoresController extends Controller
{
    public function estado(Request $request): JsonResponse
    {
        $user = $request->user();
        $dispositivo = VinculacionDosFactores::dispositivoDelToken($user, $request->user()->currentAccessToken()->id);

        return response()->json([
            'data' => [
                'requiere_2fa' => $user->hasAnyRole(['admin', 'supervisor']),
                'dos_factores_activo' => $user->two_factor_confirmed_at !== null,
                'vinculado' => $dispositivo !== null,
                'vinculado_en' => $dispositivo?->vinculado_en?->format('Y-m-d H:i'),
            ],
        ]);
    }

    public function vincular(Request $request): JsonResponse
    {
        $datos = $request->validate(['codigo' => ['required', 'string', 'max:16']]);
        $user = $request->user();

        if ($user->two_factor_secret === null) {
            return response()->json([
                'message' => 'Primero activa la verificación en dos pasos desde la web.',
            ], 422);
        }

        if (! VinculacionDosFactores::consumir($user, $datos['codigo'])) {
            return response()->json([
                'message' => 'El código de vinculación no es válido o ya venció. Genera uno nuevo en la web.',
            ], 422);
        }

        $token = $user->currentAccessToken();

        DispositivoDosFactoresModel::query()->updateOrCreate(
            ['token_id' => $token->id],
            [
                'user_id' => $user->id,
                'nombre' => $token->name,
                'vinculado_en' => now(),
            ],
        );

        return response()->json(['data' => ['vinculado' => true]], 201);
    }

    public function codigo(Request $request): JsonResponse
    {
        $user = $request->user();
        $dispositivo = VinculacionDosFactores::dispositivoDelToken($user, $request->user()->currentAccessToken()->id);

        if ($dispositivo === null) {
            return response()->json([
                'message' => 'Este teléfono no está vinculado. Vincúlalo desde la pantalla de 2FA en la web.',
            ], 403);
        }

        $codigo = VinculacionDosFactores::codigoActual($user);

        if ($codigo === null) {
            return response()->json([
                'message' => 'Esta cuenta no tiene verificación en dos pasos activa.',
            ], 422);
        }

        $dispositivo->update(['ultimo_uso_en' => now()]);

        return response()->json([
            'data' => [
                'codigo' => $codigo,
                'segundos_restantes' => VinculacionDosFactores::segundosRestantes(),
            ],
        ]);
    }

    public function desvincular(Request $request): JsonResponse
    {
        DispositivoDosFactoresModel::query()
            ->where('user_id', $request->user()->id)
            ->where('token_id', $request->user()->currentAccessToken()->id)
            ->delete();

        return response()->json(['data' => ['vinculado' => false]]);
    }
}
