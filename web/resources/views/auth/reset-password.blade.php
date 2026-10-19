@extends('layouts.guest')

@section('title', 'Nueva contraseña')

@section('content')
    <h2 class="text-xl font-bold text-bosque">Crea una nueva contraseña</h2>
    <p class="text-sm text-piedra mt-1 mb-5">Elige una contraseña que no uses en otros sitios.</p>

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-700 px-3 py-2 text-sm">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="block text-sm font-medium text-bosque mb-1">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->input('email')) }}" required
                   class="w-full rounded-xl border-linea bg-white shadow-sm text-sm focus:border-campo focus:ring-campo">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-bosque mb-1">Nueva contraseña</label>
            <input id="password" type="password" name="password" required autofocus autocomplete="new-password"
                   class="w-full rounded-xl border-linea bg-white shadow-sm text-sm focus:border-campo focus:ring-campo">
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-bosque mb-1">Repite la contraseña</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                   class="w-full rounded-xl border-linea bg-white shadow-sm text-sm focus:border-campo focus:ring-campo">
        </div>

        <button type="submit"
                class="w-full bg-campo text-white px-4 py-3 rounded-xl text-sm font-semibold hover:bg-bosque transition-colors">
            Guardar contraseña
        </button>
    </form>
@endsection
