{{-- Vincular la campana de la app: el telefono genera el codigo y aqui se escribe. --}}
<div class="border-t border-linea pt-4">
    <p class="text-sm font-medium">Recibir el código en tu celular</p>
    <p class="text-xs text-piedra mt-1 mb-3">
        En la app Ecolactea toca la <strong>campana</strong> y pulsa <strong>Generar código de vinculación</strong>.
        Escribe aquí el código que aparece (vence en 10 minutos). Desde entonces el código de 6 dígitos
        te llegará a esa campana, y <strong>solo el teléfono que vincules</strong> podrá verlo.
    </p>

    @forelse ($telefonosVinculados as $telefono)
        <div class="flex items-center justify-between gap-3 bg-crema rounded-md px-3 py-2 mb-2 text-sm">
            <div>
                <span class="text-bosque font-medium">{{ $telefono->nombre }}</span>
                <span class="text-piedra text-xs block">
                    Vinculado el {{ $telefono->vinculado_en?->format('Y-m-d H:i') }}
                </span>
            </div>
            <button type="button" wire:click="desvincularTelefono({{ $telefono->id }})"
                    wire:confirm="Este teléfono dejará de recibir códigos. ¿Continuar?"
                    class="text-xs text-red-700 hover:underline shrink-0">Desvincular</button>
        </div>
    @empty
        <p class="text-xs text-piedra mb-3">Todavía no vinculaste ningún teléfono.</p>
    @endforelse

    @if ($errorVinculacion !== '')
        <div class="rounded-md bg-red-50 border border-red-200 text-red-700 px-3 py-2 text-sm mb-3">
            {{ $errorVinculacion }}
        </div>
    @endif

    <form wire:submit="vincularTelefono" class="flex flex-wrap items-end gap-2">
        <div>
            <label for="codigo-telefono" class="block text-xs font-medium text-bosque mb-1">Código que muestra tu celular</label>
            <input id="codigo-telefono" type="text" wire:model="codigoTelefono" placeholder="ABC-123" autocomplete="off"
                   maxlength="12" class="w-40 rounded-md border-linea shadow-sm font-mono uppercase tracking-widest focus:border-campo focus:ring-campo">
        </div>
        <button type="submit"
                class="border border-campo text-campo px-4 py-2 rounded-md text-sm hover:bg-salvia">
            Vincular teléfono
        </button>
    </form>
</div>
