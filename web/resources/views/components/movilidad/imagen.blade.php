@props(['tipo'])

@php
    // Ilustraciones de referencia por tipo de movilidad; un tipo nuevo cae en el camion.
    $archivo = match ($tipo) {
        'motocarga' => 'motocarga',
        'planta' => 'planta',
        default => 'camion',
    };
    $descripcion = match ($archivo) {
        'motocarga' => 'Motocarga',
        'planta' => 'Entrega directa en planta',
        default => 'Camión',
    };
@endphp

<img src="{{ asset('img/movilidades/'.$archivo.'.svg') }}" alt="{{ $descripcion }}" loading="lazy"
     {{ $attributes->merge(['class' => 'w-full object-cover']) }}>
