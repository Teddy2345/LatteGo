<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">Cobros por Yape y transferencia</h1>
            <p class="text-sm text-piedra mt-1">Para cotejarlos, uno a uno, contra el estado de cuenta.</p>
        </div>
        <a href="{{ route('pedidos.index') }}" wire:navigate class="text-sm text-piedra hover:underline">
            &larr; Volver a pedidos
        </a>
    </div>

    @can('revisar', App\Infrastructure\Pedidos\Models\PedidoModel::class)
        <div class="bg-white rounded-2xl border border-linea shadow-sm p-5">
            <div class="flex flex-wrap items-start justify-between gap-5">
                <div class="max-w-lg">
                    <h2 class="font-semibold text-bosque">QR de cobro de la planta</h2>
                    <p class="text-sm text-piedra mt-1">
                        Sube aquí la foto del QR de Yape de la planta. El repartidor lo ve en su celular
                        junto al monto exacto del pedido, así el cliente no tiene que escribir cuánto pagar.
                        Al reemplazarlo, todos los teléfonos toman el nuevo enseguida.
                    </p>

                    @if ($avisoQr !== '')
                        <p class="mt-3 rounded-lg bg-salvia border border-linea text-bosque px-3 py-2 text-sm">{{ $avisoQr }}</p>
                    @endif

                    @error('qrCobro') <p class="mt-3 text-red-600 text-sm">{{ $message }}</p> @enderror

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <input type="file" wire:model="qrCobro" accept="image/*"
                               class="text-sm text-piedra file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-salvia file:text-bosque file:text-sm file:font-medium hover:file:bg-linea">
                        @if ($qrCobro)
                            <button type="button" wire:click="guardarQr" wire:loading.attr="disabled" wire:target="guardarQr,qrCobro"
                                    class="bg-campo text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-bosque disabled:opacity-60">
                                <span wire:loading.remove wire:target="guardarQr">Guardar QR</span>
                                <span wire:loading wire:target="guardarQr">Guardando…</span>
                            </button>
                        @endif
                    </div>
                </div>

                <div class="text-center">
                    @if ($qrCobro)
                        <img src="{{ $qrCobro->temporaryUrl() }}" alt="QR por subir" class="h-36 w-36 object-contain rounded-xl border border-linea">
                        <p class="text-xs text-piedra mt-1">Sin guardar</p>
                    @elseif ($qrActual)
                        <img src="{{ $qrActual }}" alt="QR de cobro actual" class="h-36 w-36 object-contain rounded-xl border border-linea">
                        <p class="text-xs text-piedra mt-1">QR en uso</p>
                    @else
                        <div class="h-36 w-36 rounded-xl border border-dashed border-linea flex items-center justify-center text-xs text-piedra text-center px-3">
                            Todavía no hay QR cargado
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endcan

    <div class="bg-white rounded-2xl border border-linea shadow-sm p-5">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="cobros-desde" class="block text-sm font-medium text-bosque mb-1">Desde</label>
                <input id="cobros-desde" type="date" wire:model.live="desde"
                       class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
            </div>
            <div>
                <label for="cobros-hasta" class="block text-sm font-medium text-bosque mb-1">Hasta</label>
                <input id="cobros-hasta" type="date" wire:model.live="hasta"
                       class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
            </div>
            <div>
                <label for="cobros-metodo" class="block text-sm font-medium text-bosque mb-1">Forma de pago</label>
                <select id="cobros-metodo" wire:model.live="metodo"
                        class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                    <option value="">Todas las digitales</option>
                    @foreach ($metodos as $m)
                        <option value="{{ $m->value }}">{{ $m->etiqueta() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-linea shadow-sm p-5">
            <p class="text-xs uppercase tracking-wide text-piedra font-medium">Cobros</p>
            <p class="text-2xl font-semibold text-bosque mt-1">{{ count($cobros) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-linea shadow-sm p-5">
            <p class="text-xs uppercase tracking-wide text-piedra font-medium">Suma cobrada</p>
            <p class="text-2xl font-semibold text-bosque mt-1">S/ {{ number_format($total, 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl border shadow-sm p-5 {{ $sinReferencia > 0 ? 'border-amber-300' : 'border-linea' }}">
            <p class="text-xs uppercase tracking-wide text-piedra font-medium">Sin número de operación</p>
            <p class="text-2xl font-semibold mt-1 {{ $sinReferencia > 0 ? 'text-amber-700' : 'text-bosque' }}">{{ $sinReferencia }}</p>
            @if ($sinReferencia > 0)
                <p class="text-xs text-amber-700 mt-1">Son entregas registradas antes de pedirlo. Esas hay que cotejarlas a mano.</p>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-linea shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-linea text-sm">
                <thead class="bg-crema">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Entrega</th>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Cliente</th>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Pago</th>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">N.º de operación</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Cobrado</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Pedido</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($cobros as $cobro)
                        <tr wire:key="cobro-{{ $cobro->id }}" class="hover:bg-crema">
                            <td class="px-4 py-3 text-piedra">{{ $cobro->fecha_entrega?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-bosque font-medium">{{ $cobro->cliente_nombre }}</td>
                            <td class="px-4 py-3 text-piedra capitalize">{{ $cobro->metodo_pago }}</td>
                            <td class="px-4 py-3">
                                @if ($cobro->referencia_pago)
                                    <span class="font-mono text-bosque">{{ $cobro->referencia_pago }}</span>
                                @else
                                    <span class="text-amber-700 text-xs">Sin registrar</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-bosque">S/ {{ number_format((float) $cobro->monto_cobrado, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('pedidos.detalle', $cobro->id) }}" wire:navigate
                                   class="text-campo hover:underline font-medium">
                                    {{ $cobro->codigo ?? '#'.$cobro->id }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-piedra">
                                No hay cobros digitales en esas fechas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
