<div class="max-w-2xl mx-auto px-5 py-16 text-center space-y-6">
    <div class="h-16 w-16 rounded-full bg-salvia text-campo flex items-center justify-center mx-auto">
        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
    </div>
    <div>
        <h1 class="text-2xl font-bold text-bosque">¡Gracias, {{ $pedido->clienteNombre }}!</h1>
        <p class="text-piedra mt-1">Tu pedido #{{ $pedido->id }} fue recibido. Te contactaremos al {{ $pedido->clienteTelefono }} para confirmarlo.</p>
    </div>

    <div class="bg-white rounded-2xl border border-linea shadow-sm p-6 text-left space-y-3">
        @foreach ($pedido->items as $item)
            <div class="flex justify-between text-sm">
                <span class="text-piedra">{{ $item->cantidad }} &times; {{ $item->nombreProducto }}</span>
                <span class="text-bosque font-medium">S/ {{ number_format($item->subtotal, 2) }}</span>
            </div>
        @endforeach
        <div class="border-t border-linea pt-3 flex justify-between">
            <span class="font-semibold text-bosque">Total</span>
            <span class="font-bold text-bosque text-lg">S/ {{ number_format($pedido->total, 2) }}</span>
        </div>
    </div>

    <a href="{{ route('tienda.index') }}" wire:navigate class="inline-block text-campo font-medium hover:underline">
        Volver a la tienda
    </a>
</div>
