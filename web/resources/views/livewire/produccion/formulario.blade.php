<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">Nueva producción</h1>
            <p class="text-sm text-piedra mt-1">Registro diario de quesos elaborados.</p>
        </div>
        <a href="{{ route('produccion.index') }}" wire:navigate class="text-sm text-piedra hover:underline">
            ← Volver al listado
        </a>
    </div>

    @if (count($insumosDisponibles) > 0)
        <div>
            <h2 class="text-sm font-semibold text-piedra uppercase tracking-wide mb-2">Insumos disponibles</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach ($insumosDisponibles as $insumo)
                    <div class="bg-white rounded-xl border {{ $insumo->stockBajo ? 'border-amber-300 bg-amber-50' : 'border-linea' }} px-3 py-2">
                        <p class="text-xs text-piedra truncate">{{ $insumo->nombre }}</p>
                        <p class="text-sm font-semibold {{ $insumo->stockBajo ? 'text-amber-700' : 'text-bosque' }}">
                            {{ number_format($insumo->stock, 1) }} {{ $insumo->unidad }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($error !== '')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            {{ $error }}
        </div>
    @endif

    <form wire:submit="guardar" class="bg-white rounded-2xl border border-linea shadow-sm p-6 space-y-5 max-w-xl">
        <div>
            <label class="block text-sm font-medium text-bosque mb-1">Fecha</label>
            <input type="date" wire:model="fecha"
                   class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
            @error('fecha') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Litros procesados</label>
                <input type="number" step="0.01" min="0.01" wire:model="litrosProcesados"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('litrosProcesados') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Quesos producidos</label>
                <input type="number" min="0" wire:model="quesosProducidos"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('quesosProducidos') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="text-xs text-piedra">El rendimiento esperado es de 11-12 quesos por cada 100 litros; se calcula automáticamente.</p>

        <div>
            <label class="block text-sm font-medium text-bosque mb-1">Observaciones</label>
            <textarea wire:model="observaciones" rows="3"
                      class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo"></textarea>
            @error('observaciones') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="border-t border-linea pt-4">
            <label class="block text-sm font-medium text-bosque mb-1">¿A qué producto del almacén corresponde? (opcional)</label>
            <select wire:model="productoId" class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                <option value="">No conectar con Almacén</option>
                @foreach ($productosTerminados as $producto)
                    <option value="{{ $producto->id }}">{{ $producto->nombre }} — {{ number_format($producto->stock, 2) }} {{ $producto->unidad }} en stock</option>
                @endforeach
            </select>
            <p class="text-xs text-piedra mt-1">Si lo eliges, los quesos producidos se suman al stock de ese producto.</p>
            @error('productoId') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-bosque">Insumos utilizados (opcional)</label>
                <button type="button" wire:click="agregarInsumo" class="text-sm text-campo hover:underline">+ Agregar insumo</button>
            </div>

            @if (count($insumos) > 0)
                <div class="space-y-2">
                    @foreach ($insumos as $i => $insumo)
                        <div class="flex items-center gap-2" wire:key="insumo-row-{{ $i }}">
                            <select wire:model="insumos.{{ $i }}.productoId"
                                    class="flex-1 rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                                <option value="">Seleccione insumo...</option>
                                @foreach ($insumosDisponibles as $disponible)
                                    <option value="{{ $disponible->id }}">{{ $disponible->nombre }} ({{ number_format($disponible->stock, 2) }} {{ $disponible->unidad }})</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.01" min="0.01" placeholder="Cantidad" wire:model="insumos.{{ $i }}.cantidad"
                                   class="w-28 rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                            <button type="button" wire:click="quitarInsumo({{ $i }})" class="text-rose-600 text-sm px-2">Quitar</button>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-piedra">Ningún insumo agregado todavía.</p>
            @endif
            <p class="text-xs text-piedra mt-1">Cada insumo se descuenta del almacén al guardar.</p>
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-linea">
            <a href="{{ route('produccion.index') }}" wire:navigate
               class="px-4 py-2 rounded-lg text-sm font-medium border border-linea text-bosque hover:bg-crema">Cancelar</a>
            <button type="submit"
                    class="bg-campo text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-bosque">
                Guardar
            </button>
        </div>
    </form>
</div>
