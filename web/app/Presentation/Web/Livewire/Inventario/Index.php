<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Inventario;

use App\Application\Inventario\UseCases\ListarMovimientosUseCase;
use App\Application\Inventario\UseCases\ListarProductosConStockUseCase;
use App\Domain\Inventario\ValueObjects\TipoMovimiento;
use App\Domain\Inventario\ValueObjects\TipoProducto;
use App\Infrastructure\Inventario\Models\ProductoModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Index extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', ProductoModel::class);
    }

    public function render(
        ListarProductosConStockUseCase $productos,
        ListarMovimientosUseCase $movimientos,
    ): View {
        $terminados = $productos->ejecutar(soloActivos: false, tipo: TipoProducto::ProductoTerminado);
        $insumos = $productos->ejecutar(soloActivos: false, tipo: TipoProducto::Insumo);
        $transformaciones = $movimientos->ejecutar(TipoMovimiento::Produccion);

        $entradasInsumo = $movimientos->ejecutar(TipoMovimiento::Compra);
        $salidasInsumo = $movimientos->ejecutar(TipoMovimiento::UsoProduccion);
        $movimientosInsumo = array_slice(
            array_merge($entradasInsumo, $salidasInsumo),
            0,
            10,
        );
        usort($movimientosInsumo, static fn ($a, $b) => strcmp($b->fecha, $a->fecha));

        return view('livewire.inventario.index', [
            'productosTerminados' => $terminados,
            'insumos' => $insumos,
            'transformaciones' => array_slice($transformaciones, 0, 10),
            'movimientosInsumo' => $movimientosInsumo,
            'litrosProcesados' => array_sum(array_map(
                static fn ($movimiento): float => $movimiento->litrosProcesados ?? 0.0,
                $transformaciones,
            )),
            'insumosStockBajo' => count(array_filter($insumos, static fn ($i) => $i->stockBajo)),
        ]);
    }
}
