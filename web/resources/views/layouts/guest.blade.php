<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased">
    {{-- Misma composicion que la pantalla de acceso de la app Android:
         fotografia del campo, velo verde y la tarjeta en crema. --}}
    <div class="relative min-h-screen flex items-center justify-center px-4 py-10">
        <img src="{{ asset('img/campo-amanecer.jpg') }}" alt=""
             class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-b from-bosque/55 via-bosque/50 to-bosque/95"></div>

        <div class="relative w-full max-w-md">
            <div class="flex items-center justify-center gap-3 mb-7">
                <img src="{{ asset('img/logo.jpg') }}" alt="Ecolactea Huata"
                     class="h-16 w-16 rounded-full bg-white object-contain p-1 ring-1 ring-linea">
                <div class="text-left">
                    <p class="text-2xl font-bold text-white leading-none">Ecolactea</p>
                    <p class="text-[11px] tracking-[0.35em] text-dorado font-medium mt-1">HUATA</p>
                </div>
            </div>

            <div class="bg-crema rounded-3xl shadow-xl p-7">
                @yield('content')
            </div>

            <p class="mt-6 text-center text-[11px] tracking-[0.2em] text-white/85">
                ACOPIO &middot; CALIDAD &middot; PRODUCCION
            </p>
        </div>
    </div>

    @livewireScripts
</body>
</html>
