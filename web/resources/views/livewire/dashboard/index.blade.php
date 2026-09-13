<div class="max-w-7xl mx-auto">
    <div class="space-y-8">
    <div>
        <p class="text-[10px] tracking-[0.14em] text-piedra font-medium">TU ESPACIO DE TRABAJO</p>
        <h1 class="text-2xl font-semibold text-bosque mt-1">Hola, {{ explode(' ', $user->name)[0] }}</h1>
        <p class="text-sm text-piedra mt-1">
            Resumen general —
            <span class="inline-block bg-salvia text-bosque px-2 py-0.5 rounded-full text-xs font-medium align-middle">
                {{ ucfirst($user->getRoleNames()->first() ?? '') }}
            </span>
        </p>
    </div>

    {{-- Portada: la misma pieza que abre el inicio de la app movil --}}
    <div class="relative h-44 rounded-3xl overflow-hidden">
        <img src="{{ asset('img/campo-atardecer.jpg') }}" alt="" class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-r from-bosque/90 via-bosque/60 to-bosque/10"></div>
        <div class="relative h-full flex flex-col justify-center gap-2 px-7 max-w-lg">
            <p class="text-[10px] tracking-[0.16em] text-dorado font-bold">DE NUESTRA TIERRA</p>
            <p class="text-2xl font-semibold text-white leading-snug">Calidad que nace<br>en el campo.</p>
            <p class="text-xs text-white/85">Ecolactea Huata</p>
        </div>
    </div>

    {{-- Acciones rapidas --}}
    <div class="flex flex-wrap gap-3">
        @can('proveedores.crear')
            <a href="{{ route('proveedores.crear') }}" wire:navigate class="inline-flex items-center gap-2 bg-white border border-linea shadow-sm px-4 py-2 rounded-lg text-sm font-medium text-bosque hover:bg-crema">
                + Nuevo proveedor
            </a>
        @endcan
        @can('acopios.registrar')
            <a href="{{ route('acopios.crear') }}" wire:navigate class="inline-flex items-center gap-2 bg-white border border-linea shadow-sm px-4 py-2 rounded-lg text-sm font-medium text-bosque hover:bg-crema">
                + Registrar acopio
            </a>
        @endcan
        @can('calidad.registrar')
            <a href="{{ route('calidad.crear') }}" wire:navigate class="inline-flex items-center gap-2 bg-white border border-linea shadow-sm px-4 py-2 rounded-lg text-sm font-medium text-bosque hover:bg-crema">
                + Nuevo analisis de calidad
            </a>
        @endcan
        @can('produccion.registrar')
            <a href="{{ route('produccion.crear') }}" wire:navigate class="inline-flex items-center gap-2 bg-white border border-linea shadow-sm px-4 py-2 rounded-lg text-sm font-medium text-bosque hover:bg-crema">
                + Nueva produccion
            </a>
        @endcan
    </div>

    {{-- Metricas --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @if ($puedeProveedores)
            <x-stat-card label="Proveedores activos" :value="$proveedoresActivos" :hint="$proveedoresTotal.' en total'">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 5.25v-5.25m0 0h-12" /></svg>
                </x-slot:icon>
            </x-stat-card>
        @endif

        @if ($puedeAcopios)
            <x-stat-card label="Litros esta semana" :value="number_format($litrosSemana, 0)" :hint="$semanaInicio.' → '.$semanaFin">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75c-3.75 4-6 7.09-6 9.75a6 6 0 1 0 12 0c0-2.66-2.25-5.75-6-9.75Z" /></svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card label="Acopios sin sincronizar" :value="$acopiosPendientes" tone="{{ $acopiosPendientes > 0 ? 'amber' : 'campo' }}" hint="Pendientes desde la app movil">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                </x-slot:icon>
            </x-stat-card>
        @endif

        @if ($puedeCalidad)
            <x-stat-card label="Rechazos (7 dias)" :value="$rechazosRecientes" tone="{{ $rechazosRecientes > 0 ? 'red' : 'campo' }}" hint="Analisis de calidad rechazados">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                </x-slot:icon>
            </x-stat-card>
        @endif

        @if ($puedeProduccion)
            <x-stat-card label="Rendimiento fuera de rango" :value="$produccionFueraDeRango" tone="{{ $produccionFueraDeRango > 0 ? 'amber' : 'campo' }}" hint="Fuera de 11-12 quesos/100L">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75" /></svg>
                </x-slot:icon>
            </x-stat-card>
        @endif

        @if ($puedeInventario)
            <x-stat-card label="Productos sin stock" :value="$productosAgotados" tone="{{ $productosAgotados > 0 ? 'amber' : 'campo' }}" :hint="$productosTotal.' productos en catalogo'">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3.75 3.75 7.5m16.5 0L12 11.25M20.25 7.5v9L12 20.25m0-9L3.75 7.5m8.25 3.75v9M3.75 7.5v9L12 20.25" /></svg>
                </x-slot:icon>
            </x-stat-card>
        @endif

        @if ($puedeVentas)
            <x-stat-card label="Ventas esta semana" :value="'S/ '.number_format($ventasSemana, 2)" hint="Producto terminado comercializado">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>
                </x-slot:icon>
            </x-stat-card>
        @endif

        @if ($puedePagos)
            <x-stat-card label="Pagos pendientes" :value="$pagosPendientesCantidad" tone="{{ $pagosPendientesCantidad > 0 ? 'amber' : 'campo' }}" :hint="'S/ '.number_format($pagosPendientesMonto, 2).' por pagar'">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-9.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
                </x-slot:icon>
            </x-stat-card>
        @endif

        @if ($puedePedidos)
            <x-stat-card label="Pedidos pendientes" :value="$pedidosPendientes" tone="{{ $pedidosPendientes > 0 ? 'amber' : 'campo' }}" hint="De la tienda web, por confirmar">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" /></svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card label="Recaudado esta semana" :value="'S/ '.number_format($recaudadoSemana, 2)" hint="Pedidos entregados y cobrados">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182.553-.44 1.278-.659 2.003-.659.725 0 1.45.22 2.003.659l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </x-slot:icon>
            </x-stat-card>
        @endif
    </div>

    {{-- Tendencias --}}
    @if ($puedeAcopios || $puedeProduccion || $puedeVentas || $puedePedidos)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @if ($puedeAcopios)
                <x-weekly-bar-chart title="Litros acopiados por semana" subtitle="Últimas 8 semanas" :data="$tendenciaLitros"
                                     :formatter="fn (float $v) => number_format($v, 0).' L'" />
            @endif

            @if ($puedeProduccion)
                <x-weekly-bar-chart title="Quesos producidos por semana" subtitle="Últimas 8 semanas" :data="$tendenciaProduccion"
                                     :formatter="fn (float $v) => number_format($v, 0)" />
            @endif

            @if ($puedeVentas)
                <x-weekly-bar-chart title="Ventas por semana" subtitle="Últimas 8 semanas" :data="$tendenciaVentas"
                                     :formatter="fn (float $v) => 'S/ '.number_format($v, 0)" />
            @endif

            @if ($puedePedidos)
                <div class="bg-white rounded-2xl border border-linea shadow-sm p-5">
                    <h2 class="font-semibold text-bosque mb-4">Pedidos por estado</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach ($pedidosPorEstado as $estado => $cantidad)
                            <div class="text-center bg-crema rounded-xl py-4">
                                <p class="text-2xl font-semibold text-bosque">{{ $cantidad }}</p>
                                <x-pedidos.estado-badge :estado="$estado" />
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ route('pedidos.index') }}" wire:navigate class="block text-center text-xs text-campo hover:underline mt-4">
                        Ver todos los pedidos
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- Actividad reciente --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @if ($puedeAcopios)
            <div class="bg-white rounded-2xl border border-linea shadow-sm">
                <div class="px-5 py-4 border-b border-linea flex items-center justify-between">
                    <h2 class="font-semibold text-bosque">Ultimos acopios</h2>
                    <a href="{{ route('acopios.index') }}" wire:navigate class="text-xs text-campo hover:underline">Ver todos</a>
                </div>
                <ul class="divide-y divide-linea">
                    @forelse ($ultimosAcopios as $acopio)
                        <li class="px-5 py-3 flex items-center justify-between text-sm">
                            <span class="text-piedra">{{ $acopio->fecha }}</span>
                            <span class="font-medium text-bosque">{{ number_format($acopio->cantidadLitros, 2) }} L</span>
                            @if ($acopio->estado === 'sincronizado')
                                <span class="inline-block bg-salvia text-bosque px-2 py-0.5 rounded-full text-xs">Sincronizado</span>
                            @else
                                <span class="inline-block bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full text-xs">Pendiente</span>
                            @endif
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-piedra">Sin acopios registrados todavia.</li>
                    @endforelse
                </ul>
            </div>
        @endif

        @if ($puedePagos)
            <div class="bg-white rounded-2xl border border-linea shadow-sm">
                <div class="px-5 py-4 border-b border-linea flex items-center justify-between">
                    <h2 class="font-semibold text-bosque">Ultimos pagos</h2>
                    <a href="{{ route('pagos.index') }}" wire:navigate class="text-xs text-campo hover:underline">Ver todos</a>
                </div>
                <ul class="divide-y divide-linea">
                    @forelse ($ultimosPagos as $pago)
                        <li class="px-5 py-3 flex items-center justify-between text-sm">
                            <span class="text-piedra">{{ $pago->semanaInicio }} → {{ $pago->semanaFin }}</span>
                            <span class="font-medium text-bosque">S/ {{ number_format($pago->totalPagar, 2) }}</span>
                            @if ($pago->estado === 'pagado')
                                <span class="inline-block bg-salvia text-bosque px-2 py-0.5 rounded-full text-xs">Pagado</span>
                            @else
                                <span class="inline-block bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full text-xs">Pendiente</span>
                            @endif
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-piedra">Sin pagos generados todavia.</li>
                    @endforelse
                </ul>
            </div>
        @endif

        @if ($puedePedidos)
            <div class="bg-white rounded-2xl border border-linea shadow-sm">
                <div class="px-5 py-4 border-b border-linea flex items-center justify-between">
                    <h2 class="font-semibold text-bosque">Últimos pedidos</h2>
                    <a href="{{ route('pedidos.index') }}" wire:navigate class="text-xs text-campo hover:underline">Ver todos</a>
                </div>
                <ul class="divide-y divide-linea">
                    @forelse ($ultimosPedidos as $pedido)
                        <li class="px-5 py-3 flex items-center justify-between text-sm">
                            <span class="text-piedra">{{ $pedido->clienteNombre }}</span>
                            <span class="font-medium text-bosque">S/ {{ number_format($pedido->total, 2) }}</span>
                            <x-pedidos.estado-badge :estado="$pedido->estado" />
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-piedra">Sin pedidos de la tienda todavía.</li>
                    @endforelse
                </ul>
            </div>
        @endif
    </div>
    </div>
</div>
