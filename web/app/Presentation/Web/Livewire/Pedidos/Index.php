<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Pedidos;

use App\Application\Pedidos\UseCases\ListarPedidosUseCase;
use App\Domain\Pedidos\ValueObjects\EstadoPedido;
use App\Infrastructure\Pedidos\Models\PedidoModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
final class Index extends Component
{
    #[Url]
    public string $filtro = 'pendiente';

    /**
     * Quien solo reparte (sin ventas.registrar ni reportes.ver) ve
     * unicamente sus propios pedidos asignados, sin filtro de estado por
     * defecto: le interesa tanto lo que le falta entregar como su
     * historial reciente.
     */
    public bool $soloAsignados = false;

    public function mount(): void
    {
        $this->authorize('viewAny', PedidoModel::class);

        $user = auth()->user();
        $this->soloAsignados = ! $user->can('ventas.registrar') && ! $user->can('reportes.ver');

        if ($this->soloAsignados) {
            $this->filtro = '';
        }
    }

    public function render(ListarPedidosUseCase $listar): View
    {
        $estado = EstadoPedido::tryFrom($this->filtro);

        return view('livewire.pedidos.index', [
            'pedidos' => $listar->ejecutar($estado, $this->soloAsignados ? auth()->id() : null),
        ]);
    }
}
