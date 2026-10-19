@extends('layouts.guest')

@section('title', 'Recuperar contraseña')

@section('content')
    <h2 class="text-xl font-bold text-bosque">¿Olvidaste tu contraseña?</h2>
    <p class="text-sm text-piedra mt-1 mb-5">
        Escribe el correo de tu cuenta y te enviaremos un enlace para crear una nueva.
    </p>

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-salvia border border-linea text-bosque px-3 py-2 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-700 px-3 py-2 text-sm">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-bosque mb-1">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full rounded-xl border-linea bg-white shadow-sm text-sm focus:border-campo focus:ring-campo">
        </div>

        <button type="submit"
                class="w-full bg-campo text-white px-4 py-3 rounded-xl text-sm font-semibold hover:bg-bosque transition-colors">
            Enviar enlace
        </button>
    </form>

    <p class="mt-5 text-center text-sm">
        <a href="{{ route('login') }}" class="text-campo font-medium hover:underline">Volver a iniciar sesión</a>
    </p>
@endsection
