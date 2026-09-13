<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Inventario;

use App\Application\Inventario\DTOs\RegistrarSalidaInsumoData;
use App\Application\Inventario\UseCases\ListarProductosConStockUseCase;
use App\Application\Inventario\UseCases\RegistrarSalidaInsumoUseCase;
use App\Domain\Inventario\Exceptions\InventarioException;
use App\Domain\Inventario\ValueObjects\TipoProducto;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
final class SalidaFormulario extends Component
{
    #[Validate('required|integer|exists:productos,id')]
    public ?int $productoId = null;

    #[Validate('required|date')]
    public string $fecha = '';

    #[Validate('required|numeric|min:0.01')]
    public float $cantidad = 0.0;

    #[Validate('nullable|string|max:255')]
    public ?string $motivo = null;

    public string $error = '';

    public function mount(): void
    {
        $this->authorize('registrarSalida', MovimientoInventarioModel::class);

        $this->fecha = now()->format('Y-m-d');
    }

    public function guardar(RegistrarSalidaInsumoUseCase $registrar): void
    {
        $this->authorize('registrarSalida', MovimientoInventarioModel::class);

        $this->validate();
        $this->error = '';

        try {
            $registrar->ejecutar(new RegistrarSalidaInsumoData(
                productoId: (int) $this->productoId,
                fecha: $this->fecha,
                cantidad: $this->cantidad,
                motivo: $this->motivo,
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
        return view('livewire.inventario.salida-formulario', [
            'productos' => $listar->ejecutar(tipo: TipoProducto::Insumo),
        ]);
    }
}
