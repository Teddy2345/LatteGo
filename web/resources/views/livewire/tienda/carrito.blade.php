<div class="max-w-3xl mx-auto px-5 py-10 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-bosque">Tu carrito</h1>
        <a href="{{ route('tienda.index') }}" wire:navigate class="text-sm text-campo hover:underline">&larr; Seguir comprando</a>
    </div>

    @if (count($lineas) === 0)
        <div class="bg-white rounded-2xl border border-linea shadow-sm p-10 text-center text-piedra">
            Tu carrito está vacío.
            <div class="mt-4">
                <a href="{{ route('tienda.index') }}" wire:navigate class="text-campo font-medium hover:underline">Ver productos</a>
            </div>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-linea shadow-sm divide-y divide-linea">
            @foreach ($lineas as $linea)
                <div class="p-5 flex items-center gap-4" wire:key="carrito-{{ $linea['producto']->id }}">
                    <div class="h-14 w-14 rounded-lg bg-salvia shrink-0 overflow-hidden flex items-center justify-center">
                        @if ($linea['producto']->fotoPath)
                            <img src="{{ Illuminate\Support\Facades\Storage::url($linea['producto']->fotoPath) }}" class="h-full w-full object-cover">
                        @else
                            <svg class="h-6 w-6 text-campo" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3.75 3.75 7.5m16.5 0L12 11.25M20.25 7.5v9L12 20.25m0-9L3.75 7.5m8.25 3.75v9M3.75 7.5v9L12 20.25" /></svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-bosque">{{ $linea['producto']->nombre }}</p>
                        <p class="text-xs text-piedra">S/ {{ number_format($linea['producto']->precioReferencia, 2) }} / {{ $linea['producto']->unidad }}</p>
                    </div>
                    <input type="number" min="0.01" step="0.01" value="{{ $linea['cantidad'] }}"
                           wire:change="actualizar({{ $linea['producto']->id }}, $event.target.value)"
                           class="w-20 rounded-lg border-linea shadow-sm text-sm text-center focus:border-campo focus:ring-campo">
                    <p class="w-24 text-right font-semibold text-bosque">S/ {{ number_format($linea['subtotal'], 2) }}</p>
                    <button wire:click="quitar({{ $linea['producto']->id }})" class="text-piedra hover:text-red-600" aria-label="Quitar">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between bg-white rounded-2xl border border-linea shadow-sm p-5">
            <p class="text-piedra">Total</p>
            <p class="text-2xl font-bold text-bosque">S/ {{ number_format($total, 2) }}</p>
        </div>

        <a href="{{ route('tienda.checkout') }}" wire:navigate
           class="block text-center bg-campo text-white px-4 py-3 rounded-xl text-sm font-semibold hover:bg-bosque transition-colors">
            Continuar con la compra
        </a>
    @endif
</div>
