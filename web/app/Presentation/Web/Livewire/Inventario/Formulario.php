<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Inventario;

use App\Application\Inventario\DTOs\RegistrarProduccionProductoData;
use App\Application\Inventario\UseCases\ListarProductosConStockUseCase;
use App\Application\Inventario\UseCases\RegistrarProduccionProductoUseCase;
use App\Domain\Inventario\Exceptions\InventarioException;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
final class Formulario extends Component
{
    #[Validate('required|integer|exists:productos,id')]
    public ?int $productoId = null;

    #[Validate('required|date')]
    public string $fecha = '';

    #[Validate('required|numeric|min:0.01')]
    public float $cantidad = 0.0;

    #[Validate('required|numeric|min:0.01')]
    public float $litrosProcesados = 0.0;

    #[Validate('nullable|string|max:1000')]
    public ?string $observaciones = null;

    public string $error = '';

    public function mount(): void
    {
        $this->authorize('transformar', MovimientoInventarioModel::class);

        $this->fecha = now()->format('Y-m-d');
    }

    public function guardar(RegistrarProduccionProductoUseCase $registrar): void
    {
        $this->authorize('transformar', MovimientoInventarioModel::class);

        $this->validate();
        $this->error = '';

        try {
            $registrar->ejecutar(new RegistrarProduccionProductoData(
                productoId: (int) $this->productoId,
                fecha: $this->fecha,
                cantidad: $this->cantidad,
                litrosProcesados: $this->litrosProcesados,
                observaciones: $this->observaciones,
                usuarioId: auth()->id(),
            ));
        } catch (InventarioException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->redirect(route('inventario.index'), navigate: true);
    }

    public function render(ListarProductosConStockUseCase $listar): View
    {
        return view('livewire.inventario.formulario', [
            'productos' => $listar->ejecutar(),
        ]);
    }
}
