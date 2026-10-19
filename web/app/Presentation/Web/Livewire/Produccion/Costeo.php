<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Produccion;

use App\Application\Costeo\UseCases\CalcularCostoProduccionUseCase;
use App\Application\Produccion\UseCases\ObtenerProduccionUseCase;
use App\Infrastructure\Produccion\Models\ProduccionModel;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
final class Costeo extends Component
{
    #[Locked]
    public int $produccionId;

    public function mount(int $produccion): void
    {
        $this->authorize('view', ProduccionModel::class);

        $this->produccionId = $produccion;
    }

    public function render(CalcularCostoProduccionUseCase $calcular, ObtenerProduccionUseCase $obtener): View
    {
        return view('livewire.produccion.costeo', [
            'costo' => $calcular->ejecutar($this->produccionId),
            'resultados' => $obtener->ejecutar($this->produccionId)->resultados,
            // Todas las recetas del lote, no solo la de la cabecera: una
            // tanda puede sacar dos productos y nombrar una sola enganaria.
            'recetas' => DB::table('produccion_productos')
                ->join('recetas', 'recetas.id', '=', 'produccion_productos.receta_id')
                ->where('produccion_productos.produccion_id', $this->produccionId)
                ->distinct()->pluck('recetas.nombre')->all(),
        ]);
    }
}
