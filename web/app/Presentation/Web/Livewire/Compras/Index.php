<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Compras;

use App\Application\Compras\UseCases\ListarComprasUseCase;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Index extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', MovimientoInventarioModel::class);
    }

    public function render(ListarComprasUseCase $listar): View
    {
        $compras = $listar->ejecutar();

        return view('livewire.compras.index', [
            'compras' => $compras,
            'gastado' => round(array_sum(array_map(
                static fn (array $compra): float => (float) $compra['total'],
                $compras,
            )), 2),
        ]);
    }
}
