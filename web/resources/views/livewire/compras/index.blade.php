<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">Compras de insumos</h1>
            <p class="text-sm text-piedra mt-1">Sal, cuajo, envases, etiquetas: lo que se compra para poder producir.</p>
        </div>
        @can('registrarEntrada', App\Infrastructure\Inventario\Models\MovimientoInventarioModel::class)
            <a href="{{ route('compras.crear') }}" wire:navigate
               class="inline-flex items-center gap-1.5 bg-campo text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-bosque transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Nueva compra
            </a>
        @endcan
    </div>

    @if (session('compraGuardada'))
        <div class="rounded-xl bg-salvia border border-linea text-bosque px-4 py-3 text-sm">
            {{ session('compraGuardada') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-linea shadow-sm p-5">
        <p class="text-xs text-piedra uppercase tracking-wide">Gastado en insumos</p>
        <p class="text-2xl font-semibold text-bosque mt-1">S/ {{ number_format($gastado, 2) }}</p>
        <p class="text-xs text-piedra mt-1">Suma de las {{ count($compras) }} últimas compras registradas.</p>
    </div>

    <div class="space-y-3">
        @forelse ($compras as $compra)
            <div class="bg-white rounded-2xl border border-linea shadow-sm p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-medium text-bosque">{{ $compra['proveedor'] }}</p>
                        <p class="text-xs text-piedra mt-0.5">
                            {{ $compra['fecha'] }}
                            @if ($compra['documento']) · {{ $compra['documento'] }} @endif
                            @if ($compra['usuario']) · registró {{ $compra['usuario'] }} @endif
                        </p>
                    </div>
                    <p class="text-lg font-semibold text-bosque">S/ {{ number_format((float) $compra['total'], 2) }}</p>
                </div>

                <ul class="mt-3 space-y-1 text-sm text-piedra">
                    @foreach ($compra['items'] as $item)
                        <li class="flex justify-between gap-4">
                            <span>{{ $item['insumo'] }}</span>
                            <span class="shrink-0">
                                {{ number_format((float) $item['cantidad'], 2) }} {{ $item['unidad'] }}
                                × S/ {{ number_format((float) $item['costo_unitario'], 2) }}
                                = S/ {{ number_format((float) $item['cantidad'] * (float) $item['costo_unitario'], 2) }}
                            </span>
                        </li>
                    @endforeach
                </ul>

                @if ($compra['observaciones'])
                    <p class="text-xs text-piedra mt-3 border-t border-linea pt-2">{{ $compra['observaciones'] }}</p>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-linea shadow-sm p-8 text-center text-piedra text-sm">
                Todavía no se registró ninguna compra de insumos.
            </div>
        @endforelse
    </div>
</div>
