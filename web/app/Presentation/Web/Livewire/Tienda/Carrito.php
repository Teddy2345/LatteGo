<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Tienda;

use App\Application\Inventario\UseCases\ListarProductosConStockUseCase;
use App\Presentation\Web\Livewire\Tienda\Concerns\InteractuaConCarrito;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.tienda')]
final class Carrito extends Component
{
    use InteractuaConCarrito;

    public function actualizar(int $productoId, string $cantidad): void
    {
        $carrito = $this->carritoActual();
        $valor = (float) str_replace(',', '.', $cantidad);

        if ($valor <= 0) {
            unset($carrito[$productoId]);
        } else {
            $carrito[$productoId] = $valor;
        }

        $this->guardarCarrito($carrito);
        $this->dispatch('carrito-actualizado');
    }

    public function quitar(int $productoId): void
    {
        $carrito = $this->carritoActual();
        unset($carrito[$productoId]);
        $this->guardarCarrito($carrito);
        $this->dispatch('carrito-actualizado');
    }

    public function render(ListarProductosConStockUseCase $listar): View
    {
        return view('livewire.tienda.carrito', $this->resolverCarrito($listar));
    }
}
