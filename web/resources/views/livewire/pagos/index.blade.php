<div class="max-w-7xl mx-auto">
    <div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-bosque">Pagos</h1>
        <p class="text-sm text-piedra mt-1">Planillas de pago semanales (jueves a miercoles).</p>
    </div>

    @can('generarPlanilla', App\Infrastructure\Pagos\Models\PagoModel::class)
        <div class="bg-white rounded-2xl border border-linea shadow-sm p-5 flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-bosque mb-1">Generar planilla para la semana que contiene</label>
                <input type="date" wire:model="fechaReferencia"
                       class="rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
            </div>
            <button type="button" wire:click="generarPlanilla"
                    class="bg-campo text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-bosque">
                Generar planilla
            </button>
        </div>
    @endcan

    @if ($mensaje !== '')
        <div class="rounded-lg bg-salvia border border-linea text-bosque px-4 py-3 text-sm">
            {{ $mensaje }}
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-linea shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <div class="transition-transform duration-300" wire:loading.class="translate-x-4 opacity-60" wire:target="gotoPage,previousPage,nextPage">
                <table class="min-w-full divide-y divide-linea text-sm">
                <thead class="bg-crema">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Semana</th>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Proveedor</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Litros</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Precio/L</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Total a pagar</th>
                        <th class="px-4 py-3 text-center font-medium text-piedra text-xs uppercase tracking-wide">Estado</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($pagos as $pago)
                        <tr wire:key="pago-{{ $pago->id }}" class="hover:bg-crema">
                            <td class="px-4 py-3 text-piedra">{{ $pago->semanaInicio }} &rarr; {{ $pago->semanaFin }}</td>
                            <td class="px-4 py-3 font-medium text-bosque">{{ $proveedores->get($pago->proveedorId)?->nombre ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-piedra">{{ number_format($pago->totalLitros, 2) }}</td>
                            <td class="px-4 py-3 text-right text-piedra">S/ {{ number_format($pago->precioLitro, 2) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-bosque">S/ {{ number_format($pago->totalPagar, 2) }}</td>
                            <td class="px-4 py-3 text-center">
                                @if ($pago->estado === 'pagado')
                                    <span class="inline-block bg-salvia text-bosque px-2 py-0.5 rounded-full text-xs font-medium">Pagado</span>
                                @else
                                    <span class="inline-block bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full text-xs font-medium">Pendiente</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-3 whitespace-nowrap">
                                <a href="{{ route('pagos.comprobante', $pago->id) }}" target="_blank"
                                   class="text-campo hover:underline font-medium">Comprobante</a>
                                @if ($pago->estado !== 'pagado')
                                    @can('generarPlanilla', App\Infrastructure\Pagos\Models\PagoModel::class)
                                        <button type="button" wire:click="marcarPagado({{ $pago->id }})"
                                                class="text-amber-700 hover:underline font-medium">Marcar pagado</button>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-piedra">No hay pagos para mostrar.</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>

        <div class="px-4 py-3 bg-white">
            <div class="flex items-center justify-center gap-4">
                <button wire:click="previousPage" wire:loading.attr="disabled" @if($pagos->onFirstPage()) disabled @endif
                        class="px-3 py-1 rounded-md border border-linea text-sm text-piedra hover:bg-crema @if($pagos->onFirstPage()) opacity-50 cursor-not-allowed @endif">
                    ‹ Anterior
                </button>

                <span class="text-sm text-piedra">Página {{ $pagos->currentPage() }} de {{ $pagos->lastPage() }}</span>

                <button wire:click="nextPage" wire:loading.attr="disabled" @if(! $pagos->hasMorePages()) disabled @endif
                        class="px-3 py-1 rounded-md border border-linea text-sm text-piedra hover:bg-crema @if(! $pagos->hasMorePages()) opacity-50 cursor-not-allowed @endif">
                    Siguiente ›
                </button>
            </div>
        </div>
    </div>
    </div>
</div>
