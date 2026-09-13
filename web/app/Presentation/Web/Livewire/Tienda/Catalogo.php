<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Tienda;

use App\Application\Inventario\UseCases\ListarProductosConStockUseCase;
use App\Domain\Inventario\ValueObjects\TipoProducto;
use App\Presentation\Web\Livewire\Tienda\Concerns\InteractuaConCarrito;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.tienda')]
final class Catalogo extends Component
{
    use InteractuaConCarrito;

    /** @var array<int, int> cantidad elegida por producto, antes de agregar al carrito */
    public array $cantidades = [];

    public string $mensaje = '';

    public function agregar(int $productoId): void
    {
        $cantidad = (float) ($this->cantidades[$productoId] ?? 1);

        if ($cantidad <= 0) {
            return;
        }

        $carrito = $this->carritoActual();
        $carrito[$productoId] = ($carrito[$productoId] ?? 0.0) + $cantidad;
        $this->guardarCarrito($carrito);

        $this->mensaje = 'Se agregó al carrito.';
        $this->dispatch('carrito-actualizado');
    }

    public function render(ListarProductosConStockUseCase $listar): View
    {
        return view('livewire.tienda.catalogo', [
            'productos' => $listar->ejecutar(soloActivos: true, tipo: TipoProducto::ProductoTerminado),
            'totalEnCarrito' => array_sum($this->carritoActual()),
        ]);
    }
}
