<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">Nuevo acopio</h1>
            <p class="text-sm text-piedra mt-1">Registra la leche recolectada de un proveedor.</p>
        </div>
        <a href="{{ route('acopios.index') }}" wire:navigate class="text-sm text-piedra hover:underline">
            ← Volver al listado
        </a>
    </div>

    @if ($error !== '')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            {{ $error }}
        </div>
    @endif

    <form wire:submit="guardar" class="bg-white rounded-2xl border border-linea shadow-sm p-6 space-y-5 max-w-2xl">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Proveedor</label>
                <select wire:model="proveedorId"
                        class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                    <option value="">Seleccione...</option>
                    @foreach ($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }} ({{ $proveedor->cedula }})</option>
                    @endforeach
                </select>
                @error('proveedorId') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Fecha</label>
                <input type="date" wire:model="fecha"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('fecha') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Cantidad (litros)</label>
                <input type="number" step="0.01" min="0.01" wire:model="cantidadLitros"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('cantidadLitros') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Perdida (litros, opcional)</label>
                <input type="number" step="0.01" min="0" wire:model="perdidaLitros"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('perdidaLitros') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-bosque mb-1">Motivo de la perdida (si aplica)</label>
                <input type="text" wire:model="motivoPerdida"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('motivoPerdida') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-bosque mb-1">Observaciones</label>
                <textarea wire:model="observaciones" rows="3"
                          class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo"></textarea>
                @error('observaciones') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-linea">
            <a href="{{ route('acopios.index') }}" wire:navigate
               class="px-4 py-2 rounded-lg text-sm font-medium border border-linea text-bosque hover:bg-crema">Cancelar</a>
            <button type="submit"
                    class="bg-campo text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-bosque">
                Guardar
            </button>
        </div>
    </form>
</div>
