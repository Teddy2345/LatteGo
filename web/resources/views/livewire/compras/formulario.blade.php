<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">Nueva compra de insumos</h1>
            <p class="text-sm text-piedra mt-1">Lo que entra por esta pantalla es lo que después puede consumir producción.</p>
        </div>
        <a href="{{ route('compras.index') }}" wire:navigate class="text-sm text-piedra hover:underline">← Volver a compras</a>
    </div>

    @if ($errors->any())
        <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm space-y-1">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form wire:submit="guardar" class="bg-white rounded-2xl border border-linea shadow-sm p-6 space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Fecha</label>
                <input type="date" wire:model="fecha" max="{{ now()->format('Y-m-d') }}"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
            </div>
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">A quién se le compró</label>
                <input type="text" wire:model="proveedor" placeholder="Distribuidora San Martín"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
            </div>
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Boleta / factura <span class="text-piedra font-normal">(opcional)</span></label>
                <input type="text" wire:model="documento" placeholder="B001-1234"
                       class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
            </div>
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-piedra uppercase tracking-wide">Insumos comprados</h2>
                <button type="button" wire:click="agregarItem"
                        class="text-sm text-campo hover:underline">+ Agregar insumo</button>
            </div>

            @foreach ($items as $indice => $item)
                <div class="grid grid-cols-12 gap-3 items-end border border-linea rounded-xl p-3" wire:key="compra-item-{{ $indice }}">
                    <div class="col-span-12 sm:col-span-5">
                        <label class="block text-xs font-medium text-piedra mb-1">Insumo</label>
                        <select wire:model="items.{{ $indice }}.productoId"
                                class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                            <option value="">Elige el insumo…</option>
                            @foreach ($insumos as $insumo)
                                <option value="{{ $insumo->id }}">{{ $insumo->nombre }} ({{ $insumo->unidad }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-5 sm:col-span-3">
                        <label class="block text-xs font-medium text-piedra mb-1">Cantidad</label>
                        <input type="number" step="0.01" min="0.01" wire:model.live="items.{{ $indice }}.cantidad"
                               class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                    </div>
                    <div class="col-span-5 sm:col-span-3">
                        <label class="block text-xs font-medium text-piedra mb-1">Costo por unidad (S/)</label>
                        <input type="number" step="0.01" min="0" wire:model.live="items.{{ $indice }}.costoUnitario"
                               class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                    </div>
                    <div class="col-span-2 sm:col-span-1 text-right">
                        <button type="button" wire:click="quitarItem({{ $indice }})"
                                class="text-piedra hover:text-red-600 text-sm px-2 py-2" title="Quitar">✕</button>
                    </div>
                </div>
            @endforeach
        </div>

        <div>
            <label class="block text-sm font-medium text-bosque mb-1">Observaciones <span class="text-piedra font-normal">(opcional)</span></label>
            <textarea wire:model="observaciones" rows="2"
                      class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo"></textarea>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4 border-t border-linea pt-4">
            <p class="text-sm text-piedra">
                Total de la compra:
                <span class="text-lg font-semibold text-bosque">S/ {{ number_format($this->total(), 2) }}</span>
            </p>
            <div class="flex gap-3">
                <a href="{{ route('compras.index') }}" wire:navigate
                   class="px-4 py-2 rounded-lg border border-linea text-sm text-piedra hover:bg-crema">Cancelar</a>
                <button type="submit" wire:loading.attr="disabled"
                        class="bg-campo text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-bosque disabled:opacity-60">
                    <span wire:loading.remove wire:target="guardar">Registrar compra</span>
                    <span wire:loading wire:target="guardar">Guardando…</span>
                </button>
            </div>
        </div>
    </form>
</div>
