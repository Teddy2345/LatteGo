<div class="max-w-xl mx-auto px-6 py-10 print:px-0 print:py-0">
    <div class="flex items-start justify-between mb-8">
        <div>
            <h1 class="text-xl font-bold">{{ config('app.name') }}</h1>
            <p class="text-sm text-piedra">Comprobante de pago</p>
        </div>
        <button onclick="window.print()" class="print:hidden bg-campo text-white px-4 py-2 rounded-md text-sm hover:bg-bosque">
            Imprimir
        </button>
    </div>

    <dl class="grid grid-cols-2 gap-y-3 text-sm border-t border-b border-linea py-4 mb-4">
        <dt class="text-piedra">Proveedor</dt>
        <dd class="text-right font-medium">{{ $proveedor->nombre }}</dd>

        <dt class="text-piedra">Cedula</dt>
        <dd class="text-right">{{ $proveedor->cedula }}</dd>

        <dt class="text-piedra">Semana de acopio</dt>
        <dd class="text-right">{{ $pago->semanaInicio }} &rarr; {{ $pago->semanaFin }}</dd>

        <dt class="text-piedra">Total litros</dt>
        <dd class="text-right">{{ number_format($pago->totalLitros, 2) }} L</dd>

        <dt class="text-piedra">Precio por litro</dt>
        <dd class="text-right">S/ {{ number_format($pago->precioLitro, 2) }}</dd>

        <dt class="text-piedra">Estado</dt>
        <dd class="text-right">{{ $pago->estado === 'pagado' ? 'Pagado' : 'Pendiente' }}</dd>

        @if ($pago->fechaPago)
            <dt class="text-piedra">Fecha de pago</dt>
            <dd class="text-right">{{ $pago->fechaPago }}</dd>
        @endif
    </dl>

    <div class="flex items-center justify-between text-lg font-bold">
        <span>Total a pagar</span>
        <span>S/ {{ number_format($pago->totalPagar, 2) }}</span>
    </div>

    <p class="text-xs text-piedra mt-8">Comprobante #{{ $pago->id }} — generado el {{ now()->format('Y-m-d H:i') }}</p>
</div>
