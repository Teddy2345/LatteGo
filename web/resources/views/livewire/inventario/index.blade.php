<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">Almacén</h1>
            <p class="text-sm text-piedra mt-1">Existencias de producto terminado e insumos.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('gestionarCatalogo', App\Infrastructure\Inventario\Models\ProductoModel::class)
                <a href="{{ route('inventario.catalogo.crear') }}" wire:navigate
                   class="inline-flex items-center gap-1.5 bg-white border border-linea text-bosque px-4 py-2 rounded-xl text-sm font-medium hover:bg-crema transition-colors">
                    + Nuevo producto/insumo
                </a>
            @endcan
            @can('registrarEntrada', App\Infrastructure\Inventario\Models\MovimientoInventarioModel::class)
                <a href="{{ route('inventario.entradas.crear') }}" wire:navigate
                   class="inline-flex items-center gap-1.5 bg-white border border-linea text-bosque px-4 py-2 rounded-xl text-sm font-medium hover:bg-crema transition-colors">
                    + Registrar entrada
                </a>
            @endcan
            @can('registrarSalida', App\Infrastructure\Inventario\Models\MovimientoInventarioModel::class)
                <a href="{{ route('inventario.salidas.crear') }}" wire:navigate
                   class="inline-flex items-center gap-1.5 bg-white border border-linea text-bosque px-4 py-2 rounded-xl text-sm font-medium hover:bg-crema transition-colors">
                    + Registrar salida
                </a>
            @endcan
            @can('transformar', App\Infrastructure\Inventario\Models\MovimientoInventarioModel::class)
                <a href="{{ route('inventario.crear') }}" wire:navigate
                   class="inline-flex items-center gap-1.5 bg-campo text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-bosque transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Registrar transformación
                </a>
            @endcan
        </div>
    </div>

    @if ($insumosStockBajo > 0)
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm flex items-center gap-2">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
            {{ $insumosStockBajo }} {{ $insumosStockBajo === 1 ? 'insumo está' : 'insumos están' }} por agotarse.
        </div>
    @endif

    <div>
        <h2 class="text-sm font-semibold text-piedra uppercase tracking-wide mb-3">Producto terminado</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($productosTerminados as $producto)
                <x-inventario.tarjeta-stock :producto="$producto" />
            @empty
                <div class="sm:col-span-2 lg:col-span-3 bg-white rounded-2xl border border-linea shadow-sm p-8 text-center text-piedra text-sm">
                    No hay productos terminados en el catálogo todavía.
                </div>
            @endforelse
        </div>
    </div>

    <div>
        <h2 class="text-sm font-semibold text-piedra uppercase tracking-wide mb-3">Insumos</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($insumos as $producto)
                <x-inventario.tarjeta-stock :producto="$producto" />
            @empty
                <div class="sm:col-span-2 lg:col-span-3 bg-white rounded-2xl border border-linea shadow-sm p-8 text-center text-piedra text-sm">
                    No hay insumos en el catálogo todavía.
                </div>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-linea shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-linea flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-bosque">Últimas transformaciones</h2>
            <p class="text-xs text-piedra">{{ number_format($litrosProcesados, 2) }} litros de leche procesados en total</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-linea text-sm">
                <thead class="bg-crema">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Fecha</th>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Producto</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Obtenido</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Leche usada</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Litros/unidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($transformaciones as $movimiento)
                        <tr wire:key="mov-{{ $movimiento->id }}" class="hover:bg-crema">
                            <td class="px-4 py-3 text-piedra">{{ $movimiento->fecha }}</td>
                            <td class="px-4 py-3 font-medium text-bosque">{{ $movimiento->producto }}</td>
                            <td class="px-4 py-3 text-right text-bosque">{{ number_format($movimiento->cantidad, 2) }} {{ $movimiento->unidad }}</td>
                            <td class="px-4 py-3 text-right text-piedra">{{ number_format($movimiento->litrosProcesados ?? 0, 2) }} L</td>
                            <td class="px-4 py-3 text-right text-piedra">{{ number_format($movimiento->litrosPorUnidad ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-piedra">Todavia no hay transformaciones registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-linea shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-linea">
            <h2 class="font-semibold text-bosque">Últimos movimientos de insumos</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-linea text-sm">
                <thead class="bg-crema">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Fecha</th>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Insumo</th>
                        <th class="px-4 py-3 text-center font-medium text-piedra text-xs uppercase tracking-wide">Movimiento</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Cantidad</th>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Lote</th>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Motivo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($movimientosInsumo as $movimiento)
                        <tr wire:key="mov-insumo-{{ $movimiento->id }}" class="hover:bg-crema">
                            <td class="px-4 py-3 text-piedra">{{ $movimiento->fecha }}</td>
                            <td class="px-4 py-3 font-medium text-bosque">{{ $movimiento->producto }}</td>
                            <td class="px-4 py-3 text-center">
                                @if ($movimiento->tipo === 'compra')
                                    <span class="inline-block bg-green-100 text-green-800 px-2 py-0.5 rounded-full text-xs font-medium">Entrada</span>
                                @else
                                    <span class="inline-block bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full text-xs font-medium">Salida</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-bosque">{{ number_format($movimiento->cantidad, 2) }} {{ $movimiento->unidad }}</td>
                            <td class="px-4 py-3 text-piedra">{{ $movimiento->lote ?? '—' }}</td>
                            <td class="px-4 py-3 text-piedra">{{ $movimiento->observaciones ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-piedra">Todavía no hay movimientos de insumos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
