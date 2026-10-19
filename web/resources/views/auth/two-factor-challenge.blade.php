@extends('layouts.guest')

@section('title', 'Verificación en dos pasos')

@section('content')
    <h2 class="text-lg font-semibold mb-2">Verificación en dos pasos</h2>
    <p class="text-sm text-piedra mb-4">
        Abre la campana de la app de Ecolactea y escribe aquí el código de <strong>6 dígitos</strong>.
    </p>

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-700 px-3 py-2 text-sm">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium mb-1">Código de 6 dígitos</label>
            <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                   maxlength="6" placeholder="000000" autofocus
                   class="w-full rounded-md border-linea shadow-sm focus:border-campo focus:ring-campo tracking-widest">
        </div>

        <p class="text-xs text-piedra text-center">— o —</p>

        <div>
            <label for="recovery_code" class="block text-sm font-medium mb-1">Código de recuperación</label>
            <input id="recovery_code" type="text" name="recovery_code"
                   class="w-full rounded-md border-linea shadow-sm focus:border-campo focus:ring-campo">
            <p class="text-xs text-piedra mt-1">
                Uno de los que guardaste al activar la verificación. Sirve una sola vez.
            </p>
        </div>

        <button type="submit"
                class="w-full bg-campo text-white px-4 py-2 rounded-md text-sm hover:bg-bosque">
            Verificar
        </button>
    </form>

    {{--
        La confusión más común: el celular sin vincular muestra un código con
        letras (ABC-DEF) que no sirve para entrar, solo para vincularlo. Aquí
        se dice dónde va, para no dejar a nadie dando vueltas.
    --}}
    <div class="mt-5 border-t border-linea pt-4">
        <p class="text-xs text-piedra">
            <strong class="text-bosque">¿Tu celular te da un código con letras?</strong>
            Ese es el de vinculación: ese teléfono todavía no está ligado a tu cuenta y por eso no
            recibe el de 6 dígitos. Entra con un código de recuperación y vincúlalo desde
            Seguridad (2FA), o pídele a un administrador que lo haga.
        </p>
    </div>
@endsection
