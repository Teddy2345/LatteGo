<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ficha de producción #{{ $ficha['id'] }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #22312b; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        h2 { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #6b7a72; margin: 20px 0 6px; }
        h3 { font-size: 12px; margin: 0 0 4px; }
        .gris { color: #6b7a72; }
        .cabecera { width: 100%; }
        .cabecera td { vertical-align: top; }
        .recuadro { border: 1.5px solid #22312b; border-radius: 6px; padding: 10px 14px; text-align: center; }
        .recuadro .tipo { font-size: 10px; letter-spacing: 1px; text-transform: uppercase; }
        .recuadro .numero { font-size: 15px; font-weight: bold; margin-top: 3px; }
        .datos { width: 100%; margin-top: 18px; border-top: 1px solid #d8ded9; border-bottom: 1px solid #d8ded9; padding: 8px 0; }
        .datos td { padding: 2px 0; }
        table.lineas { width: 100%; border-collapse: collapse; }
        table.lineas th { background: #f2f5f2; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #6b7a72; padding: 6px 8px; }
        table.lineas td { padding: 6px 8px; border-bottom: 1px solid #ecefec; }
        .der { text-align: right; }
        .tarjeta { border: 1px solid #d8ded9; border-radius: 6px; padding: 10px 12px; margin-bottom: 10px; }
        .pie { margin-top: 26px; font-size: 9px; color: #6b7a72; border-top: 1px solid #d8ded9; padding-top: 8px; }
        .firma { margin-top: 40px; width: 100%; }
        .firma td { text-align: center; font-size: 9px; color: #6b7a72; padding-top: 4px; }
        .linea-firma { border-top: 1px solid #22312b; width: 170px; margin: 0 auto; }
    </style>
</head>
<body>
    <table class="cabecera">
        <tr>
            <td>
                <h1>{{ $planta['razon_social'] }}</h1>
                <p class="gris" style="margin:0">{{ $planta['direccion'] }}</p>
                <p class="gris" style="margin:2px 0 0">Ficha de producción</p>
            </td>
            <td style="width: 170px">
                <div class="recuadro">
                    <div class="tipo">Lote</div>
                    <div class="numero">#{{ $ficha['id'] }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="datos">
        <tr>
            <td class="gris" style="width: 110px">Fecha</td>
            <td>{{ $ficha['fecha'] }}</td>
            <td class="gris" style="width: 110px">Leche procesada</td>
            <td class="der">{{ number_format($ficha['litrosProcesados'], 2) }} L</td>
        </tr>
        <tr>
            <td class="gris">Responsable</td>
            <td colspan="3">{{ $ficha['responsable'] ?? '—' }}</td>
        </tr>
    </table>

    <h2>Productos obtenidos y su receta</h2>

    @foreach ($ficha['productos'] as $producto)
        <div class="tarjeta">
            <h3>{{ $producto['producto'] }}</h3>
            <p class="gris" style="margin:0 0 6px">
                Planificado {{ number_format((float) $producto['cantidad_planificada'], 2) }} ·
                obtenido <strong>{{ number_format((float) $producto['cantidad_producida'], 2) }} {{ $producto['unidad'] }}</strong>
                @if ($producto['receta'])
                    · receta: {{ $producto['receta'] }}
                    @if ($producto['receta_cantidad_base'])
                        (base {{ number_format((float) $producto['receta_cantidad_base'], 2) }} {{ $producto['unidad'] }})
                    @endif
                @else
                    · registro manual, sin receta
                @endif
            </p>

            @if ($producto['receta_descripcion'])
                <p style="margin:0 0 8px">{{ $producto['receta_descripcion'] }}</p>
            @endif

            @if ($producto['insumos'])
                <table class="lineas">
                    <thead>
                        <tr>
                            <th>Insumo de la receta</th>
                            <th class="der" style="width: 130px">Cantidad por base</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($producto['insumos'] as $insumo)
                            <tr>
                                <td>{{ $insumo['nombre'] }}</td>
                                <td class="der">{{ number_format((float) $insumo['cantidad'], 2) }} {{ $insumo['unidad'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach

    <h2>Insumos realmente consumidos en el lote</h2>

    @if ($ficha['consumos'])
        <table class="lineas">
            <thead>
                <tr>
                    <th>Insumo</th>
                    <th class="der" style="width: 130px">Cantidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ficha['consumos'] as $consumo)
                    <tr>
                        <td>{{ $consumo['nombre'] }}</td>
                        <td class="der">{{ number_format((float) $consumo['cantidad'], 2) }} {{ $consumo['unidad'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="gris">Este lote no consumió insumos de almacén.</p>
    @endif

    @if ($ficha['observaciones'])
        <h2>Observaciones</h2>
        <p style="margin:0">{{ $ficha['observaciones'] }}</p>
    @endif

    <table class="firma">
        <tr>
            <td><div class="linea-firma"></div>Responsable de producción</td>
            <td><div class="linea-firma"></div>Control de calidad</td>
        </tr>
    </table>

    <p class="pie">
        Emitido el {{ now()->format('d/m/Y H:i') }}. Las cantidades consumidas son las que se descontaron de almacén al registrar el lote.
    </p>
</body>
</html>
