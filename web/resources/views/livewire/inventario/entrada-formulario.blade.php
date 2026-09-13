<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">Registrar entrada</h1>
            <p class="text-sm text-piedra mt-1">Compra o ingreso de un insumo al almacén.</p>
        </div>
        <a href="{{ route('inventario.index') }}" wire:navigate class="text-sm text-piedra hover:underline">
            &larr; Volver al almacén
        </a>
    </div>

    @if ($error !== '')
        <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $error }}</div>
    @endif

    <form wire:submit="guardar" class="bg-white rounded-2xl border border-linea shadow-sm p-6 space-y-5 max-w-xl">
        <div>
            <label class="block text-sm font-medium text-bosque mb-1">Insumo</label>
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
                <label class="block text-sm font-medium text-bosque mb-1">Cantidad</label>
                <input type="number" step="0.01" min="0.01" wire:model="cantidad"
                       class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('cantidad') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Lote (opcional)</label>
                <input type="text" wire:model="lote" class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('lote') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-bosque mb-1">Costo unitario pagado (opcional)</label>
            <input type="number" step="0.01" min="0" wire:model="costoUnitario" placeholder="S/ por unidad"
                   class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
            <p class="text-xs text-piedra mt-1">Se usa para calcular el costo de producción. Si no lo registras, ese insumo quedará fuera del costeo.</p>
            @error('costoUnitario') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-bosque mb-1">Motivo / observación (opcional)</label>
            <input type="text" wire:model="motivo" placeholder="Ej. Compra a Distribuidora Illimani"
                   class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-linea">
            <a href="{{ route('inventario.index') }}" wire:navigate
               class="px-4 py-2 rounded-xl text-sm font-medium border border-linea text-bosque hover:bg-crema">Cancelar</a>
            <button type="submit" class="bg-campo text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-bosque transition-colors">
                Registrar entrada
            </button>
        </div>
    </form>
</div>
