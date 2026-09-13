<div class="max-w-5xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-bosque">Pedidos</h1>
        <p class="text-sm text-piedra mt-1">Pedidos hechos desde la tienda web.</p>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach (['pendiente' => 'Pendientes', 'confirmado' => 'Confirmados', 'entregado' => 'Entregados', 'cancelado' => 'Cancelados', '' => 'Todos'] as $valor => $etiqueta)
            <button wire:click="$set('filtro', '{{ $valor }}')"
                    class="px-4 py-2 rounded-xl text-sm font-medium border transition-colors {{ $filtro === $valor ? 'bg-campo text-white border-campo' : 'bg-white text-bosque border-linea hover:bg-crema' }}">
                {{ $etiqueta }}
            </button>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl border border-linea shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-linea text-sm">
                <thead class="bg-crema">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Fecha</th>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Cliente</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Items</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Total</th>
                        <th class="px-4 py-3 text-center font-medium text-piedra text-xs uppercase tracking-wide">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($pedidos as $pedido)
                        <tr wire:key="pedido-{{ $pedido->id }}" class="hover:bg-crema">
                            <td class="px-4 py-3 text-piedra">{{ $pedido->fecha }}</td>
                            <td class="px-4 py-3 font-medium text-bosque">{{ $pedido->clienteNombre }}</td>
                            <td class="px-4 py-3 text-right text-piedra">{{ count($pedido->items) }}</td>
                            <td class="px-4 py-3 text-right font-medium text-bosque">S/ {{ number_format($pedido->total, 2) }}</td>
                            <td class="px-4 py-3 text-center">
                                <x-pedidos.estado-badge :estado="$pedido->estado" />
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('pedidos.detalle', $pedido->id) }}" wire:navigate class="text-campo hover:underline font-medium">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-piedra">No hay pedidos en este estado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
