<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">Nuevo analisis de calidad</h1>
            <p class="text-sm text-piedra mt-1">Resultados del LactoScan para un acopio.</p>
        </div>
        <a href="{{ route('calidad.index') }}" wire:navigate class="text-sm text-piedra hover:underline">
            ← Volver al listado
        </a>
    </div>

    @if ($error !== '')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            {{ $error }}
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-linea shadow-sm p-6 max-w-3xl">
        <label class="block text-sm font-medium text-bosque mb-1">Foto del equipo (opcional)</label>
        <p class="text-xs text-piedra mb-2">Toma o sube una foto del resultado del LactoScan. El sistema intenta reconocer los valores automáticamente; siempre revisa antes de guardar.</p>

        <div class="flex flex-wrap items-center gap-3">
            <input type="file" wire:model="fotoEquipo" accept="image/*"
                   class="text-sm text-piedra file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-salvia file:text-bosque file:text-sm file:font-medium hover:file:bg-linea">

            @if ($fotoEquipo)
                <button type="button" wire:click="analizarFoto" wire:loading.attr="disabled" wire:target="analizarFoto,fotoEquipo"
                        class="px-4 py-2 rounded-lg text-sm font-medium bg-campo text-white hover:bg-bosque disabled:opacity-60">
                    <span wire:loading.remove wire:target="analizarFoto">Analizar foto</span>
                    <span wire:loading wire:target="analizarFoto">Analizando…</span>
                </button>
            @endif
        </div>
        @error('fotoEquipo') <p class="text-red-600 text-xs mt-2">{{ $message }}</p> @enderror

        @if ($fotoEquipo)
            <img src="{{ $fotoEquipo->temporaryUrl() }}" alt="Vista previa" class="mt-3 h-32 rounded-lg border border-linea object-cover">
        @endif

        @if ($mensajeOcr !== '')
            <p class="mt-3 text-sm bg-crema text-piedra rounded-lg px-3 py-2">{{ $mensajeOcr }}</p>
        @endif
    </div>

    <form wire:submit="guardar" class="bg-white rounded-2xl border border-linea shadow-sm p-6 space-y-5 max-w-3xl">
        <div>
            <label class="block text-sm font-medium text-bosque mb-1">Acopio</label>
            <select wire:model="acopioId"
                    class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                <option value="">Seleccione...</option>
                @foreach ($acopios as $acopio)
                    <option value="{{ $acopio->id }}">
                        #{{ $acopio->id }} — {{ $acopio->fecha }} — {{ number_format($acopio->cantidadLitros, 2) }} L
                    </option>
                @endforeach
            </select>
            @error('acopioId') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-5">
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Temperatura (°C)</label>
                <input type="number" step="0.01" wire:model="temperatura"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('temperatura') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Grasa (%)</label>
                <input type="number" step="0.01" wire:model="grasa"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('grasa') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Solidos no grasos</label>
                <input type="number" step="0.01" wire:model="solidosNoGrasos"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('solidosNoGrasos') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Densidad</label>
                <input type="number" step="0.01" wire:model="densidad"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('densidad') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Proteina (%)</label>
                <input type="number" step="0.01" wire:model="proteina"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('proteina') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Lactosa (%)</label>
                <input type="number" step="0.01" wire:model="lactosa"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('lactosa') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Sales (%)</label>
                <input type="number" step="0.01" wire:model="sales"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('sales') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">pH</label>
                <input type="number" step="0.01" wire:model="ph"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('ph') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Agua añadida (%)</label>
                <input type="number" step="0.01" wire:model="aguaAgregada"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                @error('aguaAgregada') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                <p class="text-xs text-piedra mt-1">Mayor a 0 rechaza automaticamente por adulteracion.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Prueba de alcohol</label>
                <select wire:model="pruebaAlcoholAceptada"
                        class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                    <option value="1">Aceptada</option>
                    <option value="0">Rechazada</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-bosque mb-1">Rechazar por otro motivo (opcional)</label>
                <select wire:model="motivoRechazoManual"
                        class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                    <option value="">No aplica</option>
                    <option value="acidez">Acidez</option>
                    <option value="otro">Otro</option>
                </select>
                <p class="text-xs text-piedra mt-1">Solo se usa si no hubo agua añadida ni fallo de alcohol.</p>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-linea">
            <a href="{{ route('calidad.index') }}" wire:navigate
               class="px-4 py-2 rounded-lg text-sm font-medium border border-linea text-bosque hover:bg-crema">Cancelar</a>
            <button type="submit"
                    class="bg-campo text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-bosque">
                Guardar
            </button>
        </div>
    </form>
</div>
