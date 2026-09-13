<div class="max-w-5xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-bosque">Movilidades</h1>
        <p class="text-sm text-piedra mt-1">Elige tu camión o el recorrido que vas a atender hoy.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @forelse ($movilidades as $movilidad)
            <a href="{{ route('movilidades.detalle', $movilidad->id) }}" wire:navigate
               class="block bg-white rounded-2xl border border-linea shadow-sm p-5 hover:border-campo transition-colors">
                <div class="flex items-center gap-4">
                    <div class="h-16 w-20 flex items-center justify-center bg-salvia rounded-xl shrink-0">
                        @if ($movilidad->tipo === 'planta')
                            <svg class="h-9 w-9 text-campo" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75" /></svg>
                        @elseif ($movilidad->tipo === 'motocarga')
                            <svg class="h-9 w-9 text-campo" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h9m-9 0H3.75v-4.5l1.5-4.5h6l3 4.5h3.75a1.5 1.5 0 0 1 1.5 1.5v3h-1.5m-9-4.5h7.5" /></svg>
                        @else
                            <svg class="h-9 w-9 text-campo" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 5.25v-5.25m0 0h-12" /></svg>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-bosque text-lg">{{ $movilidad->nombre }}</p>
                        <p class="text-sm text-piedra">{{ $movilidad->rutaNombre ?? 'Sin ruta asignada' }}</p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-piedra text-xs uppercase tracking-wide">Proveedores</p>
                        <p class="text-bosque font-semibold">{{ $movilidad->proveedoresAtendidos }} / {{ $movilidad->totalProveedores }}</p>
                    </div>
                    <div>
                        <p class="text-piedra text-xs uppercase tracking-wide">Litros hoy</p>
                        <p class="text-bosque font-semibold">{{ number_format($movilidad->litrosRecolectadosHoy, 1) }} L</p>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="h-2 bg-crema rounded-full overflow-hidden">
                        <div class="h-full bg-campo transition-all" style="width: {{ $movilidad->progresoPorcentaje }}%"></div>
                    </div>
                    <p class="text-xs text-piedra mt-1">{{ $movilidad->progresoPorcentaje }}% del recorrido</p>
                </div>

                <div class="mt-4 flex items-center justify-end gap-1 text-campo font-medium text-sm">
                    Ver recorrido
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" /></svg>
                </div>
            </a>
        @empty
            <div class="sm:col-span-2 bg-white rounded-2xl border border-linea shadow-sm p-10 text-center text-piedra text-sm">
                @if ($filtradoPorUsuario)
                    Todavía no tienes una movilidad asignada. Pídele a un administrador que te asigne la tuya desde Acopios → Rutas y Camiones.
                @else
                    Todavía no hay movilidades registradas. Un administrador puede crearlas desde Acopios → Rutas y Camiones.
                @endif
            </div>
        @endforelse
    </div>
</div>
