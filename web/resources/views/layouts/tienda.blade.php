<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Tienda · Ecolactea Huata' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-crema text-bosque antialiased min-h-screen flex flex-col">
    <header class="bg-bosque text-white sticky top-0 z-30">
        <div class="max-w-5xl mx-auto px-5 py-3 flex items-center justify-between gap-4">
            <a href="{{ route('inicio') }}" wire:navigate class="flex items-center gap-2.5">
                <img src="{{ asset('img/logo.jpg') }}" alt="Ecolactea Huata" class="h-9 w-9 rounded-full bg-white object-contain p-0.5">
                <div class="leading-none">
                    <p class="font-semibold text-white">Ecolactea</p>
                    <p class="text-[9px] tracking-[0.3em] text-dorado mt-1">HUATA</p>
                </div>
            </a>
            <div class="flex items-center gap-2 sm:gap-3">
                <livewire:tienda.carrito-badge />
                {{-- En celular solo el icono, para que la cabecera no se parta en dos lineas. --}}
                @auth
                    <a href="{{ route('dashboard') }}" aria-label="Ir al panel"
                       class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-xl border border-white/30 px-3 py-2 text-sm font-medium text-white hover:bg-white/10 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" /></svg>
                        <span class="hidden sm:inline">Ir al panel</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" aria-label="Iniciar sesión"
                       class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-xl border border-white/30 px-3 py-2 text-sm font-medium text-white hover:bg-white/10 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.1a7.5 7.5 0 0 1 15 0" /></svg>
                        <span class="hidden sm:inline">Iniciar sesión</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="bg-bosque text-white/70 text-xs text-center py-6">
        Ecolactea Huata &middot; Del campo, con cuidado.
    </footer>

    @livewireScripts
</body>
</html>
