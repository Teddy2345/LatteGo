<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">Ventas</h1>
            <p class="text-sm text-piedra mt-1">Salidas de producto terminado hacia clientes.</p>
        </div>
        @can('vender', App\Infrastructure\Inventario\Models\MovimientoInventarioModel::class)
            <a href="{{ route('ventas.crear') }}" wire:navigate
               class="inline-flex items-center gap-1.5 bg-campo text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-bosque transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Registrar venta
            </a>
        @endcan
    </div>

    @php($emitido = $recienEmitido ?? session('comprobanteEmitido'))
    @if ($emitido)
        <div class="rounded-xl bg-salvia border border-linea text-bosque px-4 py-3 text-sm flex flex-wrap items-center gap-3">
            <span>Comprobante emitido.</span>
            <a href="{{ route('comprobantes.pdf', $emitido) }}"
               class="font-medium underline">Descargar el PDF</a>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-linea shadow-sm p-5 flex items-start gap-4 max-w-sm">
        <div class="h-11 w-11 rounded-lg bg-salvia text-campo flex items-center justify-center shrink-0">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-9.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
        </div>
        <div>
            <p class="text-sm text-piedra">Total comercializado</p>
            <p class="text-2xl font-semibold text-bosque mt-0.5">S/ {{ number_format($recaudado, 2) }}</p>
            <p class="text-xs text-piedra mt-1">{{ count($ventas) }} ventas registradas</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-linea shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-linea text-sm">
                <thead class="bg-crema">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Fecha</th>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Producto</th>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Cliente</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Cantidad</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Precio</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Total</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Comprobante</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($ventas as $venta)
                        <tr wire:key="venta-{{ $venta->id }}" class="hover:bg-crema">
                            <td class="px-4 py-3 text-piedra">{{ $venta->fecha }}</td>
                            <td class="px-4 py-3 font-medium text-bosque">{{ $venta->producto }}</td>
                            <td class="px-4 py-3 text-piedra">
                                {{ $venta->cliente ?? '—' }}
                                @php($tipo = \App\Domain\Inventario\ValueObjects\TipoCliente::desde($venta->tipoCliente))
                                <span @class([
                                    'block mt-0.5 text-xs',
                                    'text-campo font-medium' => $tipo !== \App\Domain\Inventario\ValueObjects\TipoCliente::Externo,
                                ])>{{ $tipo->etiquetaCorta() }}</span>
                            </td>
                            <td class="px-4 py-3 text-right text-bosque">{{ number_format($venta->cantidad, 2) }} {{ $venta->unidad }}</td>
                            <td class="px-4 py-3 text-right text-piedra">{{ number_format($venta->precioUnitario ?? 0, 2) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-bosque">{{ number_format($venta->total ?? 0, 2) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if (isset($comprobantes[$venta->id]))
                                    <a href="{{ route('comprobantes.pdf', $comprobantes[$venta->id]['id']) }}"
                                       class="text-campo font-medium hover:underline">
                                        {{ $comprobantes[$venta->id]['numero_completo'] }} · PDF
                                    </a>
                                @else
                                    @can('vender', App\Infrastructure\Inventario\Models\MovimientoInventarioModel::class)
                                        <button type="button" wire:click="emitirComprobante({{ $venta->id }})"
                                                class="text-campo hover:underline">Emitir</button>
                                    @else
                                        <span class="text-piedra">—</span>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-piedra">Todavia no hay ventas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($ventaId !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="cerrarComprobante"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="font-semibold text-bosque">Emitir comprobante</h2>
                        <p class="text-xs text-piedra mt-1">El importe sale de la venta; aquí solo van los datos del cliente.</p>
                    </div>
                    <button type="button" wire:click="cerrarComprobante" class="text-piedra hover:text-bosque text-sm shrink-0">Cerrar</button>
                </div>

                @if ($errorComprobante !== '')
                    <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-3 py-2 text-sm">{{ $errorComprobante }}</div>
                @endif

                <div class="flex flex-wrap gap-2">
                    @foreach (['boleta' => 'Boleta', 'factura' => 'Factura'] as $valor => $etiqueta)
                        <label class="flex items-center gap-2 border rounded-xl px-3 py-2 text-sm cursor-pointer
                                      {{ $tipoComprobante === $valor ? 'border-campo bg-salvia' : 'border-linea hover:bg-crema' }}">
                            <input type="radio" wire:model.live="tipoComprobante" value="{{ $valor }}" class="text-campo focus:ring-campo">
                            {{ $etiqueta }}
                        </label>
                    @endforeach
                </div>

                <div>
                    <label class="block text-sm font-medium text-bosque mb-1">Cliente</label>
                    <input type="text" wire:model="clienteNombre" maxlength="160"
                           class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                </div>

                <div>
                    <label class="block text-sm font-medium text-bosque mb-1">
                        {{ $tipoComprobante === 'factura' ? 'RUC' : 'DNI (opcional)' }}
                    </label>
                    <input type="text" wire:model="clienteDocumento" maxlength="20"
                           class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" wire:click="cerrarComprobante"
                            class="px-4 py-2 rounded-lg border border-linea text-sm text-piedra hover:bg-crema">Cancelar</button>
                    <button type="button" wire:click="confirmarComprobante"
                            class="bg-campo text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-bosque">Emitir</button>
                </div>
            </div>
        </div>
    @endif

</div>
