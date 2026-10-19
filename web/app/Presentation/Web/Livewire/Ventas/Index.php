<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Ventas;

use App\Application\Comprobantes\UseCases\EmitirComprobanteUseCase;
use App\Application\Inventario\DTOs\MovimientoInventarioData;
use App\Application\Inventario\UseCases\ListarMovimientosUseCase;
use App\Domain\Comprobantes\Repositories\ComprobanteRepository;
use App\Domain\Inventario\ValueObjects\TipoMovimiento;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
final class Index extends Component
{
    /** Venta para la que se está emitiendo el comprobante, si hay una abierta. */
    #[Locked]
    public ?int $ventaId = null;

    public string $tipoComprobante = 'boleta';

    public string $clienteNombre = '';

    public string $clienteDocumento = '';

    public string $errorComprobante = '';

    /**
     * El comprobante recien emitido desde esta pantalla. Va en una
     * propiedad y no en un flash porque Livewire responde sin recargar la
     * pagina, y un flash no se llega a ver.
     */
    public ?int $recienEmitido = null;

    public function mount(): void
    {
        $this->authorize('verVentas', MovimientoInventarioModel::class);
    }

    /**
     * Abre el cuadro para emitir el comprobante de una venta que se registró
     * sin él. El importe no se pide: sale de la venta, no del formulario.
     */
    public function emitirComprobante(int $ventaId): void
    {
        $this->authorize('vender', MovimientoInventarioModel::class);

        $this->ventaId = $ventaId;
        $this->recienEmitido = null;
        $this->tipoComprobante = 'boleta';
        $this->clienteDocumento = '';
        $this->errorComprobante = '';

        $venta = MovimientoInventarioModel::query()->findOrFail($ventaId);
        $this->clienteNombre = $venta->cliente ?: 'Cliente de mostrador';
    }

    public function cerrarComprobante(): void
    {
        $this->ventaId = null;
        $this->errorComprobante = '';
    }

    public function confirmarComprobante(EmitirComprobanteUseCase $emitir): void
    {
        $this->authorize('vender', MovimientoInventarioModel::class);

        $venta = MovimientoInventarioModel::query()->findOrFail($this->ventaId);
        $this->errorComprobante = '';

        try {
            $comprobante = $emitir->ejecutar([
                'tipo' => $this->tipoComprobante,
                'fecha' => $venta->fecha->format('Y-m-d'),
                'clienteNombre' => $this->clienteNombre ?: 'Cliente de mostrador',
                'clienteDocumento' => $this->clienteDocumento,
                'total' => round((float) $venta->cantidad * (float) $venta->precio_unitario, 2),
                'movimientoInventarioId' => $venta->id,
            ], (int) auth()->id());
        } catch (ValidationException $e) {
            $this->errorComprobante = $e->validator->errors()->first();

            return;
        }

        $this->ventaId = null;
        $this->recienEmitido = (int) $comprobante['id'];
    }

    public function render(ListarMovimientosUseCase $listar, ComprobanteRepository $comprobantes): View
    {
        $ventas = $listar->ejecutar(TipoMovimiento::Venta);

        return view('livewire.ventas.index', [
            'ventas' => $ventas,
            'comprobantes' => $comprobantes->deVentas(array_map(
                static fn (MovimientoInventarioData $venta): int => $venta->id,
                $ventas,
            )),
            'recaudado' => array_sum(array_map(
                static fn ($venta): float => $venta->total ?? 0.0,
                $ventas,
            )),
        ]);
    }
}
