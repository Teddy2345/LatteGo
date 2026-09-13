<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">Pedido #{{ $pedido->id }}</h1>
            <p class="text-sm text-piedra mt-1">{{ $pedido->fecha }}</p>
        </div>
        <a href="{{ route('pedidos.index') }}" wire:navigate class="text-sm text-piedra hover:underline">
            &larr; Volver al listado
        </a>
    </div>

    @if ($error !== '')
        <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $error }}</div>
    @endif

    <div class="bg-white rounded-2xl border border-linea shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-bosque">Datos del cliente</h2>
            <x-pedidos.estado-badge :estado="$pedido->estado" />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-piedra text-xs">Nombre</p>
                <p class="text-bosque">{{ $pedido->clienteNombre }}</p>
            </div>
            <div>
                <p class="text-piedra text-xs">Teléfono</p>
                <p class="text-bosque">{{ $pedido->clienteTelefono }}</p>
            </div>
            <div>
                <p class="text-piedra text-xs">Dirección</p>
                <p class="text-bosque">{{ $pedido->clienteDireccion }}</p>
            </div>
        </div>
        @if ($pedido->observaciones)
            <div>
                <p class="text-piedra text-xs">Observaciones</p>
                <p class="text-bosque text-sm">{{ $pedido->observaciones }}</p>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-2xl border border-linea shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-linea">
            <h2 class="text-sm font-semibold text-bosque">Productos pedidos</h2>
        </div>
        <table class="min-w-full divide-y divide-linea text-sm">
            <thead class="bg-crema">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Producto</th>
                    <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Cantidad</th>
                    <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Precio</th>
                    <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-linea">
                @foreach ($pedido->items as $item)
                    <tr>
                        <td class="px-4 py-3 text-bosque">{{ $item->nombreProducto }}</td>
                        <td class="px-4 py-3 text-right text-piedra">{{ number_format($item->cantidad, 2) }}</td>
                        <td class="px-4 py-3 text-right text-piedra">S/ {{ number_format($item->precioUnitario, 2) }}</td>
                        <td class="px-4 py-3 text-right font-medium text-bosque">S/ {{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-crema">
                    <td colspan="3" class="px-4 py-3 text-right font-semibold text-bosque">Total</td>
                    <td class="px-4 py-3 text-right font-semibold text-bosque">S/ {{ number_format($pedido->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if ($pedido->estado === 'pendiente')
        @can('revisar', App\Infrastructure\Pedidos\Models\PedidoModel::class)
            <div class="flex justify-end gap-3">
                <button wire:click="cancelar" wire:confirm="¿Cancelar este pedido?"
                        class="px-4 py-2 rounded-xl text-sm font-medium border border-linea text-bosque hover:bg-crema">
                    Cancelar pedido
                </button>
                <button wire:click="confirmar"
                        class="bg-campo text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-bosque transition-colors">
                    Confirmar pedido
                </button>
            </div>
        @endcan
    @endif

    @if ($pedido->estado === 'confirmado')
        <div class="bg-white rounded-2xl border border-linea shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-semibold text-bosque">Reparto y cobro</h2>

            @if ($pedido->repartidorId === null)
                @can('asignarRepartidor', App\Infrastructure\Pedidos\Models\PedidoModel::class)
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-bosque mb-1">Asignar repartidor</label>
                            <select wire:model="repartidorId" class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                                <option value="">Seleccione...</option>
                                @foreach ($repartidores as $repartidor)
                                    <option value="{{ $repartidor->id }}">{{ $repartidor->name }}</option>
                                @endforeach
                            </select>
                            @error('repartidorId') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <button wire:click="asignar" class="bg-campo text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-bosque transition-colors">
                            Asignar
                        </button>
                    </div>
                @else
                    <p class="text-sm text-piedra">Todavía no se asignó un repartidor.</p>
                @endcan
            @else
                <p class="text-sm text-piedra">Repartidor asignado: <span class="text-bosque font-medium">{{ $repartidores->firstWhere('id', $pedido->repartidorId)?->name ?? "Usuario #{$pedido->repartidorId}" }}</span></p>

                @can('entregar', App\Infrastructure\Pedidos\Models\PedidoModel::class)
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-linea">
                        <div>
                            <label class="block text-sm font-medium text-bosque mb-1">Método de pago</label>
                            <select wire:model="metodoPago" class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="qr">QR</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-bosque mb-1">Monto cobrado</label>
                            <input type="number" step="0.01" min="0.01" wire:model="montoCobrado"
                                   class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                            @error('montoCobrado') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-bosque mb-1">Fecha de entrega</label>
                            <input type="date" wire:model="fechaEntrega"
                                   class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button wire:click="entregar" class="bg-campo text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-bosque transition-colors">
                            Registrar entrega y cobro
                        </button>
                    </div>
                @endcan
            @endif
        </div>
    @endif

    @if ($pedido->estado === 'entregado')
        <div class="bg-white rounded-2xl border border-linea shadow-sm p-6 space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-bosque">Reparto y cobro</h2>
                <a href="{{ route('pedidos.comprobante', $pedido->id) }}" target="_blank" class="text-sm text-campo hover:underline font-medium">
                    Ver comprobante
                </a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-piedra text-xs">Repartidor</p>
                    <p class="text-bosque">{{ $repartidores->firstWhere('id', $pedido->repartidorId)?->name ?? "Usuario #{$pedido->repartidorId}" }}</p>
                </div>
                <div>
                    <p class="text-piedra text-xs">Método de pago</p>
                    <p class="text-bosque capitalize">{{ $pedido->metodoPago }}</p>
                </div>
                <div>
                    <p class="text-piedra text-xs">Monto cobrado</p>
                    <p class="text-bosque">S/ {{ number_format($pedido->montoCobrado, 2) }}</p>
                </div>
                <div>
                    <p class="text-piedra text-xs">Fecha de entrega</p>
                    <p class="text-bosque">{{ $pedido->fechaEntrega }}</p>
                </div>
            </div>
        </div>
    @endif
</div>
