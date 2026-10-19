<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers\Mobile;

use App\Application\Proveedor\DTOs\ProveedorData;
use App\Application\Proveedor\DTOs\RutaData;
use App\Application\Proveedor\UseCases\ListarProveedoresActivosUseCase;
use App\Application\Proveedor\UseCases\ListarRutasDisponiblesUseCase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Presentation\Support\AlcanceProveedores;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catalogo minimo que la app movil necesita para armar el formulario de
 * recepcion en campo: proveedores activos y zonas de acopio.
 *
 * Se autoriza con el permiso de registrar acopios y no con el de ver
 * proveedores: el acopiador debe poder elegir a quien le recibe la leche sin
 * tener acceso al padron completo. Por eso solo se exponen id y nombre.
 */
final class CatalogoController extends Controller
{
    public function __invoke(
        Request $request,
        ListarProveedoresActivosUseCase $proveedores,
        ListarRutasDisponiblesUseCase $rutas,
    ): JsonResponse {
        $this->authorize('create', AcopioModel::class);

        // Cada acopiador recibe solo a los proveedores de la ruta de su movilidad.
        $rutaPermitida = AlcanceProveedores::rutaPermitida($request->user());
        $delAlcance = array_values(array_filter(
            $proveedores->ejecutar(),
            static fn (ProveedorData $proveedor): bool => $rutaPermitida === null || $proveedor->rutaId === $rutaPermitida,
        ));

        return response()->json([
            'data' => [
                'sin_movilidad' => $rutaPermitida === 0,
                'proveedores' => array_map(
                    static fn (ProveedorData $proveedor): array => [
                        'id' => $proveedor->id,
                        'nombre' => $proveedor->nombre,
                    ],
                    $delAlcance,
                ),
                'zonas' => array_map(
                    static fn (RutaData $ruta): array => [
                        'id' => $ruta->id,
                        'nombre' => $ruta->nombre,
                    ],
                    $rutas->ejecutar(),
                ),
            ],
        ]);
    }
}
