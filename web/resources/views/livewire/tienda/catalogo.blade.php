<div class="max-w-5xl mx-auto px-5 py-10 space-y-8">
    <div class="text-center space-y-2">
        <h1 class="text-3xl font-bold text-bosque">Nuestros productos</h1>
        <p class="text-piedra">Quesos y derivados lácteos directo de la planta de Huata.</p>
    </div>

    @if ($mensaje !== '')
        <div class="rounded-xl bg-salvia text-bosque px-4 py-3 text-sm text-center max-w-md mx-auto">{{ $mensaje }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($productos as $producto)
            <div class="bg-white rounded-2xl border border-linea shadow-sm overflow-hidden flex flex-col">
                <div class="h-40 bg-salvia flex items-center justify-center">
                    @if ($producto->fotoPath)
                        <img src="{{ Illuminate\Support\Facades\Storage::url($producto->fotoPath) }}" alt="{{ $producto->nombre }}"
                             class="h-full w-full object-cover">
                    @else
                        <svg class="h-14 w-14 text-campo" fill="none" viewBox="0 0 24 24" stroke-width="1.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3.75 3.75 7.5m16.5 0L12 11.25M20.25 7.5v9L12 20.25m0-9L3.75 7.5m8.25 3.75v9M3.75 7.5v9L12 20.25" /></svg>
                    @endif
                </div>
                <div class="p-5 flex-1 flex flex-col gap-2">
                    <h2 class="font-semibold text-bosque">{{ $producto->nombre }}</h2>
                    @if ($producto->descripcion)
                        <p class="text-xs text-piedra line-clamp-3">{{ $producto->descripcion }}</p>
                    @endif
                    <p class="text-xl font-bold text-campo mt-auto">S/ {{ number_format($producto->precioReferencia, 2) }}
                        <span class="text-xs font-normal text-piedra">/ {{ $producto->unidad }}</span>
                    </p>

                    @if ($producto->stock > 0)
                        <div class="flex items-center gap-2 pt-1">
                            <input type="number" min="1" step="1" value="1" wire:model="cantidades.{{ $producto->id }}"
                                   class="w-16 rounded-lg border-linea shadow-sm text-sm text-center focus:border-campo focus:ring-campo">
                            <button wire:click="agregar({{ $producto->id }})"
                                    class="flex-1 bg-campo text-white px-3 py-2 rounded-lg text-sm font-medium hover:bg-bosque transition-colors">
                                Agregar
                            </button>
                        </div>
                    @else
                        <p class="text-xs font-medium text-amber-700 pt-1">Agotado</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="sm:col-span-2 lg:col-span-3 text-center text-piedra py-16">
                Todavía no hay productos disponibles en la tienda.
            </div>
        @endforelse
    </div>
</div>
