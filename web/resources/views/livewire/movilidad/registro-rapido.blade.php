<div class="max-w-md mx-auto">
    <a href="{{ route('movilidades.detalle', $movilidadId) }}" wire:navigate class="text-sm text-piedra hover:text-bosque">&larr; Volver al recorrido</a>

    <div class="bg-white rounded-2xl border border-linea shadow-sm p-6 mt-3">
        <p class="text-piedra text-sm">Proveedor</p>
        <p class="text-2xl font-bold text-bosque mb-6">{{ $proveedorNombre }}</p>

        @if ($error !== '')
            <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-700 px-3 py-2 text-sm">{{ $error }}</div>
        @endif

        <form wire:submit="registrar" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-bosque mb-2">Litros entregados</label>
                <input type="number" step="0.01" min="0.01" wire:model="litros" autofocus
                       inputmode="decimal"
                       class="w-full rounded-2xl border-linea shadow-sm text-3xl font-bold text-center text-bosque py-4 focus:border-campo focus:ring-campo">
                @error('litros') <p class="text-red-600 text-xs mt-1 text-center">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="w-full bg-campo text-white rounded-2xl py-5 text-lg font-bold hover:bg-bosque transition-colors">
                Registrar acopio
            </button>
        </form>
    </div>
</div>
