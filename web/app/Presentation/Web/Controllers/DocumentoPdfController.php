<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Produccion\UseCases\ObtenerFichaProduccionUseCase;
use App\Domain\Comprobantes\Repositories\ComprobanteRepository;
use App\Domain\Comprobantes\ValueObjects\TipoComprobante;
use App\Infrastructure\Pedidos\Models\PedidoModel;
use App\Infrastructure\Produccion\Models\ProduccionModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Los papeles que la planta entrega o archiva: la boleta/factura de una
 * venta y la ficha de un lote de produccion. Se arman con la misma
 * plantilla que se veria en pantalla, pero salen como PDF descargable.
 */
final class DocumentoPdfController
{
    public function comprobante(int $comprobante, ComprobanteRepository $comprobantes): Response
    {
        $documento = $comprobantes->buscar($comprobante);

        $this->exigirAccesoAlComprobante($documento);

        $tipo = TipoComprobante::desde($documento['tipo']);

        $pdf = Pdf::loadView('pdf.comprobante', [
            'documento' => $documento,
            'tipo' => $tipo,
            'lineas' => $comprobantes->lineas($comprobante),
            'planta' => config('ecolactea.planta'),
        ])->setPaper('a4');

        return $pdf->download($tipo->serie().'-'.str_pad((string) $documento['numero'], 6, '0', STR_PAD_LEFT).'.pdf');
    }

    public function fichaProduccion(int $produccion, ObtenerFichaProduccionUseCase $fichas): Response
    {
        Gate::authorize('view', ProduccionModel::class);

        $ficha = $fichas->ejecutar($produccion);

        $pdf = Pdf::loadView('pdf.ficha-produccion', [
            'ficha' => $ficha,
            'planta' => config('ecolactea.planta'),
        ])->setPaper('a4');

        return $pdf->download('produccion-'.$produccion.'.pdf');
    }

    /**
     * El comprobante lleva los datos del cliente, asi que solo lo abre quien
     * comercializa o consulta reportes; el repartidor, unicamente el del
     * pedido que el mismo llevo.
     */
    private function exigirAccesoAlComprobante(array $documento): void
    {
        $usuario = auth()->user();

        if ($usuario->can('ventas.registrar') || $usuario->can('reportes.ver')) {
            return;
        }

        abort_unless($usuario->can('pedidos.repartir') && $documento['pedido_id'] !== null, 403);

        $pedido = PedidoModel::query()->findOrFail($documento['pedido_id']);

        abort_if($pedido->repartidor_id !== $usuario->id, 403, 'Este pedido no te fue asignado.');
    }
}
