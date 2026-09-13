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

            <div class="border-t border-linea pt-4">
                <p class="text-sm font-medium">Recibir el código en tu celular</p>
                <p class="text-xs text-piedra mt-1 mb-3">
                    Vincula tu teléfono una vez y el código de 6 dígitos te llegará a la campana
                    de la app Ecolactea, sin necesidad de instalar otra aplicación.
                    <strong>Solo el teléfono que vincules</strong> podrá verlo.
                </p>

                @forelse ($telefonosVinculados as $telefono)
                    <div class="flex items-center justify-between gap-3 bg-crema rounded-md px-3 py-2 mb-2 text-sm">
                        <div>
                            <span class="text-bosque font-medium">{{ $telefono->nombre }}</span>
                            <span class="text-piedra text-xs block">
                                Vinculado el {{ $telefono->vinculado_en?->format('Y-m-d H:i') }}
                            </span>
                        </div>
                        <button type="button" wire:click="desvincularTelefono({{ $telefono->id }})"
                                wire:confirm="Este teléfono dejará de recibir códigos. ¿Continuar?"
                                class="text-xs text-red-700 hover:underline shrink-0">Desvincular</button>
                    </div>
                @empty
                    <p class="text-xs text-piedra mb-3">Todavía no vinculaste ningún teléfono.</p>
                @endforelse

                @if ($codigoVinculacion !== null)
                    <div class="bg-salvia border border-linea rounded-md px-4 py-3">
                        <p class="text-xs text-bosque mb-1">
                            Escribe este código en la app, en Perfil → Verificación en dos pasos.
                            Vence en 10 minutos.
                        </p>
                        <p class="font-mono text-2xl tracking-widest text-bosque">{{ $codigoVinculacion }}</p>
                    </div>
                @else
                    <button type="button" wire:click="generarCodigoVinculacion"
                            class="border border-campo text-campo px-4 py-2 rounded-md text-sm hover:bg-salvia">
                        Vincular mi celular
                    </button>
                @endif
            </div>

            <a href="{{ route('dashboard') }}" wire:navigate
               class="inline-block bg-campo text-white px-4 py-2 rounded-md text-sm hover:bg-bosque">
                Continuar
            </a>
        </div>
    @elseif ($pendienteConfirmar)
        <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <p class="text-sm">Escanea este codigo QR con tu aplicacion de autenticacion (Google Authenticator, Authy, etc.):</p>

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
