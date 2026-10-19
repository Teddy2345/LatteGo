<div class="max-w-lg">
    <h1 class="text-2xl font-semibold mb-2">Verificacion en dos pasos</h1>
    <p class="text-sm text-piedra mb-6">
        Tu rol requiere 2FA activado para poder usar el sistema.
    </p>

    @if ($confirmado)
        <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <div class="rounded-md bg-salvia border border-linea text-bosque px-4 py-3 text-sm">
                2FA activado correctamente.
            </div>

            @if ($aviso !== '')
                <div class="rounded-md bg-amber-50 border border-amber-200 text-amber-800 px-3 py-2 text-sm">
                    {{ $aviso }}
                </div>
            @endif

            <div id="codigos-respaldo">
                <p class="text-sm font-medium">Códigos de respaldo</p>
                <p class="text-xs text-piedra mt-1 mb-2">
                    Sirven para entrar cuando no tengas a mano la aplicación de autenticación.
                    Cada uno funciona <strong>una sola vez</strong>: imprímelos o guárdalos en un lugar seguro.
                </p>

                @if (count($recoveryCodes) > 0)
                    <div class="bg-crema rounded-md p-3 font-mono text-xs grid grid-cols-2 gap-1">
                        @foreach ($recoveryCodes as $codigo)
                            <div>{{ $codigo }}</div>
                        @endforeach
                    </div>
                    <div class="flex flex-wrap gap-3 mt-3">
                        <button type="button" onclick="window.print()"
                                class="text-sm text-campo hover:underline">Imprimir</button>
                        <button type="button" wire:click="regenerarCodigos"
                                wire:confirm="Se anularán los códigos actuales y se generarán otros. ¿Continuar?"
                                class="text-sm text-piedra hover:underline">Generar códigos nuevos</button>
                    </div>
                @else
                    <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
                        Esta cuenta todavía no tiene códigos de respaldo.
                    </p>
                    <button type="button" wire:click="regenerarCodigos"
                            class="mt-3 bg-campo text-white px-4 py-2 rounded-md text-sm hover:bg-bosque">
                        Generar códigos de respaldo
                    </button>
                @endif
            </div>

            @include('livewire.auth.partials.vincular-telefono')

            <a href="{{ route('dashboard') }}" wire:navigate
               class="inline-block bg-campo text-white px-4 py-2 rounded-md text-sm hover:bg-bosque">
                Continuar
            </a>
        </div>
    @elseif ($pendienteConfirmar)
        <div class="bg-white shadow rounded-lg p-6 space-y-4">
            @if ($aviso !== '')
                <div class="rounded-md bg-salvia border border-linea text-bosque px-3 py-2 text-sm">
                    {{ $aviso }}
                </div>
            @endif

            <div class="rounded-md bg-crema border border-linea px-4 py-3">
                <p class="text-sm font-medium text-bosque">Con tu celular (recomendado)</p>
                <p class="text-xs text-piedra mt-1">
                    Vincula la app Ecolactea abajo y escribe en «Código de 6 dígitos» el que te muestra su campana.
                    No necesitas instalar otra aplicación.
                </p>
                @include('livewire.auth.partials.vincular-telefono')
            </div>

            <p class="text-sm">O escanea este codigo QR con una aplicacion de autenticacion (Google Authenticator, Authy, etc.):</p>

            <div class="border border-linea rounded-md p-4 inline-block bg-white">
                {!! $qrCode !!}
            </div>

            <div>
                <p class="text-sm font-medium">¿No puedes escanear?</p>
                <p class="text-xs text-piedra mt-1 mb-2">
                    Elige «introducir clave manualmente» en tu aplicación y escribe esta clave.
                    No necesitas cámara.
                </p>
                <div class="bg-crema rounded-md px-3 py-2 font-mono text-sm tracking-wider break-all">
                    {{ $claveManual }}
                </div>
            </div>

            @if ($error !== '')
                <div class="rounded-md bg-red-50 border border-red-200 text-red-700 px-3 py-2 text-sm">
                    {{ $error }}
                </div>
            @endif

            <form wire:submit="confirmar" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium mb-1">Codigo de 6 digitos</label>
                    <input type="text" wire:model="code" inputmode="numeric"
                           class="w-full rounded-md border-linea shadow-sm focus:border-campo focus:ring-campo">
                </div>
                <button type="submit"
                        class="bg-campo text-white px-4 py-2 rounded-md text-sm hover:bg-bosque">
                    Confirmar
                </button>
            </form>
        </div>
    @else
        <div class="bg-white shadow rounded-lg p-6">
            <button type="button" wire:click="habilitar"
                    class="bg-campo text-white px-4 py-2 rounded-md text-sm hover:bg-bosque">
                Habilitar 2FA
            </button>
        </div>
    @endif
</div>
