<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-crema text-bosque antialiased" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen lg:flex">
        {{-- Overlay movil --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/40 z-30 lg:hidden"></div>

        {{-- Sidebar --}}
        <aside
            class="fixed inset-y-0 left-0 z-40 w-64 bg-bosque text-salvia flex flex-col transform transition-transform duration-200 ease-in-out lg:static lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="h-16 flex items-center gap-3 px-5 border-b border-white/10 shrink-0">
                <img src="{{ asset('img/logo.jpg') }}" alt="Ecolactea Huata"
                     class="h-9 w-9 rounded-full bg-white object-contain p-0.5 shrink-0">
                <div class="leading-none">
                    <p class="font-semibold tracking-tight text-white">Ecolactea</p>
                    <p class="text-[9px] tracking-[0.3em] text-dorado mt-1">HUATA</p>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-sm">
                @php $current = request()->route()?->getName() ?? ''; @endphp

                <x-nav-link href="{{ route('dashboard') }}" :active="$current === 'dashboard'">
                    <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" /></x-slot:icon>
                    Dashboard
                </x-nav-link>

                @can('viewAny', App\Infrastructure\Proveedor\Models\ProveedorModel::class)
                    <x-nav-link href="{{ route('proveedores.index') }}" :active="$current === 'proveedores.index' || str_starts_with($current, 'proveedores.crear') || str_starts_with($current, 'proveedores.editar')">
                        <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 5.25v-5.25m0 0h-12" /></x-slot:icon>
                        Proveedores
                    </x-nav-link>
                @endcan

                @can('viewAny', App\Infrastructure\Proveedor\Models\SolicitudCambioZonaModel::class)
                    <x-nav-link href="{{ route('proveedores.cambios-zona') }}" :active="$current === 'proveedores.cambios-zona'">
                        <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3m14.25 7.5L21 15.75m0 0-3.75-3.75M21 15.75H3" /></x-slot:icon>
                        Cambios de zona
                    </x-nav-link>
                @endcan

                @can('viewAny', App\Infrastructure\Movilidad\Models\MovilidadModel::class)
                    <x-nav-link href="{{ route('movilidades.index') }}" :active="str_starts_with($current, 'movilidades.')">
                        <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 5.25v-5.25m0 0h-12" /></x-slot:icon>
                        Movilidades
                    </x-nav-link>
                @endcan

                @can('viewAny', App\Infrastructure\Acopio\Models\AcopioModel::class)
                    <x-nav-link href="{{ route('acopios.index') }}" :active="str_starts_with($current, 'acopios.')">
                        <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75c-3.75 4-6 7.09-6 9.75a6 6 0 1 0 12 0c0-2.66-2.25-5.75-6-9.75Z" /></x-slot:icon>
                        Acopios
                    </x-nav-link>
                @endcan

                @can('viewAny', App\Infrastructure\Calidad\Models\CalidadModel::class)
                    <x-nav-link href="{{ route('calidad.index') }}" :active="str_starts_with($current, 'calidad.')">
                        <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23-.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" /></x-slot:icon>
                        Calidad
                    </x-nav-link>
                @endcan

                @can('viewAny', App\Infrastructure\Produccion\Models\ProduccionModel::class)
                    <x-nav-link href="{{ route('produccion.index') }}" :active="str_starts_with($current, 'produccion.')">
                        <x-slot:icon>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5 11.15 5.47a1 1 0 0 1 1.7 0l6.65 14.03a1 1 0 0 1-.9 1.5H5.4a1 1 0 0 1-.9-1.5Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 16.5h.008v.008H9.75V16.5Zm3-3h.008v.008h-.008v-.008Z" />
                        </x-slot:icon>
                        Producción
                    </x-nav-link>
                @endcan

                @can('viewAny', App\Infrastructure\Inventario\Models\ProductoModel::class)
                    <x-nav-link href="{{ route('inventario.index') }}" :active="str_starts_with($current, 'inventario.')">
                        <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3.75 3.75 7.5m16.5 0L12 11.25M20.25 7.5v9L12 20.25m0-9L3.75 7.5m8.25 3.75v9M3.75 7.5v9L12 20.25" /></x-slot:icon>
                        Almacen
                    </x-nav-link>
                @endcan

                @can('verVentas', App\Infrastructure\Inventario\Models\MovimientoInventarioModel::class)
                    <x-nav-link href="{{ route('ventas.index') }}" :active="str_starts_with($current, 'ventas.')">
                        <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></x-slot:icon>
                        Ventas
                    </x-nav-link>
                @endcan

                @can('viewAny', App\Infrastructure\Pedidos\Models\PedidoModel::class)
                    <x-nav-link href="{{ route('pedidos.index') }}" :active="str_starts_with($current, 'pedidos.')">
                        <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" /></x-slot:icon>
                        Pedidos
                    </x-nav-link>
                @endcan

                @can('viewAny', App\Infrastructure\Pagos\Models\PagoModel::class)
                    <x-nav-link href="{{ route('pagos.index') }}" :active="str_starts_with($current, 'pagos.')">
                        <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></x-slot:icon>
                        Pagos
                    </x-nav-link>
                @endcan

                @can('viewAny', App\Infrastructure\Notificacion\Models\NotificacionModel::class)
                    <x-nav-link href="{{ route('notificaciones.index') }}" :active="$current === 'notificaciones.index'">
                        <x-slot:icon><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></x-slot:icon>
                        Notificaciones
                    </x-nav-link>
                @endcan
            </nav>

            <div class="border-t border-white/10 p-3">
                <a href="{{ route('dos-factores.setup') }}" wire:navigate
                   class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs text-salvia hover:bg-white/10">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                    Seguridad (2FA)
                </a>
            </div>
        </aside>

        {{-- Contenido --}}
        <div class="flex-1 flex flex-col min-w-0">
            <header class="h-16 bg-white border-b border-linea flex items-center justify-between px-4 lg:px-8 shrink-0">
                <button type="button" @click="sidebarOpen = true" class="lg:hidden text-piedra hover:text-bosque">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                </button>

                <div class="hidden lg:block text-sm text-piedra">
                    {{ now()->translatedFormat('l, d \d\e F \d\e Y') }}
                </div>

                @auth
                    <div class="flex items-center gap-4 text-sm">
                        @can('viewAny', App\Infrastructure\Notificacion\Models\NotificacionModel::class)
                            <livewire:notificacion.campana />
                        @endcan

                        <div class="text-right hidden sm:block">
                            <div class="font-medium text-bosque">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-piedra">{{ auth()->user()->getRoleNames()->first() }}</div>
                        </div>
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-salvia text-bosque font-semibold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" title="Cerrar sesion" class="text-piedra hover:text-red-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" /></svg>
                            </button>
                        </form>
                    </div>
                @endauth
            </header>

            <main class="flex-1 px-4 py-6 lg:px-8 lg:py-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
