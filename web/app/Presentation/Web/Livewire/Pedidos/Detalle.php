<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Pedidos;

use App\Application\Pedidos\DTOs\EntregarPedidoData;
use App\Application\Pedidos\UseCases\AsignarRepartidorUseCase;
use App\Application\Pedidos\UseCases\CancelarPedidoUseCase;
use App\Application\Pedidos\UseCases\ConfirmarPedidoUseCase;
use App\Application\Pedidos\UseCases\EntregarPedidoUseCase;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Domain\Pedidos\Exceptions\PedidoException;
use App\Infrastructure\Pedidos\Models\PedidoModel;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
final class Detalle extends Component
{
    public int $pedidoId;

    public ?int $repartidorId = null;

    #[Validate('required|in:efectivo,transferencia,qr')]
    public string $metodoPago = 'efectivo';

    #[Validate('required|numeric|min:0.01')]
    public float $montoCobrado = 0.0;

    #[Validate('required|date')]
    public string $fechaEntrega = '';

    public string $error = '';

    public function mount(int $pedido): void
    {
        $this->authorize('view', PedidoModel::class);
        $this->autorizarPedidoPropio($pedido);

        $this->pedidoId = $pedido;
        $this->fechaEntrega = now()->format('Y-m-d');
    }

    /**
     * Quien solo reparte no puede abrir ni cobrar el pedido de otro
     * repartidor: el dinero cobrado quedaria a nombre de quien no hizo la
     * entrega. Ventas y quien ve reportes si acceden a todos.
     */
    private function autorizarPedidoPropio(int $pedidoId): void
    {
        $user = auth()->user();

        if ($user->can('ventas.registrar') || $user->can('reportes.ver')) {
            return;
        }

        $pedido = PedidoModel::query()->findOrFail($pedidoId);

        abort_if($pedido->repartidor_id !== $user->id, 403, 'Este pedido no te fue asignado.');
    }

    public function confirmar(ConfirmarPedidoUseCase $confirmar): void
    {
        $this->authorize('revisar', PedidoModel::class);

        try {
            $confirmar->ejecutar($this->pedidoId);
        } catch (PedidoException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function cancelar(CancelarPedidoUseCase $cancelar): void
    {
        $this->authorize('revisar', PedidoModel::class);

        try {
            $cancelar->ejecutar($this->pedidoId);
        } catch (PedidoException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function asignar(AsignarRepartidorUseCase $asignar): void
    {
        $this->authorize('asignarRepartidor', PedidoModel::class);

        $this->validate(['repartidorId' => 'required|integer|exists:users,id']);
        $this->error = '';

        try {
            $asignar->ejecutar($this->pedidoId, (int) $this->repartidorId);
        } catch (PedidoException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function entregar(EntregarPedidoUseCase $entregar): void
    {
        $this->authorize('entregar', PedidoModel::class);
        // Se revalida aqui porque el pedido pudo reasignarse a otro
        // repartidor mientras esta pantalla seguia abierta.
        $this->autorizarPedidoPropio($this->pedidoId);

        $this->validate([
            'metodoPago' => 'required|in:efectivo,transferencia,qr',
            'montoCobrado' => 'required|numeric|min:0.01',
            'fechaEntrega' => 'required|date',
        ]);
        $this->error = '';

        try {
            $entregar->ejecutar(new EntregarPedidoData(
                id: $this->pedidoId,
                metodoPago: $this->metodoPago,
                montoCobrado: $this->montoCobrado,
                fechaEntrega: $this->fechaEntrega,
            ));
        } catch (PedidoException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render(ObtenerPedidoUseCase $obtener): View
    {
        $pedido = $obtener->ejecutar($this->pedidoId);

        if ($this->montoCobrado === 0.0) {
            $this->montoCobrado = $pedido->total;
        }

        return view('livewire.pedidos.detalle', [
            'pedido' => $pedido,
            'repartidores' => User::role('repartidor')->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
