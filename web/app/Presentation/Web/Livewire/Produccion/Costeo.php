<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Produccion;

use App\Application\Costeo\UseCases\CalcularCostoProduccionUseCase;
use App\Infrastructure\Produccion\Models\ProduccionModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Costeo extends Component
{
    public int $produccionId;

    public function mount(int $produccion): void
    {
        $this->authorize('view', ProduccionModel::class);

        $this->produccionId = $produccion;
    }

    public function render(CalcularCostoProduccionUseCase $calcular): View
    {
        return view('livewire.produccion.costeo', [
            'costo' => $calcular->ejecutar($this->produccionId),
        ]);
    }
}
