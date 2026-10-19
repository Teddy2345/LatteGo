<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Presentation\Api\V1\Support\VinculacionDosFactores;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Campana de verificacion en dos pasos de la app movil.
 *
 * El telefono solo entrega el codigo si su dueño lo vinculo antes: la app
 * genera un codigo de vinculacion y el dueño lo escribe en la pantalla de
 * 2FA de la web. Sin esa vinculacion se responde 403 aunque la
 * sesion movil sea valida: de lo contrario bastaria la contraseña para
 * obtener el segundo factor y el 2FA no protegeria nada.
 */
final class DosFactoresController extends Controller
{
    /**
     * Identificador propio del telefono. La app lo crea una vez y lo guarda,
     * de modo que la vinculacion no se pierda al volver a iniciar sesion.
     * Una app anterior no lo manda y se sigue atendiendo por token.
     */
    private function dispositivoId(Request $request): ?string
    {
        $id = trim((string) $request->header('X-Dispositivo'));

        return preg_match('/^[A-Za-z0-9-]{8,64}$/', $id) === 1 ? $id : null;
    }

    public function estado(Request $request): JsonResponse
    {
        $user = $request->user();
        $dispositivo = VinculacionDosFactores::dispositivoVinculado($user, $user->currentAccessToken()->id, $this->dispositivoId($request));

        return response()->json([
            'data' => [
                'requiere_2fa' => $user->hasAnyRole(['admin', 'supervisor']),
                'dos_factores_activo' => $user->two_factor_confirmed_at !== null,
                // Ya se pulso "Habilitar" en la web aunque falte confirmar:
                // desde aqui se puede vincular y usar la campana para confirmar.
                'dos_factores_iniciado' => $user->two_factor_secret !== null,
                'vinculado' => $dispositivo !== null,
                'vinculado_en' => $dispositivo?->vinculado_en?->format('Y-m-d H:i'),
            ],
        ]);
    }

    /**
     * El telefono pide un codigo de vinculacion para mostrarlo en la campana.
     * Su dueño lo escribe en la pantalla de 2FA de la web, y recien ahi el
     * telefono queda vinculado.
     */
    public function codigoVinculacion(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->two_factor_secret === null) {
            return response()->json([
                'message' => 'Primero pulsa «Habilitar 2FA» en la web.',
            ], 422);
        }

        $token = $user->currentAccessToken();
        $codigo = VinculacionDosFactores::generarCodigoParaTelefono($user, $token->id, $token->name, $this->dispositivoId($request));

        return response()->json([
            'data' => [
                'codigo' => $codigo,
                'vence_en_segundos' => VinculacionDosFactores::VIGENCIA_MINUTOS * 60,
            ],
        ], 201);
    }

    public function codigo(Request $request): JsonResponse
    {
        $user = $request->user();
        $dispositivo = VinculacionDosFactores::dispositivoVinculado($user, $user->currentAccessToken()->id, $this->dispositivoId($request));

        if ($dispositivo === null) {
            return response()->json([
                'message' => 'Este teléfono no está vinculado. Genera el código aquí y escríbelo en la web.',
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
        $dispositivo = VinculacionDosFactores::dispositivoVinculado(
            $request->user(),
            $request->user()->currentAccessToken()->id,
            $this->dispositivoId($request),
        );

        $dispositivo?->delete();

        return response()->json(['data' => ['vinculado' => false]]);
    }
}
