<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-bosque">Cambios de zona pendientes</h1>
        <p class="text-sm text-piedra mt-1">Solicitudes de los acopiadores para trasladar un proveedor a otra ruta.</p>
    </div>

    @if ($error !== '')
        <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $error }}</div>
    @endif

    <div class="space-y-3">
        @forelse ($solicitudes as $solicitud)
            <div class="bg-white rounded-2xl border border-linea shadow-sm p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-bosque text-lg">{{ $solicitud->proveedorNombre }}</p>
                        <p class="text-sm text-piedra mt-0.5">
                            {{ $solicitud->rutaActualNombre ?? 'Sin zona actual' }}
                            <svg class="inline h-3.5 w-3.5 mx-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" /></svg>
                            <span class="font-medium text-bosque">{{ $solicitud->rutaSolicitadaNombre }}</span>
                        </p>
                        <p class="text-xs text-piedra mt-1">
                            Desde el {{ $solicitud->fechaCambio }}
                            @if ($solicitud->solicitadoPorNombre)
                                &middot; Solicitado por {{ $solicitud->solicitadoPorNombre }}
                            @endif
                        </p>
                        @if ($solicitud->motivo)
                            <p class="text-sm text-piedra mt-2 bg-crema rounded-lg px-3 py-2">{{ $solicitud->motivo }}</p>
                        @endif
                    </div>

                    <div class="flex gap-2 shrink-0">
                        <button type="button" wire:click="abrirRechazo({{ $solicitud->id }})"
                                class="px-4 py-2 rounded-xl text-sm font-medium border border-linea text-piedra hover:bg-crema">
                            Rechazar
                        </button>
                        <button type="button" wire:click="aprobar({{ $solicitud->id }})"
                                wire:confirm="¿Aprobar el cambio de {{ $solicitud->proveedorNombre }} a {{ $solicitud->rutaSolicitadaNombre }}?"
                                class="px-4 py-2 rounded-xl text-sm font-medium bg-campo text-white hover:bg-bosque">
                            Aprobar
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-linea shadow-sm p-10 text-center text-piedra text-sm">
                No hay solicitudes de cambio de zona pendientes.
            </div>
        @endforelse
    </div>

    @if ($solicitudParaRechazar !== null)
        <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="cancelarRechazo"></div>
            <div class="bg-white rounded-2xl p-6 z-10 w-full max-w-sm shadow-sm">
                <h3 class="text-lg font-semibold text-bosque mb-1">Rechazar solicitud</h3>
                <p class="text-sm text-piedra mb-4">El proveedor se queda en su ruta actual.</p>

                <label class="block text-sm font-medium text-bosque mb-1">Observación (opcional)</label>
                <textarea wire:model="observacionRechazo" rows="3"
                          class="w-full rounded-xl border-linea shadow-sm text-sm focus:border-campo focus:ring-campo"></textarea>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <button type="button" wire:click="cancelarRechazo"
                            class="flex items-center justify-center rounded-xl border border-linea text-piedra py-3 text-sm font-semibold hover:bg-crema">
                        Cancelar
                    </button>
                    <button type="button" wire:click="confirmarRechazo"
                            class="flex items-center justify-center rounded-xl bg-campo text-white py-3 text-sm font-semibold hover:bg-bosque">
                        Confirmar rechazo
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
