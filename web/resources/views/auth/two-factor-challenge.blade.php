@extends('layouts.guest')

@section('title', 'Verificacion en dos pasos')

@section('content')
    <h2 class="text-lg font-semibold mb-2">Verificacion en dos pasos</h2>
    <p class="text-sm text-piedra mb-4">
        Ingresa el codigo de tu aplicacion de autenticacion, o un codigo de recuperacion.
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
            <label for="code" class="block text-sm font-medium mb-1">Codigo de la app</label>
            <input id="code" type="text" name="code" inputmode="numeric" autofocus
                   class="w-full rounded-md border-linea shadow-sm focus:border-campo focus:ring-campo">
        </div>

        <p class="text-xs text-piedra text-center">— o —</p>

        <div>
            <label for="recovery_code" class="block text-sm font-medium mb-1">Codigo de recuperacion</label>
            <input id="recovery_code" type="text" name="recovery_code"
                   class="w-full rounded-md border-linea shadow-sm focus:border-campo focus:ring-campo">
        </div>

        <button type="submit"
                class="w-full bg-campo text-white px-4 py-2 rounded-md text-sm hover:bg-bosque">
            Verificar
        </button>
    </form>
@endsection
