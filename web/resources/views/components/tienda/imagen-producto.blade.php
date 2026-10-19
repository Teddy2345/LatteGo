@props(['producto'])

@php
    // Si el almacen subio una foto real, se usa esa. Si no, una ilustracion
    // de referencia segun el tipo de producto, para que la tienda no muestre
    // una caja generica en cada tarjeta.
    if ($producto->fotoPath) {
        $src = Illuminate\Support\Facades\Storage::url($producto->fotoPath);
    } else {
        $nombre = mb_strtolower($producto->nombre);

        $ilustracion = match (true) {
            str_contains($nombre, 'madurad') => 'queso-madurado',
            str_contains($nombre, 'queso') => 'queso-fresco',
            str_contains($nombre, 'yogur') && (str_contains($nombre, 'frutilla') || str_contains($nombre, 'fresa')) => 'yogurt-frutilla',
            str_contains($nombre, 'yogur') => 'yogurt-natural',
            str_contains($nombre, 'mantequilla') => 'mantequilla',
            str_contains($nombre, 'leche') => 'leche',
            default => 'lacteo',
        };

        $src = asset("img/productos/{$ilustracion}.svg");
    }
@endphp

<img src="{{ $src }}" alt="{{ $producto->nombre }}" loading="lazy" {{ $attributes->merge(['class' => 'h-full w-full object-cover']) }}>
