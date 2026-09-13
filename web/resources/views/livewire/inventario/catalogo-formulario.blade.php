<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">{{ $productoId === null ? 'Nuevo producto o insumo' : 'Editar '.$nombre }}</h1>
            <p class="text-sm text-piedra mt-1">{{ $productoId === null ? 'Da de alta un item del catálogo del almacén.' : 'Actualiza los datos del catálogo del almacén.' }}</p>
        </div>
        <a href="{{ route('inventario.index') }}" wire:navigate class="text-sm text-piedra hover:underline">
            &larr; Volver al almacén
        </a>
    </div>

    <form wire:submit="guardar" class="bg-white rounded-2xl border border-linea shadow-sm p-6 space-y-5 max-w-xl">
        <div>
            <label class="block text-sm font-medium text-bosque mb-1">Tipo</label>
            <select wire:model.live="tipo" @disabled($productoId !== null) class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo disabled:bg-crema disabled:text-piedra">
                @foreach ($tipos as $t)
                    <option value="{{ $t->value }}">{{ $t === \App\Domain\Inventario\ValueObjects\TipoProducto::Insumo ? 'Insumo' : 'Producto terminado' }}</option>
                @endforeach
            </select>
            @if ($productoId !== null)
                <p class="text-xs text-piedra mt-1">El tipo no se puede cambiar después de creado.</p>
            @endif
        </div>

        <div>
            <label class="block text-sm font-medium text-bosque mb-1">Nombre</label>
            <input type="text" wire:model="nombre" class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
            @error('nombre') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-bosque mb-1">Categoría (opcional)</label>
            <input type="text" wire:model="categoria" placeholder="Ej. Lácteos, Envases, Aditivos"
                   class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
            @error('categoria') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-3 gap-5">
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Unidad</label>
                <input type="text" wire:model="unidad" placeholder="kilo, pieza, litro..."
                       class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('unidad') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Precio referencia</label>
                <input type="number" step="0.01" min="0" wire:model="precioReferencia"
                       class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('precioReferencia') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Stock mínimo (opcional)</label>
                <input type="number" step="0.01" min="0" wire:model="stockMinimo"
                       class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('stockMinimo') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="text-xs text-piedra">El stock mínimo activa la alerta de "por agotarse" en el almacén. Déjalo vacío si no quieres vigilarlo.</p>

        @if ($tipo === 'producto_terminado')
            <div class="border-t border-linea pt-5 space-y-5">
                <p class="text-xs text-piedra">Foto y descripción se muestran en la tienda web al público.</p>

                <div>
                    <label class="block text-sm font-medium text-bosque mb-1">Descripción (opcional)</label>
                    <textarea wire:model="descripcion" rows="3" maxlength="500"
                              class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo"></textarea>
                    @error('descripcion') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-bosque mb-1">Foto (opcional)</label>
                    @if ($foto)
                        <img src="{{ $foto->temporaryUrl() }}" class="h-28 w-28 object-cover rounded-xl border border-linea mb-2">
                    @elseif ($fotoPath)
                        <img src="{{ Illuminate\Support\Facades\Storage::url($fotoPath) }}" class="h-28 w-28 object-cover rounded-xl border border-linea mb-2">
                    @endif
                    <input type="file" wire:model="foto" accept="image/*"
                           class="w-full text-sm text-piedra file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-salvia file:text-bosque">
                    <p class="text-xs text-piedra mt-1" wire:loading wire:target="foto">Subiendo…</p>
                    @error('foto') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif

        <div class="flex justify-end gap-3 pt-2 border-t border-linea">
            <a href="{{ route('inventario.index') }}" wire:navigate
               class="px-4 py-2 rounded-xl text-sm font-medium border border-linea text-bosque hover:bg-crema">Cancelar</a>
            <button type="submit" class="bg-campo text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-bosque transition-colors">
                {{ $productoId === null ? 'Guardar' : 'Guardar cambios' }}
            </button>
        </div>
    </form>
</div>
