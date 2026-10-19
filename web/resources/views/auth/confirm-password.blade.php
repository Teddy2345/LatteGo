@extends('layouts.guest')

@section('title', 'Confirmar contraseña')

@section('content')
    <h2 class="text-xl font-bold text-bosque">Confirma tu contraseña</h2>
    <p class="text-sm text-piedra mt-1 mb-5">Por seguridad, vuelve a escribir tu contraseña para continuar.</p>

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-700 px-3 py-2 text-sm">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <div>
            <label for="password" class="block text-sm font-medium text-bosque mb-1">Contraseña</label>
            <input id="password" type="password" name="password" required autofocus autocomplete="current-password"
                   class="w-full rounded-xl border-linea bg-white shadow-sm text-sm focus:border-campo focus:ring-campo">
        </div>

        <button type="submit"
                class="w-full bg-campo text-white px-4 py-3 rounded-xl text-sm font-semibold hover:bg-bosque transition-colors">
            Confirmar
        </button>
    </form>
@endsection
