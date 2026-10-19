<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers\Mobile;

use App\Application\Comprobantes\UseCases\EmitirComprobanteUseCase;
use App\Application\Pedidos\DTOs\EntregarPedidoData;
use App\Application\Pedidos\DTOs\ItemPedidoData;
use App\Application\Pedidos\DTOs\PedidoData;
use App\Application\Pedidos\UseCases\EntregarPedidoUseCase;
use App\Application\Pedidos\UseCases\ListarPedidosUseCase;
use App\Domain\Pedidos\Exceptions\EstadoPedidoInvalidoException;
use App\Domain\Pedidos\ValueObjects\MetodoPago;
use App\Infrastructure\Pedidos\Models\PedidoModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Lo que el repartidor necesita en la calle: a quien le lleva, donde, cuanto
 * cobra y con que. El QR de la planta viaja con cada pedido para que al
 * llegar no haya que buscarlo: se muestra junto al monto exacto.
 */
final class RepartoController
{
    public function pendientes(Request $request, ListarPedidosUseCase $listar): JsonResponse
    {
        $usuario = $this->exigirRepartidor($request);

        $pedidos = array_values(array_filter(
            $listar->ejecutar(repartidorId: (int) $usuario->id),
            // Lo pendiente de llevar: lo entregado y lo cancelado ya no es ruta.
            static fn (PedidoData $p): bool => $p->estado === 'confirmado',
        ));

        return response()->json([
            'data' => array_map(static fn (PedidoData $p): array => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'cliente' => $p->clienteNombre,
                'telefono' => $p->clienteTelefono,
                'direccion' => $p->clienteDireccion,
                'fecha' => $p->fecha,
                'tipo_entrega' => $p->tipoEntrega,
                'observaciones' => $p->observaciones,
                'total' => round($p->total, 2),
                'items' => array_map(static fn (ItemPedidoData $i): array => [
                    'producto' => $i->nombreProducto,
                    'cantidad' => round($i->cantidad, 2),
                    'precio_unitario' => round($i->precioUnitario, 2),
                ], $p->items),
            ], $pedidos),
            'cobro' => $this->datosDeCobro(),
        ]);
    }

    public function entregar(Request $request, int $pedido, EntregarPedidoUseCase $entregar, EmitirComprobanteUseCase $emitir): JsonResponse
    {
        $usuario = $this->exigirRepartidor($request);

        $datos = $request->validate([
            'metodo_pago' => ['required', 'string', 'in:efectivo,yape,transferencia'],
            'referencia_pago' => ['nullable', 'string', 'max:40'],
            'monto_cobrado' => ['required', 'numeric', 'min:0'],
        ]);

        // Nadie entrega —ni cobra— un pedido que no le fue asignado.
        $asignado = PedidoModel::query()->findOrFail($pedido);

        abort_if($asignado->repartidor_id !== $usuario->id, 403, 'Este pedido no te fue asignado.');

        try {
            $entregado = $entregar->ejecutar(new EntregarPedidoData(
                id: $pedido,
                metodoPago: $datos['metodo_pago'],
                montoCobrado: (float) $datos['monto_cobrado'],
                fechaEntrega: now()->format('Y-m-d'),
                referenciaPago: $datos['referencia_pago'] ?? null,
            ));
        } catch (EstadoPedidoInvalidoException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // La boleta del pedido: el cliente se queda con su papel aunque la
        // entrega haya sido en la puerta de su casa.
        $comprobante = $emitir->ejecutar([
            'tipo' => 'boleta',
            'fecha' => $entregado->fechaEntrega ?? now()->format('Y-m-d'),
            'clienteNombre' => $entregado->clienteNombre,
            'clienteDocumento' => null,
            'clienteDireccion' => $entregado->clienteDireccion,
            'total' => $entregado->total,
            'pedidoId' => $entregado->id,
        ], (int) $usuario->id);

        return response()->json([
            'data' => [
                'id' => $entregado->id,
                'estado' => $entregado->estado,
                'metodo_pago' => $entregado->metodoPago,
                'monto_cobrado' => $entregado->montoCobrado,
                'comprobante' => $comprobante['numero_completo'],
                'comprobante_url' => route('api.v1.mobile.comprobantes.pdf', $comprobante['id']),
            ],
        ]);
    }

    /**
     * El QR de Yape de la planta, tal como lo subio el administrador. Si
     * todavia no hay ninguno, el telefono muestra solo el monto y se cobra
     * en efectivo o con el numero de operacion a mano.
     */
    private function datosDeCobro(): array
    {
        $ruta = (string) config('ecolactea.cobros.qr_path');
        $hay = $ruta !== '' && Storage::disk('public')->exists($ruta);

        return [
            // Se arma a mano sobre el host por el que entro el telefono, y no
            // con Storage::url(), que depende de APP_URL: la planta cambia de
            // red y el celular entra por la IP del equipo, no por localhost.
            'qr_url' => $hay ? url('storage/'.ltrim($ruta, '/')) : null,
            'metodos' => array_map(static fn (MetodoPago $m): array => [
                'valor' => $m->value,
                'etiqueta' => $m->etiqueta(),
                'exige_referencia' => $m->exigeReferencia(),
            ], [MetodoPago::Efectivo, MetodoPago::Yape]),
        ];
    }

    private function exigirRepartidor(Request $request)
    {
        $usuario = $request->user();

        abort_unless($usuario->can('pedidos.repartir'), 403, 'Esta pantalla es del personal de reparto.');

        return $usuario;
    }
}
