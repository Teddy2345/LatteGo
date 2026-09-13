<div class="max-w-xl mx-auto px-6 py-10 print:px-0 print:py-0">
    <div class="flex items-start justify-between mb-8">
        <div>
            <h1 class="text-xl font-bold">{{ config('app.name') }}</h1>
            <p class="text-sm text-piedra">Comprobante de pedido</p>
        </div>
        <button onclick="window.print()" class="print:hidden bg-campo text-white px-4 py-2 rounded-md text-sm hover:bg-bosque">
            Imprimir
        </button>
    </div>

    <dl class="grid grid-cols-2 gap-y-3 text-sm border-t border-b border-linea py-4 mb-4">
        <dt class="text-piedra">Cliente</dt>
        <dd class="text-right font-medium">{{ $pedido->clienteNombre }}</dd>

        <dt class="text-piedra">Teléfono</dt>
        <dd class="text-right">{{ $pedido->clienteTelefono }}</dd>

        <dt class="text-piedra">Dirección</dt>
        <dd class="text-right">{{ $pedido->clienteDireccion }}</dd>

        <dt class="text-piedra">Fecha del pedido</dt>
        <dd class="text-right">{{ $pedido->fecha }}</dd>

        <dt class="text-piedra">Estado</dt>
        <dd class="text-right capitalize">{{ $pedido->estado }}</dd>

        @if ($repartidor)
            <dt class="text-piedra">Repartidor</dt>
            <dd class="text-right">{{ $repartidor->name }}</dd>
        @endif

        @if ($pedido->metodoPago)
            <dt class="text-piedra">Método de pago</dt>
            <dd class="text-right capitalize">{{ $pedido->metodoPago }}</dd>
        @endif

        @if ($pedido->fechaEntrega)
            <dt class="text-piedra">Fecha de entrega</dt>
            <dd class="text-right">{{ $pedido->fechaEntrega }}</dd>
        @endif
    </dl>

    <table class="w-full text-sm mb-4">
        <thead>
            <tr class="text-piedra text-left">
                <th class="pb-2">Producto</th>
                <th class="pb-2 text-right">Cant.</th>
                <th class="pb-2 text-right">Precio</th>
                <th class="pb-2 text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pedido->items as $item)
                <tr class="border-t border-linea">
                    <td class="py-2">{{ $item->nombreProducto }}</td>
                    <td class="py-2 text-right">{{ number_format($item->cantidad, 2) }}</td>
                    <td class="py-2 text-right">{{ number_format($item->precioUnitario, 2) }}</td>
                    <td class="py-2 text-right">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="flex items-center justify-between text-lg font-bold border-t border-linea pt-3">
        <span>Total</span>
        <span>S/ {{ number_format($pedido->total, 2) }}</span>
    </div>

    @if ($pedido->montoCobrado)
        <div class="flex items-center justify-between text-sm text-piedra mt-1">
            <span>Cobrado</span>
            <span>S/ {{ number_format($pedido->montoCobrado, 2) }}</span>
        </div>
    @endif

    <p class="text-xs text-piedra mt-8">Comprobante — Pedido #{{ $pedido->id }} — generado el {{ now()->format('Y-m-d H:i') }}</p>
</div>
