<div class="max-w-2xl mx-auto space-y-5">
    <div class="flex items-center justify-between gap-4">
        <div>
            <a href="{{ route('acopios.index') }}" wire:navigate class="text-sm text-piedra hover:text-bosque">&larr; Acopios</a>
            <h1 class="text-2xl font-semibold text-bosque mt-1">{{ $movilidad->nombre }}</h1>
            <p class="text-sm text-piedra">{{ $movilidad->rutaNombre ?? 'Sin ruta asignada' }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-linea shadow-sm p-5 grid grid-cols-2 gap-4">
        <div>
            <p class="text-piedra text-xs uppercase tracking-wide">Litros recolectados</p>
            <p class="text-2xl font-bold text-bosque">{{ number_format($movilidad->litrosRecolectadosHoy, 1) }} L</p>
        </div>
        <div>
            <p class="text-piedra text-xs uppercase tracking-wide">Progreso del recorrido</p>
            <p class="text-2xl font-bold text-bosque">{{ $movilidad->progresoPorcentaje }}%</p>
        </div>
        <div class="col-span-2">
            <div class="h-2 bg-crema rounded-full overflow-hidden">
                <div class="h-full bg-campo transition-all" style="width: {{ $movilidad->progresoPorcentaje }}%"></div>
            </div>
        </div>
    </div>

    <div>
        <h2 class="text-sm font-semibold text-piedra uppercase tracking-wide mb-2">Recorrido</h2>
        <div class="space-y-3">
            @forelse ($movilidad->proveedores as $proveedor)
                <div class="bg-white rounded-2xl border border-linea shadow-sm p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-bosque">{{ $proveedor->nombre }}</p>
                            @if ($proveedor->finca)
                                <p class="text-xs text-piedra">{{ $proveedor->finca }}</p>
                            @endif
                        </div>

                        @if ($proveedor->estado === 'recogido')
                            <span class="inline-flex items-center gap-1 bg-salvia text-bosque px-2.5 py-1 rounded-full text-xs font-medium shrink-0">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ number_format($proveedor->litros, 1) }} L
                            </span>
                        @elseif ($proveedor->estado === 'no_entrego')
                            <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-800 px-2.5 py-1 rounded-full text-xs font-medium shrink-0">
                                No entregó
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 bg-crema text-piedra px-2.5 py-1 rounded-full text-xs font-medium shrink-0">
                                Pendiente
                            </span>
                        @endif
                    </div>

                    @if ($proveedor->cambioZonaPendiente)
                        <div class="mt-2 flex items-center gap-1.5 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-1.5">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                            Cambio a {{ $proveedor->zonaSolicitada }} pendiente de aprobación
                        </div>
                    @endif

                    @if ($proveedor->estado === 'pendiente')
                        @can('registrarIncidencia', App\Infrastructure\Movilidad\Models\MovilidadModel::class)
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <a href="{{ route('movilidades.registro-rapido', [$movilidad->id, $proveedor->proveedorId]) }}" wire:navigate
                                   class="flex items-center justify-center bg-campo text-white rounded-xl py-3 text-sm font-semibold hover:bg-bosque transition-colors">
                                    Registrar acopio
                                </a>
                                <button type="button" wire:click="abrirNoEntrega({{ $proveedor->proveedorId }})"
                                        class="flex items-center justify-center bg-white border border-linea text-piedra rounded-xl py-3 text-sm font-semibold hover:bg-crema transition-colors">
                                    No entregó
                                </button>
                            </div>
                        @endcan
                    @endif

                </div>
            @empty
                <div class="bg-white rounded-2xl border border-linea shadow-sm p-8 text-center text-piedra text-sm">
                    Esta movilidad no tiene proveedores en su ruta todavía.
                </div>
            @endforelse
        </div>
    </div>

    @if ($proveedorParaNoEntrega !== null)
        <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="cancelarNoEntrega"></div>
            <div class="bg-white rounded-2xl p-6 z-10 w-full max-w-sm shadow-sm">
                <h3 class="text-lg font-semibold text-bosque mb-1">Registrar que no entregó</h3>
                <p class="text-sm text-piedra mb-4">El proveedor queda marcado como visitado sin recepción hoy.</p>

                <label class="block text-sm font-medium text-bosque mb-1">Motivo (opcional)</label>
                <textarea wire:model="motivoNoEntrega" rows="3"
                          class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo"
                          placeholder="Ej. proveedor ausente, sin producción hoy..."></textarea>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <button type="button" wire:click="cancelarNoEntrega"
                            class="flex items-center justify-center rounded-xl border border-linea text-piedra py-3 text-sm font-semibold hover:bg-crema">
                        Cancelar
                    </button>
                    <button type="button" wire:click="confirmarNoEntrega"
                            class="flex items-center justify-center rounded-xl bg-campo text-white py-3 text-sm font-semibold hover:bg-bosque">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
