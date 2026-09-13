<div class="max-w-7xl mx-auto">
    <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">Control de calidad</h1>
            <p class="text-sm text-piedra mt-1">Analisis de laboratorio (LactoScan) por acopio.</p>
        </div>
        @can('create', App\Infrastructure\Calidad\Models\CalidadModel::class)
            <a href="{{ route('calidad.crear') }}" wire:navigate
               class="inline-flex items-center gap-1.5 bg-campo text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-bosque">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Nuevo analisis
            </a>
        @endcan
    </div>

    <div class="bg-white rounded-2xl border border-linea shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <div class="transition-transform duration-300" wire:loading.class="translate-x-4 opacity-60" wire:target="gotoPage,previousPage,nextPage">
                <table class="min-w-full divide-y divide-linea text-sm">
                <thead class="bg-crema">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Fecha</th>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Proveedor</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Densidad</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Agua %</th>
                        <th class="px-4 py-3 text-center font-medium text-piedra text-xs uppercase tracking-wide">Resultado</th>
                        <th class="px-4 py-3 text-center font-medium text-piedra text-xs uppercase tracking-wide">Sancion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($analisis as $item)
                        <tr wire:key="calidad-{{ $item->id }}" class="hover:bg-crema">
                            <td class="px-4 py-3 text-piedra">{{ $item->fecha }}</td>
                            <td class="px-4 py-3 font-medium text-bosque">{{ $proveedores->get($item->proveedorId)?->nombre ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-piedra">{{ number_format($item->densidad, 2) }}</td>
                            <td class="px-4 py-3 text-right text-piedra">{{ number_format($item->aguaAgregada, 2) }}</td>
                            <td class="px-4 py-3 text-center">
                                @if ($item->resultado === 'aceptada')
                                    <span class="inline-block bg-salvia text-bosque px-2 py-0.5 rounded-full text-xs font-medium">Aceptada</span>
                                @else
                                    <span class="inline-block bg-red-100 text-red-800 px-2 py-0.5 rounded-full text-xs font-medium">
                                        Rechazada ({{ $item->motivoRechazo }})
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($item->sancionAplicada === 'ninguna')
                                    <span class="text-gray-300">—</span>
                                @else
                                    <span class="inline-block bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full text-xs font-medium">
                                        {{ str_replace('_', ' ', $item->sancionAplicada) }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-piedra">No hay analisis para mostrar.</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>

        <div class="px-4 py-3 bg-white">
            <div class="flex items-center justify-center gap-4">
                <button wire:click="previousPage" wire:loading.attr="disabled" @if($analisis->onFirstPage()) disabled @endif
                        class="px-3 py-1 rounded-md border border-linea text-sm text-piedra hover:bg-crema @if($analisis->onFirstPage()) opacity-50 cursor-not-allowed @endif">
                    ‹ Anterior
                </button>

                <span class="text-sm text-piedra">Página {{ $analisis->currentPage() }} de {{ $analisis->lastPage() }}</span>

                <button wire:click="nextPage" wire:loading.attr="disabled" @if(! $analisis->hasMorePages()) disabled @endif
                        class="px-3 py-1 rounded-md border border-linea text-sm text-piedra hover:bg-crema @if(! $analisis->hasMorePages()) opacity-50 cursor-not-allowed @endif">
                    Siguiente ›
                </button>
            </div>
        </div>
    </div>
    </div>
</div>
