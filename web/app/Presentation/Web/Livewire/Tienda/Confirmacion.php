<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Tienda;

use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.tienda')]
final class Confirmacion extends Component
{
    public int $pedidoId;

    public function mount(int $pedido): void
    {
        // Esta pantalla es publica y muestra nombre y telefono del cliente:
        // solo puede verla el visitante que acaba de hacer ese pedido, no
        // cualquiera que pruebe numeros de pedido en la URL.
        abort_unless(
            in_array($pedido, session('tienda.pedidos', []), true),
            403,
            'Este pedido no es tuyo.',
        );

        $this->pedidoId = $pedido;
    }

    public function render(ObtenerPedidoUseCase $obtener): View
    {
        return view('livewire.tienda.confirmacion', [
            'pedido' => $obtener->ejecutar($this->pedidoId),
        ]);
    }
}
