<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">{{ $proveedorId === null ? 'Nuevo proveedor' : 'Editar proveedor' }}</h1>
            <p class="text-sm text-piedra mt-1">Datos del productor y su ruta de acopio.</p>
        </div>
        <a href="{{ route('proveedores.index') }}" wire:navigate class="text-sm text-piedra hover:underline">
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
                <label class="block text-sm font-medium text-bosque mb-1">Nombre</label>
                <input type="text" wire:model="nombre"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('nombre') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Cedula</label>
                <input type="text" wire:model="cedula" @disabled($proveedorId !== null)
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo disabled:bg-crema disabled:text-piedra">
                @error('cedula') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                @if ($proveedorId !== null)
                    <p class="text-xs text-piedra mt-1">La cedula no se puede modificar tras el registro.</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Telefono</label>
                <input type="text" wire:model="telefono"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('telefono') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Finca</label>
                <input type="text" wire:model="finca"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('finca') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Litros promedio/dia</label>
                <input type="number" min="0" wire:model="litrosProm"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('litrosProm') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Precio por litro</label>
                <input type="number" step="0.01" min="0.01" wire:model="precioLitro"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('precioLitro') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Ruta de acopio</label>
                <select wire:model="rutaId"
                        class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                    <option value="">Sin asignar</option>
                    @foreach ($rutas as $ruta)
                        <option value="{{ $ruta->id }}">{{ $ruta->nombre }}</option>
                    @endforeach
                </select>
                @error('rutaId') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-linea">
            <a href="{{ route('proveedores.index') }}" wire:navigate
               class="px-4 py-2 rounded-lg text-sm font-medium border border-linea text-bosque hover:bg-crema">Cancelar</a>
            <button type="submit"
                    class="bg-campo text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-bosque">
                Guardar
            </button>
        </div>
    </form>
</div>
