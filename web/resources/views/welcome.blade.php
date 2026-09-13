<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="antialiased">
    <div class="relative min-h-screen flex items-center justify-center px-6 py-12">
        <img src="{{ asset('img/campo-amanecer.jpg') }}" alt=""
             class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-b from-bosque/60 via-bosque/55 to-bosque/95"></div>

        <div class="relative w-full max-w-lg text-center">
            <div class="flex items-center justify-center gap-3">
                <img src="{{ asset('img/logo.jpg') }}" alt="Ecolactea Huata"
                     class="h-16 w-16 rounded-full bg-white object-contain p-1 ring-1 ring-linea">
                <div class="text-left">
                    <p class="text-3xl font-bold text-white leading-none">Ecolactea</p>
                    <p class="text-xs tracking-[0.35em] text-dorado font-medium mt-1">HUATA</p>
                </div>
            </div>

            <h1 class="mt-8 text-3xl sm:text-4xl font-semibold text-white leading-snug">
                Del campo, con cuidado.
            </h1>
            <p class="mt-3 text-white/90">
                Acopio, calidad, produccion y pagos de la planta lechera de Huata.
            </p>

            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-campo px-6 py-3 text-sm font-semibold text-white hover:bg-bosque transition-colors">
                        Ir al panel
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-campo px-6 py-3 text-sm font-semibold text-white hover:bg-bosque transition-colors">
                        Ingresar al sistema
                    </a>
                @endauth
                <a href="{{ route('tienda.index') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/10 border border-white/30 px-6 py-3 text-sm font-semibold text-white hover:bg-white/20 transition-colors">
                    Visitar la tienda
                </a>
            </div>

            <p class="mt-10 text-[11px] tracking-[0.2em] text-white/80">
                ACOPIO &middot; CALIDAD &middot; PRODUCCION
            </p>
        </div>
    </div>
</body>
</html>
