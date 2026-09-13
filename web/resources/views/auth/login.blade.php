@extends('layouts.guest')

@section('title', 'Iniciar sesion')

@section('content')
    <h2 class="text-xl font-bold text-bosque">Bienvenido de nuevo</h2>
    <p class="text-sm text-piedra mt-1 mb-5">Ingresa a tu cuenta para continuar.</p>

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

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-bosque mb-1">Correo electronico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full rounded-xl border-linea bg-white shadow-sm text-sm focus:border-campo focus:ring-campo">
        </div>

        <div x-data="{ visible: false }">
            <label for="password" class="block text-sm font-medium text-bosque mb-1">Contrasena</label>
            <div class="relative">
                <input :type="visible ? 'text' : 'password'" id="password" name="password" required
                       class="w-full rounded-xl border-linea bg-white shadow-sm text-sm focus:border-campo focus:ring-campo pr-11">
                <button type="button" @click="visible = !visible"
                        :aria-label="visible ? 'Ocultar contrasena' : 'Mostrar contrasena'"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-piedra hover:text-bosque">
                    <svg x-show="!visible" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    <svg x-show="visible" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                </button>
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-piedra">
            <input type="checkbox" name="remember" class="rounded border-linea text-campo focus:ring-campo">
            Recordarme
        </label>

        <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 bg-campo text-white px-4 py-3 rounded-xl text-sm font-semibold hover:bg-bosque transition-colors">
            Ingresar
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" /></svg>
        </button>
    </form>
@endsection
