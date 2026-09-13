<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Pedidos;

use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Infrastructure\Pedidos\Models\PedidoModel;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.print')]
final class Comprobante extends Component
{
    public int $pedidoId;

    public function mount(int $pedido): void
    {
        $this->authorize('view', PedidoModel::class);

        $user = auth()->user();

        // Quien solo reparte imprime unicamente el comprobante de lo que el
        // mismo entrego: el comprobante lleva los datos del cliente.
        if (! $user->can('ventas.registrar') && ! $user->can('reportes.ver')) {
            $modelo = PedidoModel::query()->findOrFail($pedido);

            abort_if($modelo->repartidor_id !== $user->id, 403, 'Este pedido no te fue asignado.');
        }

        $this->pedidoId = $pedido;
    }

    public function render(ObtenerPedidoUseCase $obtener): View
    {
        $pedido = $obtener->ejecutar($this->pedidoId);

        return view('livewire.pedidos.comprobante', [
            'pedido' => $pedido,
            'repartidor' => $pedido->repartidorId === null ? null : User::find($pedido->repartidorId),
        ]);
    }
}
