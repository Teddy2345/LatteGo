<div class="max-w-7xl mx-auto">
    <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">Proveedores</h1>
            <p class="text-sm text-piedra mt-1">Productores de leche registrados en la planta.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('update', App\Infrastructure\Proveedor\Models\ProveedorModel::class)
                <button type="button" wire:click="abrirPrecioTemporada"
                        class="border border-campo text-campo px-4 py-2 rounded-lg text-sm font-medium hover:bg-salvia">
                    Precio por litro
                </button>
            @endcan
            @can('create', App\Infrastructure\Proveedor\Models\ProveedorModel::class)
                <a href="{{ route('proveedores.crear') }}" wire:navigate
                   class="inline-flex items-center gap-1.5 bg-campo text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-bosque">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Nuevo proveedor
                </a>
            @endcan
        </div>
    </div>

    <div class="relative w-full sm:max-w-md">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-piedra" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
        <input type="text" wire:model.live.debounce.300ms="busqueda"
               placeholder="Buscar por nombre o cedula..."
               class="w-full pl-9 rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
    </div>

    <div class="bg-white rounded-2xl border border-linea shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <div class="transition-transform duration-300" wire:loading.class="translate-x-4 opacity-60" wire:target="gotoPage,previousPage,nextPage,busqueda">
                <table class="min-w-full divide-y divide-linea text-sm">
                <thead class="bg-crema">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Nombre</th>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Cedula</th>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Finca</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Litros prom.</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Precio/L</th>
                        <th class="px-4 py-3 text-center font-medium text-piedra text-xs uppercase tracking-wide">Estado</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($proveedores as $proveedor)
                        <tr wire:key="proveedor-{{ $proveedor->id }}" class="hover:bg-crema">
                            <td class="px-4 py-3 font-medium text-bosque">{{ $proveedor->nombre }}</td>
                            <td class="px-4 py-3 text-piedra">{{ $proveedor->cedula }}</td>
                            <td class="px-4 py-3 text-piedra">{{ $proveedor->finca ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-piedra">{{ $proveedor->litrosProm }}</td>
                            <td class="px-4 py-3 text-right text-piedra">S/ {{ number_format($proveedor->precioLitro, 2) }}</td>
                            <td class="px-4 py-3 text-center">
                                @if ($proveedor->activo)
                                    <span class="inline-block bg-salvia text-bosque px-2 py-0.5 rounded-full text-xs font-medium">Activo</span>
                                @else
                                    <span class="inline-block bg-crema text-piedra px-2 py-0.5 rounded-full text-xs font-medium">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-3 whitespace-nowrap">
                                @can('update', App\Infrastructure\Proveedor\Models\ProveedorModel::class)
                                    <a href="{{ route('proveedores.editar', $proveedor->id) }}" wire:navigate
                                       class="text-campo hover:underline font-medium">Editar</a>
                                    <button type="button"
                                            wire:click="cambiarEstado({{ $proveedor->id }}, {{ $proveedor->activo ? 'false' : 'true' }})"
                                            class="text-amber-700 hover:underline font-medium">
                                        {{ $proveedor->activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                @endcan
                                @can('delete', App\Infrastructure\Proveedor\Models\ProveedorModel::class)
                                    <button type="button"
                                            wire:click="eliminar({{ $proveedor->id }})"
                                            wire:confirm="¿Eliminar a {{ $proveedor->nombre }}? Esta accion no se puede deshacer."
                                            class="text-red-700 hover:underline font-medium">
                                        Eliminar
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-piedra">
                                No hay proveedores para mostrar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>

        <div class="px-4 py-3 bg-white">
            <div class="flex items-center justify-center gap-4">
                <button wire:click="previousPage" wire:loading.attr="disabled" @if($proveedores->onFirstPage()) disabled @endif
                        class="px-3 py-1 rounded-md border border-linea text-sm text-piedra hover:bg-crema @if($proveedores->onFirstPage()) opacity-50 cursor-not-allowed @endif">
                    ‹ Anterior
                </button>

                <span class="text-sm text-piedra">Página {{ $proveedores->currentPage() }} de {{ $proveedores->lastPage() }}</span>

                <button wire:click="nextPage" wire:loading.attr="disabled" @if(! $proveedores->hasMorePages()) disabled @endif
                        class="px-3 py-1 rounded-md border border-linea text-sm text-piedra hover:bg-crema @if(! $proveedores->hasMorePages()) opacity-50 cursor-not-allowed @endif">
                    Siguiente ›
                </button>
            </div>
        </div>
    </div>

    @if ($mostrarPrecioTemporada)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="cerrarPrecioTemporada"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="font-semibold text-bosque">Precio por litro de la temporada</h2>
                        <p class="text-xs text-piedra mt-1">
                            Se aplica a todos los proveedores. Las planillas ya generadas conservan
                            el precio con el que se pagaron: esto rige de la siguiente en adelante.
                        </p>
                    </div>
                    <button type="button" wire:click="cerrarPrecioTemporada"
                            class="text-piedra hover:text-bosque text-sm shrink-0">Cerrar</button>
                </div>

                @if ($errorPrecio !== '')
                    <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-3 py-2 text-sm">
                        {{ $errorPrecio }}
                    </div>
                @endif

                @if ($avisoPrecio !== '')
                    <div class="rounded-lg bg-salvia border border-linea text-bosque px-3 py-2 text-sm">
                        {{ $avisoPrecio }}
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-bosque mb-1">Precio por litro (S/)</label>
                    <input type="number" step="0.01" min="0.01" wire:model="precioTemporada"
                           class="w-full rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" wire:click="cerrarPrecioTemporada"
                            class="px-4 py-2 rounded-lg border border-linea text-sm text-piedra hover:bg-crema">
                        Cancelar
                    </button>
                    <button type="button" wire:click="aplicarPrecioTemporada"
                            wire:confirm="Se cambiará el precio por litro de todos los proveedores. ¿Continuar?"
                            class="bg-campo text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-bosque">
                        Aplicar a todos
                    </button>
                </div>
            </div>
        </div>
    @endif
    </div>
</div>
