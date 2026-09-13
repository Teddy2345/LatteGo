<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Produccion;

use App\Application\Produccion\DTOs\RegistrarDespachoData;
use App\Application\Produccion\UseCases\ListarProduccionUseCase;
use App\Application\Produccion\UseCases\RegistrarDespachoUseCase;
use App\Domain\Produccion\Exceptions\ProduccionException;
use App\Infrastructure\Produccion\Models\DespachoModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
final class DespachoFormulario extends Component
{
    #[Validate('required|integer')]
    public ?int $produccionId = null;

    #[Validate('required|integer|min:0')]
    public int $quesosRecibidos = 0;

    #[Validate('required|integer|min:0')]
    public int $quesosDespachados = 0;

    #[Validate('nullable|string|max:1000')]
    public ?string $observaciones = null;

    public string $error = '';

    public function mount(): void
    {
        $this->authorize('create', DespachoModel::class);
    }

    public function guardar(RegistrarDespachoUseCase $registrar): void
    {
        $this->authorize('create', DespachoModel::class);

        $this->validate();
        $this->error = '';

        try {
            $registrar->ejecutar(new RegistrarDespachoData(
                produccionId: $this->produccionId,
                despachadorId: auth()->id(),
                quesosRecibidos: $this->quesosRecibidos,
                quesosDespachados: $this->quesosDespachados,
                observaciones: $this->observaciones,
            ));
        } catch (ProduccionException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->redirect(route('produccion.index'), navigate: true);
    }

    public function render(ListarProduccionUseCase $listarProduccion): View
    {
        return view('livewire.produccion.despacho-formulario', [
            'producciones' => $listarProduccion->ejecutar(),
        ]);
    }
}
