@props(['estado'])

@php
    $estilos = match ($estado) {
        'confirmado' => 'bg-salvia text-bosque',
        'entregado' => 'bg-campo text-white',
        'cancelado' => 'bg-red-50 text-red-700',
        default => 'bg-amber-100 text-amber-800',
    };
    $etiquetas = ['pendiente' => 'Pendiente', 'confirmado' => 'Confirmado', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'];
@endphp

<span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $estilos }}">{{ $etiquetas[$estado] ?? $estado }}</span>
