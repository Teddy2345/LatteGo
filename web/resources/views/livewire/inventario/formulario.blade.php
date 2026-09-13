<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">Registrar transformacion</h1>
            <p class="text-sm text-piedra mt-1">Leche que entra a proceso y producto terminado que ingresa al almacen.</p>
        </div>
        <a href="{{ route('inventario.index') }}" wire:navigate class="text-sm text-piedra hover:underline">
            &larr; Volver al almacen
        </a>
    </div>

    @if ($error !== '')
        <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $error }}</div>
    @endif

    <form wire:submit="guardar" class="bg-white rounded-2xl border border-linea shadow-sm p-6 space-y-5 max-w-xl">
        <div>
            <label class="block text-sm font-medium text-bosque mb-1">Producto</label>
            <select wire:model="productoId" class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                <option value="">Seleccione...</option>
                @foreach ($productos as $producto)
                    <option value="{{ $producto->id }}">
                        {{ $producto->nombre }} — {{ number_format($producto->stock, 2) }} {{ $producto->unidad }} en stock
                    </option>
                @endforeach
            </select>
            @error('productoId') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-bosque mb-1">Fecha</label>
            <input type="date" wire:model="fecha" class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
            @error('fecha') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Cantidad obtenida</label>
                <input type="number" step="0.01" min="0.01" wire:model="cantidad"
                       class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('cantidad') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Litros de leche procesados</label>
                <input type="number" step="0.01" min="0.01" wire:model="litrosProcesados"
                       class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('litrosProcesados') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-bosque mb-1">Observaciones</label>
            <textarea wire:model="observaciones" rows="3"
                      class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo"></textarea>
            @error('observaciones') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-linea">
            <a href="{{ route('inventario.index') }}" wire:navigate
               class="px-4 py-2 rounded-xl text-sm font-medium border border-linea text-bosque hover:bg-crema">Cancelar</a>
            <button type="submit" class="bg-campo text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-bosque transition-colors">
                Guardar e ingresar a stock
            </button>
        </div>
    </form>
</div>
