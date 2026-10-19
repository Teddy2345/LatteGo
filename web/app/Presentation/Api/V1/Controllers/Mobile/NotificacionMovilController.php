<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers\Mobile;

use App\Application\Notificacion\DTOs\NotificacionData;
use App\Application\Notificacion\UseCases\ContarNotificacionesNoLeidasUseCase;
use App\Application\Notificacion\UseCases\ListarNotificacionesUseCase;
use App\Application\Notificacion\UseCases\MarcarNotificacionLeidaUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * La campana de la app.
 *
 * El personal de planta ve las alertas generales y las suyas; el proveedor,
 * solo las suyas —ahi le llega el codigo de sus pedidos—, porque los avisos
 * de la planta no le incumben.
 */
final class NotificacionMovilController extends Controller
{
    public function index(
        Request $request,
        ListarNotificacionesUseCase $listar,
        ContarNotificacionesNoLeidasUseCase $contar,
    ): JsonResponse {
        $user = $request->user();
        $soloPropias = $user->hasRole('proveedor');

        $notificaciones = $listar->ejecutar((int) $user->id, 30, soloPropias: $soloPropias);

        return response()->json([
            'data' => [
                'no_leidas' => $contar->ejecutar((int) $user->id, soloPropias: $soloPropias),
                'notificaciones' => array_map(static fn (NotificacionData $n): array => [
                    'id' => $n->id,
                    'tipo' => $n->tipo,
                    'titulo' => $n->titulo,
                    'mensaje' => $n->mensaje,
                    'nivel' => $n->nivel,
                    'leida' => $n->leida,
                    'creada_en' => $n->creadaEn,
                    'datos' => $n->datos,
                ], $notificaciones),
            ],
        ]);
    }

    public function marcarLeida(Request $request, int $notificacion, MarcarNotificacionLeidaUseCase $marcar): JsonResponse
    {
        $marcar->ejecutar($notificacion, (int) $request->user()->id);

        return response()->json(['data' => ['leida' => true]]);
    }
}
