<div class="max-w-7xl mx-auto">
    <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">Acopios</h1>
            <p class="text-sm text-piedra mt-1">Registro de leche recolectada en campo.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('gestionar', App\Infrastructure\Movilidad\Models\MovilidadModel::class)
                <button type="button" wire:click="abrirModalRutas"
                        class="inline-flex items-center gap-1.5 bg-white border border-linea text-campo px-4 py-2 rounded-lg text-sm font-medium shadow-sm hover:border-campo">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" /></svg>
                    Gestionar rutas y camiones
                </button>
            @endcan
            @can('create', App\Infrastructure\Acopio\Models\AcopioModel::class)
                <a href="{{ route('acopios.crear') }}" wire:navigate
                   class="inline-flex items-center gap-1.5 bg-campo text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-bosque">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Nuevo acopio
                </a>
            @endcan
        </div>
    </div>

    @if (count($movilidades) > 0)
        <section aria-labelledby="titulo-movilidades">
            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
                <h2 id="titulo-movilidades" class="text-lg font-semibold text-bosque">{{ $soloSuMovilidad ? 'Tu movilidad' : 'Camiones y motocarga' }}</h2>
                <p class="text-xs text-piedra">Avance de hoy. Elige una para ver su ruta en el mapa y los proveedores que atiende.</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                @foreach ($movilidades as $movilidad)
                    <button type="button" wire:key="movilidad-{{ $movilidad->id }}" wire:click="verCobertura({{ $movilidad->id }})"
                            class="group text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-campo rounded-2xl">
                        <x-movilidad.tarjeta :movilidad="$movilidad"
                                             class="h-full transition hover:border-campo hover:shadow-md">
                            {{-- Avance del recorrido de hoy: antes vivia en la pantalla
                                 de Movilidades, que ahora se atiende desde aqui. --}}
                            <dl class="mt-2 space-y-1 text-xs">
                                <div class="flex items-baseline justify-between gap-2">
                                    <dt class="text-piedra">Proveedores</dt>
                                    <dd class="font-semibold text-bosque">{{ $movilidad->proveedoresAtendidos }} / {{ $movilidad->totalProveedores }}</dd>
                                </div>
                                <div class="flex items-baseline justify-between gap-2">
                                    <dt class="text-piedra">Litros hoy</dt>
                                    <dd class="font-semibold text-bosque">{{ number_format($movilidad->litrosRecolectadosHoy, 1) }} L</dd>
                                </div>
                            </dl>

                            <div class="mt-2">
                                <div class="h-1.5 bg-crema rounded-full overflow-hidden">
                                    <div class="h-full bg-campo transition-all" style="width: {{ $movilidad->progresoPorcentaje }}%"></div>
                                </div>
                                <p class="text-xs text-piedra mt-1">{{ $movilidad->progresoPorcentaje }}% del recorrido</p>
                            </div>
                        </x-movilidad.tarjeta>
                    </button>
                @endforeach
            </div>
        </section>
    @endif

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

@if ($cobertura)
    @php
        $paradas = $cobertura->paradasReferencia;
        $esPlanta = $cobertura->tipo === 'planta';
    @endphp
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:key="cobertura-{{ $cobertura->id }}"
         x-data x-on:keydown.escape.window="$wire.cerrarCobertura()" role="dialog" aria-modal="true" aria-labelledby="titulo-cobertura">
        <div class="fixed inset-0 bg-black/50" wire:click="cerrarCobertura"></div>

        <div class="relative z-10 w-full max-w-5xl max-h-[90vh] overflow-y-auto bg-white rounded-2xl shadow-xl">
            <div class="flex items-center gap-4 p-5 border-b border-linea">
                <div class="w-28 shrink-0 overflow-hidden rounded-xl border border-linea">
                    <x-movilidad.imagen :tipo="$cobertura->tipo" class="aspect-[8/5]" />
                </div>
                <div class="min-w-0 flex-1">
                    <h3 id="titulo-cobertura" class="text-xl font-semibold text-bosque">{{ $cobertura->nombre }}</h3>
                    <p class="text-sm text-piedra">
                        {{ $esPlanta ? 'Proveedores que entregan directo en planta' : 'Ruta '.($cobertura->rutaNombre ?? 'sin asignar') }}
                    </p>
                    <p class="text-xs text-piedra mt-0.5">
                        Acopiador titular: {{ $cobertura->usuarioId !== null ? ($titulares[$cobertura->usuarioId] ?? '—') : 'sin asignar' }}
                    </p>
                </div>
                <button type="button" wire:click="cerrarCobertura" class="self-start text-sm text-piedra hover:text-bosque">Cerrar</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-5 p-5">
                <div class="md:col-span-3">
                    <div class="overflow-hidden rounded-xl border border-linea bg-crema">
                        <iframe src="{{ App\Presentation\Support\MapaReferencial::embed($paradas) }}"
                                class="block w-full h-72 md:h-96" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                                title="Mapa de referencia de {{ $cobertura->nombre }}"></iframe>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs text-piedra flex items-center gap-2">
                            <span class="inline-block bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full font-medium">Referencial</span>
                            Recorrido aproximado hasta tener la ruta oficial.
                        </p>
                        <a href="{{ App\Presentation\Support\MapaReferencial::enlace($paradas) }}" target="_blank" rel="noopener noreferrer"
                           class="text-sm font-medium text-campo hover:underline">Abrir en Google Maps ↗</a>
                    </div>

                    @if ($paradas !== [])
                        <ol class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-piedra">
                            <li class="font-medium text-bosque">Planta</li>
                            @foreach ($paradas as $parada)
                                <li aria-hidden="true">→</li>
                                <li class="font-medium text-bosque">{{ Str::before($parada, ',') }}</li>
                            @endforeach
                        </ol>
                    @endif
                </div>

                <div class="md:col-span-2">
                    <div class="flex items-baseline justify-between gap-2 mb-2">
                        <h4 class="font-semibold text-bosque">Proveedores de la ruta</h4>
                        <span class="text-xs text-piedra">{{ count($cobertura->proveedores) }} activos</span>
                    </div>

                    @if (count($cobertura->proveedores) > 0)
                        <ul class="divide-y divide-linea border border-linea rounded-xl md:max-h-96 overflow-y-auto">
                            @foreach ($cobertura->proveedores as $proveedor)
                                <li wire:key="cobertura-proveedor-{{ $proveedor->id }}" class="flex items-center justify-between gap-3 px-3 py-2.5">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-bosque truncate">{{ $proveedor->nombre }}</p>
                                        <p class="text-xs text-piedra truncate">
                                            {{ $proveedor->finca ?? 'Sin finca registrada' }}@if ($proveedor->telefono) · {{ $proveedor->telefono }}@endif
                                        </p>
                                    </div>
                                    <span class="shrink-0 text-xs text-piedra">{{ number_format($proveedor->litrosPromedio, 0) }} L prom.</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-piedra border border-dashed border-linea rounded-xl p-4">Esta ruta todavía no tiene proveedores activos.</p>
                    @endif

                    <a href="{{ route('movilidades.detalle', $cobertura->id) }}" wire:navigate
                       class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-campo hover:underline">
                        Ver el recorrido de hoy
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" /></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif

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
</div>
