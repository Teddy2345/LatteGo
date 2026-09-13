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
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($ventas as $venta)
                        <tr wire:key="venta-{{ $venta->id }}" class="hover:bg-crema">
                            <td class="px-4 py-3 text-piedra">{{ $venta->fecha }}</td>
                            <td class="px-4 py-3 font-medium text-bosque">{{ $venta->producto }}</td>
                            <td class="px-4 py-3 text-piedra">{{ $venta->cliente ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-bosque">{{ number_format($venta->cantidad, 2) }} {{ $venta->unidad }}</td>
                            <td class="px-4 py-3 text-right text-piedra">{{ number_format($venta->precioUnitario ?? 0, 2) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-bosque">{{ number_format($venta->total ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-piedra">Todavia no hay ventas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
