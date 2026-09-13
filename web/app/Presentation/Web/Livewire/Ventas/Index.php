<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Ventas;

use App\Application\Inventario\UseCases\ListarMovimientosUseCase;
use App\Domain\Inventario\ValueObjects\TipoMovimiento;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Index extends Component
{
    public function mount(): void
    {
        $this->authorize('verVentas', MovimientoInventarioModel::class);
    }

    public function render(ListarMovimientosUseCase $listar): View
    {
        $ventas = $listar->ejecutar(TipoMovimiento::Venta);

        return view('livewire.ventas.index', [
            'ventas' => $ventas,
            'recaudado' => array_sum(array_map(
                static fn ($venta): float => $venta->total ?? 0.0,
                $ventas,
            )),
        ]);
    }
}
