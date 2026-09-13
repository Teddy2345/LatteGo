<div class="max-w-3xl mx-auto px-5 py-10 space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-bosque">Finalizar compra</h1>
        <p class="text-sm text-piedra mt-1">Deja tus datos y nuestro equipo confirmará tu pedido por teléfono.</p>
    </div>

    @if ($error !== '')
        <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $error }}</div>
    @endif

    @if (count($lineas) === 0)
        <div class="bg-white rounded-2xl border border-linea shadow-sm p-10 text-center text-piedra">
            Tu carrito está vacío.
            <div class="mt-4">
                <a href="{{ route('tienda.index') }}" wire:navigate class="text-campo font-medium hover:underline">Ver productos</a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <form wire:submit="confirmar" class="bg-white rounded-2xl border border-linea shadow-sm p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-bosque mb-1">Nombre completo</label>
                    <input type="text" wire:model="nombre" class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                    @error('nombre') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-bosque mb-1">Teléfono</label>
                    <input type="text" wire:model="telefono" class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                    @error('telefono') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-bosque mb-1">Dirección de entrega</label>
                    <input type="text" wire:model="direccion" class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                    @error('direccion') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-bosque mb-1">Observaciones (opcional)</label>
                    <textarea wire:model="observaciones" rows="2" class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo"></textarea>
                </div>
                <button type="submit" class="w-full bg-campo text-white px-4 py-3 rounded-xl text-sm font-semibold hover:bg-bosque transition-colors">
                    Confirmar pedido
                </button>
                <p class="text-xs text-piedra text-center">No se realiza ningún cobro en este paso.</p>
            </form>

            <div class="bg-white rounded-2xl border border-linea shadow-sm p-6 space-y-3 h-fit">
                <h2 class="text-sm font-semibold text-bosque">Resumen del pedido</h2>
                @foreach ($lineas as $linea)
                    <div class="flex justify-between text-sm">
                        <span class="text-piedra">{{ $linea['cantidad'] }} &times; {{ $linea['producto']->nombre }}</span>
                        <span class="text-bosque font-medium">S/ {{ number_format($linea['subtotal'], 2) }}</span>
                    </div>
                @endforeach
                <div class="border-t border-linea pt-3 flex justify-between">
                    <span class="font-semibold text-bosque">Total</span>
                    <span class="font-bold text-bosque text-lg">S/ {{ number_format($total, 2) }}</span>
                </div>
            </div>
        </div>
    @endif
</div>
