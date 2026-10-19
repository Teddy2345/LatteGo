@props(['movilidad', 'disposicion' => 'columna'])

{{--
    El item de una movilidad: su ilustracion, su nombre y el recorrido que
    atiende. Lo comparten Acopios —donde se elige una para ver su ruta en el
    mapa— y Movilidades, donde el acopiador abre la suya para registrar. Asi
    el camion se nombra igual en las dos pantallas.

    `disposicion`: "columna" pone la imagen arriba (rejilla de Acopios) y
    "fila" la pone al lado del texto (tarjeta ancha de Movilidades). El slot
    queda para lo propio de cada pantalla: el avance del recorrido o el
    numero de proveedores.
--}}

@php
    $enFila = $disposicion === 'fila';

    $recorrido = $movilidad->tipo === 'planta'
        ? 'Recepción en planta'
        : 'Ruta '.($movilidad->rutaNombre ?? 'sin asignar');
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-linea shadow-sm overflow-hidden']) }}>
    <div class="{{ $enFila ? 'flex items-center gap-4 p-5' : '' }}">
        <div class="overflow-hidden {{ $enFila ? 'w-24 shrink-0 rounded-xl border border-linea' : '' }}">
            {{-- La rejilla de Acopios acerca la ilustracion al pasar por encima; la
                 tarjeta ancha de Movilidades se queda quieta, como estaba. --}}
            <x-movilidad.imagen :tipo="$movilidad->tipo"
                                class="aspect-[8/5] {{ $enFila ? '' : 'transition-transform duration-300 group-hover:scale-105' }}" />
        </div>

        <div class="{{ $enFila ? 'min-w-0 flex-1' : 'p-3' }}">
            <p class="font-semibold text-bosque leading-tight {{ $enFila ? 'text-lg' : '' }}">{{ $movilidad->nombre }}</p>
            <p class="{{ $enFila ? 'text-sm' : 'text-xs mt-0.5' }} text-piedra">{{ $recorrido }}</p>
        </div>
    </div>

    @if (trim($slot) !== '')
        <div class="{{ $enFila ? 'px-5 pb-5' : 'px-3 pb-3' }}">{{ $slot }}</div>
    @endif
</div>
