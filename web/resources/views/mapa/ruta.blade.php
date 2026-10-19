<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recorrido de {{ $movilidad }}</title>
    {{-- Pagina minima para el mapa dentro de la app: Google solo sirve su
         embed dentro de un iframe de una pagina servida por un sitio.
         El iframe va fijo al viewport porque dentro del WebView del telefono
         un alto en porcentaje se queda en cero y el mapa no se ve. --}}
    <style>
        html, body { margin: 0; height: 100%; background: #F7F7F0; }
        iframe { border: 0; position: fixed; inset: 0; width: 100%; height: 100%; }
    </style>
</head>
<body>
    <iframe src="{{ $mapa }}" referrerpolicy="no-referrer-when-downgrade" title="Recorrido de {{ $movilidad }}"></iframe>
</body>
</html>
