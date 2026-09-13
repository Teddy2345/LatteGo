<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Produccion;

use App\Application\Produccion\DTOs\ProduccionData;
use App\Application\Produccion\UseCases\ListarProduccionUseCase;
use App\Infrastructure\Produccion\Models\ProduccionModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
final class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';
    public function mount(): void
    {
        $this->authorize('viewAny', ProduccionModel::class);
    }

    public function render(ListarProduccionUseCase $listar): View
    {
        $paginador = ProduccionModel::query()->orderByDesc('fecha')->paginate(8);

        $mapped = $paginador->getCollection()->map(function (ProduccionModel $m): ProduccionData {
            return new ProduccionData(
                id: $m->id,
                fecha: $m->fecha->format('Y-m-d'),
                litrosProcesados: (float) $m->litros_procesados,
                quesosProducidos: (int) $m->quesos_producidos,
                rendimientoPorcentaje: (float) $m->rendimiento_porcentaje,
                rendimientoEnRangoEsperado: true,
                jefaProduccionId: $m->jefa_produccion_id,
                observaciones: $m->observaciones,
            );
        });

        $paginador->setCollection($mapped);

        return view('livewire.produccion.index', [
            'producciones' => $paginador,
        ]);
    }
}
