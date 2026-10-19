<?php

declare(strict_types=1);

namespace App\Application\Pedidos\UseCases;

use App\Application\Notificacion\DTOs\RegistrarNotificacionData;
use App\Application\Notificacion\UseCases\RegistrarNotificacionUseCase;
use App\Application\Pedidos\DTOs\CrearPedidoData;
use App\Application\Pedidos\DTOs\ItemCarritoData;
use App\Application\Pedidos\DTOs\PedidoData;
use App\Domain\Inventario\ValueObjects\TipoCliente;
use App\Domain\Notificacion\ValueObjects\NivelNotificacion;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\ValueObjects\TipoEntrega;
use App\Domain\Proveedor\Exceptions\CuentaProveedorException;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * El pedido que hace el proveedor desde la app.
 *
 * Se cobra a su precio —que sale del catalogo, nunca del telefono— y al
 * guardarse le llega a su campana el codigo del comprobante: es lo que
 * enseña al repartidor o en planta al recoger, y con el la planta encuentra
 * el pedido sin preguntarle el nombre.
 */
final class CrearPedidoDeProveedorUseCase
{
    public function __construct(
        private readonly CrearPedidoUseCase $crear,
        private readonly PedidoRepository $pedidos,
        private readonly RegistrarNotificacionUseCase $notificar,
    ) {
    }

    /**
     * @param  ItemCarritoData[]  $items
     */
    public function ejecutar(
        User $usuario,
        array $items,
        TipoEntrega $tipoEntrega,
        string $direccion,
        ?string $observaciones = null,
    ): PedidoData {
        $proveedor = ProveedorModel::query()->where('user_id', $usuario->id)->first();

        if ($proveedor === null) {
            throw new CuentaProveedorException('Esta cuenta no está ligada a un proveedor de la planta.');
        }

        if (! $proveedor->activo) {
            throw new CuentaProveedorException('Tu ficha de proveedor está dada de baja. Acércate a la planta.');
        }

        // Quien recoge en planta no necesita dar direccion.
        $entregaEn = $tipoEntrega === TipoEntrega::Recojo
            ? 'Recoge en planta'
            : trim($direccion);

        if ($entregaEn === '') {
            throw new CuentaProveedorException('Indica dónde quieres que te lo dejen.');
        }

        $pedido = DB::transaction(fn (): PedidoData => $this->crear->ejecutar(new CrearPedidoData(
            clienteNombre: $proveedor->nombre,
            clienteTelefono: (string) ($proveedor->telefono ?? ''),
            clienteDireccion: $entregaEn,
            fecha: now()->format('Y-m-d'),
            observaciones: $observaciones,
            items: $items,
            usuarioId: (int) $usuario->id,
            tipoEntrega: $tipoEntrega,
            tipoCliente: TipoCliente::Proveedor,
        )));

        $this->avisarDelCodigo($usuario, $pedido, $tipoEntrega);

        return $pedido;
    }

    private function avisarDelCodigo(User $usuario, PedidoData $pedido, TipoEntrega $tipoEntrega): void
    {
        $comoLoRecibe = $tipoEntrega === TipoEntrega::Recojo
            ? 'Menciónalo en planta al recogerlo.'
            : 'Menciónaselo al repartidor cuando te lo entregue.';

        // El numero identifica el pedido en la entrega; no es un cupon: el
        // proveedor ya compro a su precio, que es el unico que ve en la tienda.
        $this->notificar->ejecutar(new RegistrarNotificacionData(
            tipo: 'pedido_proveedor',
            titulo: 'Pedido '.$pedido->codigo,
            mensaje: sprintf(
                'Registrado por S/ %s. %s',
                number_format($pedido->total, 2),
                $comoLoRecibe,
            ),
            nivel: NivelNotificacion::Info,
            datos: [
                'pedido_id' => $pedido->id,
                'codigo' => $pedido->codigo,
                'total' => $pedido->total,
                'tipo_entrega' => $tipoEntrega->value,
            ],
            usuarioId: (int) $usuario->id,
        ));
    }
}
