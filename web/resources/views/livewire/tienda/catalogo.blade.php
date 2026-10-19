<div>
    <section class="relative overflow-hidden">
        <img src="{{ asset('img/campo-amanecer.jpg') }}" alt=""
             class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-r from-bosque/90 via-bosque/70 to-bosque/30"></div>

        <div class="relative max-w-5xl mx-auto px-5 py-14 sm:py-20">
            <p class="text-xs tracking-[0.3em] text-dorado font-medium">DE NUESTRA TIERRA</p>
            <h1 class="mt-3 text-3xl sm:text-5xl font-bold text-white leading-tight max-w-xl">
                Del campo, con cuidado.
            </h1>
            <p class="mt-4 text-white/90 max-w-lg">
                Quesos, yogures, leche y mantequilla elaborados en la planta de Huata con leche
                fresca de nuestros ganaderos. Haz tu pedido y te lo llevamos.
            </p>
            <a href="#productos"
               class="mt-7 inline-flex items-center gap-2 rounded-xl bg-dorado px-6 py-3 text-sm font-semibold text-bosque hover:bg-white transition-colors">
                Ver productos
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" /></svg>
            </a>
        </div>
    </section>

    <div id="productos" class="max-w-5xl mx-auto px-5 py-12 space-y-8 scroll-mt-16">
        <div class="text-center space-y-2">
            <h2 class="text-3xl font-bold text-bosque">Nuestros productos</h2>
            <p class="text-piedra">Directo de la planta de Huata a tu mesa.</p>
        </div>

        @if ($mensaje !== '')
            <div class="rounded-xl bg-salvia text-bosque px-4 py-3 text-sm text-center max-w-md mx-auto">{{ $mensaje }}</div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($productos as $producto)
                <div wire:key="producto-{{ $producto->id }}"
                     class="bg-white rounded-2xl border border-linea shadow-sm overflow-hidden flex flex-col hover:shadow-md transition-shadow">
                    <div class="h-44 bg-salvia">
                        <x-tienda.imagen-producto :producto="$producto" />
                    </div>
                    <div class="p-5 flex-1 flex flex-col gap-2">
                        <h3 class="font-semibold text-bosque">{{ $producto->nombre }}</h3>
                        @if ($producto->descripcion)
                            <p class="text-xs text-piedra line-clamp-3">{{ $producto->descripcion }}</p>
                        @endif
                        <p class="text-xl font-bold text-campo mt-auto">S/ {{ number_format($producto->precioReferencia, 2) }}
                            <span class="text-xs font-normal text-piedra">/ {{ $producto->unidad }}</span>
                        </p>

                        @if ($producto->stock > 0)
                            <div class="flex items-center gap-2 pt-1">
                                <input type="number" min="1" step="1" wire:model="cantidades.{{ $producto->id }}"
                                       aria-label="Cantidad de {{ $producto->nombre }}"
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
</div>
