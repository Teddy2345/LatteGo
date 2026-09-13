<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">Registrar despacho</h1>
            <p class="text-sm text-piedra mt-1">Confirma la cantidad recibida y calcula la merma.</p>
        </div>
        <a href="{{ route('produccion.index') }}" wire:navigate class="text-sm text-piedra hover:underline">
            ← Volver al listado
        </a>
    </div>

    @if ($error !== '')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            {{ $error }}
        </div>
    @endif

    <form wire:submit="guardar" class="bg-white rounded-2xl border border-linea shadow-sm p-6 space-y-5 max-w-xl">
        <div>
            <label class="block text-sm font-medium text-bosque mb-1">Producción</label>
            <select wire:model="produccionId"
                    class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                <option value="">Seleccione...</option>
                @foreach ($producciones as $produccion)
                    <option value="{{ $produccion->id }}">
                        {{ $produccion->fecha }} — {{ $produccion->quesosProducidos }} quesos producidos
                    </option>
                @endforeach
            </select>
            @error('produccionId') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Quesos recibidos</label>
                <input type="number" min="0" wire:model="quesosRecibidos"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('quesosRecibidos') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Quesos despachados</label>
                <input type="number" min="0" wire:model="quesosDespachados"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('quesosDespachados') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="text-xs text-piedra">Si los quesos recibidos difieren de los producidos, la diferencia se registra automáticamente como merma.</p>

        <div>
            <label class="block text-sm font-medium text-bosque mb-1">Observaciones</label>
            <textarea wire:model="observaciones" rows="3"
                      class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo"></textarea>
            @error('observaciones') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
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
