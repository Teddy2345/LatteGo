<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">Costeo de producción</h1>
            <p class="text-sm text-piedra mt-1">{{ $costo->fecha }} — {{ number_format($costo->litrosProcesados, 2) }} L procesados, {{ $costo->quesosProducidos }} quesos obtenidos.</p>
        </div>
        <a href="{{ route('produccion.index') }}" wire:navigate class="text-sm text-piedra hover:underline">
            &larr; Volver al listado
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-linea shadow-sm p-5">
            <p class="text-xs uppercase tracking-wide text-piedra font-medium">Costo de leche</p>
            @if ($costo->costoLeche === null)
                <p class="text-sm text-piedra mt-2">No disponible — no hay acopios sincronizados con precio para la semana de esta producción.</p>
            @else
                <p class="text-2xl font-semibold text-bosque mt-1">S/ {{ number_format($costo->costoLeche, 2) }}</p>
                <p class="text-xs text-piedra mt-1">S/ {{ number_format($costo->precioLechePromedioPonderado, 2) }} / L (promedio ponderado de esa semana de acopio)</p>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-linea shadow-sm p-5">
            <p class="text-xs uppercase tracking-wide text-piedra font-medium">Costo de insumos</p>
            <p class="text-2xl font-semibold text-bosque mt-1">S/ {{ number_format($costo->costoInsumosConocido, 2) }}</p>
            @if ($costo->costoInsumosIncompleto)
                <p class="text-xs text-amber-700 mt-1">Incompleto: hay insumos sin costo unitario registrado (ver detalle abajo).</p>
            @elseif (count($costo->insumos) === 0)
                <p class="text-xs text-piedra mt-1">Esta producción no consumió insumos del almacén.</p>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-linea shadow-sm p-5">
            <p class="text-xs uppercase tracking-wide text-piedra font-medium">Costo total</p>
            @if ($costo->costoTotal === null)
                <p class="text-sm text-piedra mt-2">No disponible — falta el costo de la leche.</p>
            @else
                <p class="text-2xl font-semibold text-bosque mt-1">S/ {{ number_format($costo->costoTotal, 2) }}</p>
                <p class="text-xs text-piedra mt-1">
                    @if ($costo->costoPorQueso !== null)
                        S/ {{ number_format($costo->costoPorQueso, 2) }} por queso
                    @else
                        Sin quesos producidos para prorratear
                    @endif
                </p>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-linea shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-linea">
            <h2 class="text-sm font-semibold text-bosque">Insumos utilizados</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-linea text-sm">
                <thead class="bg-crema">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Insumo</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Cantidad</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Costo unitario</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Costo total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($costo->insumos as $insumo)
                        <tr wire:key="costo-insumo-{{ $insumo->productoId }}">
                            <td class="px-4 py-3 text-bosque">{{ $insumo->nombre }}</td>
                            <td class="px-4 py-3 text-right text-piedra">{{ number_format($insumo->cantidad, 2) }} {{ $insumo->unidad }}</td>
                            <td class="px-4 py-3 text-right text-piedra">
                                @if ($insumo->costoUnitario === null)
                                    <span class="text-amber-700">Sin costo registrado</span>
                                @else
                                    S/ {{ number_format($insumo->costoUnitario, 2) }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-bosque">
                                {{ $insumo->costoTotal === null ? '—' : 'S/ '.number_format($insumo->costoTotal, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-piedra">No se registraron insumos para esta producción.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
