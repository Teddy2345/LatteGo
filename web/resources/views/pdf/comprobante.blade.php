<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $documento['numero_completo'] }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #22312b; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        .gris { color: #6b7a72; }
        .cabecera { width: 100%; }
        .cabecera td { vertical-align: top; }
        .recuadro { border: 1.5px solid #22312b; border-radius: 6px; padding: 10px 14px; text-align: center; }
        .recuadro .tipo { font-size: 10px; letter-spacing: 1px; text-transform: uppercase; }
        .recuadro .numero { font-size: 15px; font-weight: bold; margin-top: 3px; }
        .datos { width: 100%; margin-top: 18px; border-top: 1px solid #d8ded9; border-bottom: 1px solid #d8ded9; padding: 8px 0; }
        .datos td { padding: 2px 0; }
        table.lineas { width: 100%; border-collapse: collapse; margin-top: 16px; }
        table.lineas th { background: #f2f5f2; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #6b7a72; padding: 6px 8px; }
        table.lineas td { padding: 7px 8px; border-bottom: 1px solid #ecefec; }
        .der { text-align: right; }
        .total { margin-top: 14px; width: 100%; }
        .total .caja { border: 1.5px solid #22312b; border-radius: 6px; padding: 8px 14px; font-size: 14px; font-weight: bold; }
        .pie { margin-top: 26px; font-size: 9px; color: #6b7a72; border-top: 1px solid #d8ded9; padding-top: 8px; }
    </style>
</head>
<body>
    <table class="cabecera">
        <tr>
            <td>
                <h1>{{ $planta['razon_social'] }}</h1>
                <p class="gris" style="margin:0">{{ $planta['direccion'] }}</p>
                @if ($planta['ruc'])
                    <p class="gris" style="margin:2px 0 0">RUC {{ $planta['ruc'] }}</p>
                @endif
                @if ($planta['telefono'])
                    <p class="gris" style="margin:2px 0 0">Tel. {{ $planta['telefono'] }}</p>
                @endif
            </td>
            <td style="width: 190px">
                <div class="recuadro">
                    <div class="tipo">{{ $tipo->etiqueta() }}</div>
                    <div class="numero">{{ $documento['numero_completo'] }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="datos">
        <tr>
            <td class="gris" style="width: 90px">Cliente</td>
            <td><strong>{{ $documento['cliente_nombre'] }}</strong></td>
            <td class="gris" style="width: 70px">Fecha</td>
            <td class="der">{{ $documento['fecha'] }}</td>
        </tr>
        <tr>
            <td class="gris">{{ $tipo->etiquetaDocumento() }}</td>
            <td>{{ $documento['cliente_documento'] ?: '—' }}</td>
            <td class="gris">Emitido</td>
            <td class="der">{{ \Illuminate\Support\Carbon::parse($documento['created_at'])->format('d/m/Y H:i') }}</td>
        </tr>
        @if ($documento['cliente_direccion'])
            <tr>
                <td class="gris">Dirección</td>
                <td colspan="3">{{ $documento['cliente_direccion'] }}</td>
            </tr>
        @endif
    </table>

    <table class="lineas">
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="der" style="width: 80px">Cantidad</th>
                <th class="der" style="width: 80px">P. unitario</th>
                <th class="der" style="width: 90px">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lineas as $linea)
                <tr>
                    <td>{{ $linea['descripcion'] }}</td>
                    <td class="der">{{ number_format($linea['cantidad'], 2) }} {{ $linea['unidad'] }}</td>
                    <td class="der">S/ {{ number_format($linea['precioUnitario'], 2) }}</td>
                    <td class="der">S/ {{ number_format($linea['importe'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="total">
        <tr>
            <td></td>
            <td style="width: 200px">
                <div class="caja">
                    <table style="width:100%">
                        <tr>
                            <td>TOTAL</td>
                            <td class="der">S/ {{ number_format((float) $documento['total'], 2) }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <p class="pie">
        Documento interno de {{ $planta['razon_social'] }} con numeración propia; no constituye comprobante
        electrónico autorizado por SUNAT. Gracias por su compra.
    </p>
</body>
</html>
