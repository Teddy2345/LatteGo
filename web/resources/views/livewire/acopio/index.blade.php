<div class="max-w-7xl mx-auto">
    <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-bosque">Acopios</h1>
                <p class="text-sm text-piedra mt-1">Registro de leche recolectada en campo.</p>
            </div>

            @can('gestionar', App\Infrastructure\Movilidad\Models\MovilidadModel::class)
            <div class="ml-2">
                <div class="flex items-center gap-2 bg-white/80 backdrop-blur-sm px-3 py-2 rounded-lg border border-linea shadow-sm">
                    <button type="button" wire:click="abrirModalRutas" class="flex items-center gap-2">
                        <!-- Camión animado (CSS inline): ruedas girando y cuerpo rebotando -->
                        <svg class="h-8 w-12" viewBox="0 0 64 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="img">
                            <style>
                                .truck-body { fill: #0f766e; transform-box: fill-box; transform-origin: center; animation: bounce 1s ease-in-out infinite; }
                                .truck-cab { fill: #115e59; }
                                .wheel { fill: #0b3b2e; transform-box: fill-box; transform-origin: center; animation: spin 1s linear infinite; }
                                @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
                                @keyframes bounce { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-3px); } }
                            </style>

                            <!-- Cuerpo del camión -->
                            <g class="truck-body">
                                <rect x="2" y="10" width="38" height="18" rx="3" />
                            </g>
                            <g class="truck-cab">
                                <rect x="40" y="14" width="14" height="12" rx="2" />
                                <rect x="43" y="17" width="6" height="6" rx="1" fill="#ffffff" fill-opacity="0.95" />
                            </g>

                            <!-- Ruedas -->
                            <g>
                                <g class="wheel" transform="translate(18 30)">
                                    <circle cx="0" cy="0" r="4" />
                                </g>
                                <g class="wheel" transform="translate(46 30)">
                                    <circle cx="0" cy="0" r="4" />
                                </g>
                            </g>
                        </svg>
                        <span class="text-sm text-piedra">Rutas y Camiones</span>
                    </button>

                    @if (Route::has('rutas.index'))
                        <a href="{{ route('rutas.index') }}" class="ml-2 text-campo text-sm hover:underline">Ir a gestión de rutas</a>
                    @endif
                </div>
            </div>
            @endcan
        @can('create', App\Infrastructure\Acopio\Models\AcopioModel::class)
            <a href="{{ route('acopios.crear') }}" wire:navigate
               class="inline-flex items-center gap-1.5 bg-campo text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-bosque">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Nuevo acopio
            </a>
        @endcan
    </div>

    <select wire:model.live="filtroEstado"
            class="rounded-lg border-linea shadow-sm text-sm focus:border-campo focus:ring-campo">
        <option value="">Todos los estados</option>
        <option value="pendiente_sincronizar">Pendiente de sincronizar</option>
        <option value="sincronizado">Sincronizado</option>
    </select>

    <div class="bg-white rounded-2xl border border-linea shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <div class="transition-transform duration-300" wire:loading.class="translate-x-4 opacity-60" wire:target="gotoPage,previousPage,nextPage,filtroEstado">
                <table class="min-w-full divide-y divide-linea text-sm">
                <thead class="bg-crema">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Fecha</th>
                        <th class="px-4 py-3 text-left font-medium text-piedra text-xs uppercase tracking-wide">Proveedor</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Litros</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Perdida</th>
                        <th class="px-4 py-3 text-center font-medium text-piedra text-xs uppercase tracking-wide">Estado</th>
                        <th class="px-4 py-3 text-right font-medium text-piedra text-xs uppercase tracking-wide">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @forelse ($acopios as $acopio)
                        <tr wire:key="acopio-{{ $acopio->id }}" class="hover:bg-crema">
                            <td class="px-4 py-3 text-piedra">{{ $acopio->fecha }}</td>
                            <td class="px-4 py-3 font-medium text-bosque">{{ $proveedores->get($acopio->proveedorId)?->nombre ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-piedra">{{ number_format($acopio->cantidadLitros, 2) }}</td>
                            <td class="px-4 py-3 text-right text-piedra">
                                {{ $acopio->perdidaLitros !== null ? number_format($acopio->perdidaLitros, 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($acopio->estado === 'sincronizado')
                                    <span class="inline-block bg-salvia text-bosque px-2 py-0.5 rounded-full text-xs font-medium">Sincronizado</span>
                                @else
                                    <span class="inline-block bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full text-xs font-medium">Pendiente</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($acopio->estado !== 'sincronizado' && auth()->user()->can('sincronizar', App\Infrastructure\Acopio\Models\AcopioModel::class))
                                    <button type="button" wire:click="sincronizar({{ $acopio->id }})"
                                            class="text-campo hover:underline font-medium">Sincronizar</button>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-piedra">No hay acopios para mostrar.</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>

        <div class="px-4 py-3 bg-white">
            <div class="flex items-center justify-center gap-4">
                <button wire:click="previousPage" wire:loading.attr="disabled" @if($acopios->onFirstPage()) disabled @endif
                        class="px-3 py-1 rounded-md border border-linea text-sm text-piedra hover:bg-crema @if($acopios->onFirstPage()) opacity-50 cursor-not-allowed @endif">
                    ‹ Anterior
                </button>

                <span class="text-sm text-piedra">Página {{ $acopios->currentPage() }} de {{ $acopios->lastPage() }}</span>

                <button wire:click="nextPage" wire:loading.attr="disabled" @if(! $acopios->hasMorePages()) disabled @endif
                        class="px-3 py-1 rounded-md border border-linea text-sm text-piedra hover:bg-crema @if(! $acopios->hasMorePages()) opacity-50 cursor-not-allowed @endif">
                    Siguiente ›
                </button>
            </div>
        </div>
    </div>
    </div>
</div>

@if($showRutasModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" wire:click="cerrarModalRutas"></div>
        <div class="bg-white rounded-2xl p-6 z-10 w-full max-w-lg max-h-[85vh] overflow-y-auto shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-bosque">Rutas y Camiones</h3>
                <button type="button" wire:click="cerrarModalRutas" class="text-piedra hover:text-bosque">Cerrar</button>
            </div>

            @if ($errorCamion)
                <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-700 px-3 py-2 text-sm">{{ $errorCamion }}</div>
            @endif

            @if(count($rutas) > 0)
                <div class="mt-2 space-y-4">
                    @foreach($rutas as $ruta)
                        <div class="border border-linea rounded-xl p-3">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm font-medium text-bosque">{{ $ruta['nombre'] ?? '—' }}</div>
                                <button type="button" wire:click="agregarCamion({{ $ruta['id'] }})" class="text-sm text-campo hover:underline">+ Agregar camión</button>
                            </div>

                            @php $camiones = $camionesPorRuta[$ruta['id']] ?? []; @endphp

                            @if(count($camiones) > 0)
                                <div class="grid grid-cols-1 gap-3">
                                    @foreach($camiones as $camion)
                                        <div class="flex items-start gap-3 p-3 rounded-xl bg-crema">
                                            <div class="h-12 w-16 flex items-center justify-center bg-white rounded-lg shrink-0">
                                                <svg class="h-8 w-8 text-campo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 13h13v-6H3v6z"></path><path d="M16 13l2 3h3"></path><circle cx="7.5" cy="17.5" r="1.5"></circle><circle cx="18.5" cy="17.5" r="1.5"></circle></svg>
                                            </div>

                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="flex items-center gap-3 min-w-0">
                                                        <input type="radio" name="camion_activo_{{ $ruta['id'] }}"
                                                               @if($camion['activa']) checked @endif
                                                               title="Camión activo hoy en esta ruta"
                                                               wire:click="seleccionarCamion({{ $camion['id'] }})">

                                                        <input type="text" value="{{ $camion['nombre'] }}" placeholder="Nombre del camión"
                                                               class="text-sm font-medium border-none bg-transparent focus:ring-0 px-0"
                                                               wire:change="actualizarNombreCamion({{ $camion['id'] }}, $event.target.value)">
                                                    </div>

                                                    <button type="button" wire:click="quitarCamion({{ $camion['id'] }})" class="text-sm text-rose-600 shrink-0">Quitar</button>
                                                </div>

                                                <div class="mt-2">
                                                    <div class="text-xs text-piedra mb-1">Acopiador titular (su propia ruta y proveedores)</div>
                                                    <select class="w-full rounded-lg border-linea text-sm"
                                                            wire:change="asignarUsuario({{ $camion['id'] }}, $event.target.value)">
                                                        <option value="">Sin asignar</option>
                                                        @foreach ($usuariosAcopiadores as $usuario)
                                                            <option value="{{ $usuario->id }}" @selected($camion['usuarioId'] === $usuario->id)>{{ $usuario->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="mt-2">
                                                    <div class="text-xs text-piedra mb-1">Personas en este camión</div>
                                                    @if(!empty($camion['personas']))
                                                        <ul class="space-y-1.5">
                                                            @foreach($camion['personas'] as $persona)
                                                                <li class="flex items-center justify-between gap-2 text-sm">
                                                                    <span class="text-bosque">{{ $persona['nombre'] }}</span>
                                                                    <button type="button" wire:click="quitarPersona({{ $persona['id'] }})" class="text-rose-600 text-xs">Eliminar</button>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <p class="text-sm text-piedra">No hay personas asignadas.</p>
                                                    @endif

                                                    <div class="mt-2 flex gap-2">
                                                        <input type="text" placeholder="Nombre persona" class="flex-1 rounded-lg border-linea text-sm" wire:model.defer="nuevaPersonaPorCamion.{{ $camion['id'] }}">
                                                        <button type="button" wire:click="agregarPersona({{ $camion['id'] }})" class="text-sm bg-campo text-white px-3 py-1 rounded-lg hover:bg-bosque">Agregar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-piedra">No hay camiones para esta ruta.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-piedra">No hay rutas registradas.</p>
            @endif
        </div>
    </div>
@endif
