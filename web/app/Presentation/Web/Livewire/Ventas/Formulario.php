<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Ventas;

use App\Application\Inventario\DTOs\RegistrarVentaData;
use App\Application\Inventario\UseCases\ListarProductosConStockUseCase;
use App\Application\Inventario\UseCases\RegistrarVentaUseCase;
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
    public float $precioUnitario = 0.0;

    #[Validate('nullable|string|max:160')]
    public ?string $cliente = null;

    public string $error = '';

    public function mount(): void
    {
        $this->authorize('vender', MovimientoInventarioModel::class);

        $this->fecha = now()->format('Y-m-d');
    }

    /**
     * Al elegir producto se propone su precio de referencia, que el usuario
     * puede ajustar antes de confirmar la venta.
     */
    public function updatedProductoId(ListarProductosConStockUseCase $listar): void
    {
        foreach ($listar->ejecutar() as $producto) {
            if ($producto->id === (int) $this->productoId) {
                $this->precioUnitario = $producto->precioReferencia;

                return;
            }
        }
    }

    public function guardar(RegistrarVentaUseCase $registrar): void
    {
        $this->authorize('vender', MovimientoInventarioModel::class);

        $this->validate();
        $this->error = '';

        try {
            $registrar->ejecutar(new RegistrarVentaData(
                productoId: (int) $this->productoId,
                fecha: $this->fecha,
                cantidad: $this->cantidad,
                precioUnitario: $this->precioUnitario,
                cliente: $this->cliente,
                usuarioId: auth()->id(),
            ));
        } catch (InventarioException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->redirect(route('ventas.index'), navigate: true);
    }

    public function render(ListarProductosConStockUseCase $listar): View
    {
        return view('livewire.ventas.formulario', [
            'productos' => $listar->ejecutar(),
        ]);
    }
}
