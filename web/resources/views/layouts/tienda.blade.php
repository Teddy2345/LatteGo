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
    <header class="bg-bosque text-white">
        <div class="max-w-5xl mx-auto px-5 py-3 flex items-center justify-between gap-4">
            <a href="{{ route('tienda.index') }}" wire:navigate class="flex items-center gap-2.5">
                <img src="{{ asset('img/logo.jpg') }}" alt="Ecolactea Huata" class="h-9 w-9 rounded-full bg-white object-contain p-0.5">
                <div class="leading-none">
                    <p class="font-semibold text-white">Ecolactea</p>
                    <p class="text-[9px] tracking-[0.3em] text-dorado mt-1">TIENDA</p>
                </div>
            </a>
            <livewire:tienda.carrito-badge />
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
