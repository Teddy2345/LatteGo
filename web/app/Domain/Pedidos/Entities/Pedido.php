<?php

declare(strict_types=1);

namespace App\Domain\Pedidos\Entities;

use App\Domain\Inventario\ValueObjects\TipoCliente;
use App\Domain\Pedidos\Exceptions\CarritoVacioException;
use App\Domain\Pedidos\Exceptions\EstadoPedidoInvalidoException;
use App\Domain\Pedidos\Exceptions\PedidoYaRevisadoException;
use App\Domain\Pedidos\ValueObjects\EstadoPedido;
use App\Domain\Pedidos\ValueObjects\MetodoPago;
use App\Domain\Pedidos\ValueObjects\TipoEntrega;
use DateTimeImmutable;

/**
 * Un pedido hecho desde la tienda web publica. Nace Pendiente y solo queda
 * como registro de lo que el cliente quiere comprar. Ventas lo confirma o
 * cancela, le asigna un repartidor, y quien reparte lo marca como
 * entregado al momento de cobrar (no hay pasarela de pago: solo queda
 * constancia de como se cobro).
 */
final class Pedido
{
    private function __construct(
        public readonly ?int $id,
        public readonly string $clienteNombre,
        public readonly string $clienteTelefono,
        public readonly string $clienteDireccion,
        public readonly DateTimeImmutable $fecha,
        public readonly EstadoPedido $estado,
        public readonly ?string $observaciones,
        /** @var ItemPedido[] */
        public readonly array $items,
        public readonly ?int $repartidorId,
        public readonly ?MetodoPago $metodoPago,
        public readonly ?float $montoCobrado,
        public readonly ?DateTimeImmutable $fechaEntrega,
        public readonly ?int $usuarioId = null,
        public readonly ?string $codigo = null,
        public readonly ?TipoEntrega $tipoEntrega = null,
        public readonly TipoCliente $tipoCliente = TipoCliente::Externo,
        public readonly ?string $referenciaPago = null,
    ) {
    }

    /**
     * @param  ItemPedido[]  $items
     */
    public static function crear(
        string $clienteNombre,
        string $clienteTelefono,
        string $clienteDireccion,
        DateTimeImmutable $fecha,
        ?string $observaciones,
        array $items,
        ?int $usuarioId = null,
        ?TipoEntrega $tipoEntrega = null,
        TipoCliente $tipoCliente = TipoCliente::Externo,
    ): self {
        if ($items === []) {
            throw new CarritoVacioException('El carrito esta vacio.');
        }

        return new self(
            id: null,
            clienteNombre: $clienteNombre,
            clienteTelefono: $clienteTelefono,
            clienteDireccion: $clienteDireccion,
            fecha: $fecha,
            estado: EstadoPedido::Pendiente,
            observaciones: $observaciones,
            items: $items,
            repartidorId: null,
            metodoPago: null,
            montoCobrado: null,
            fechaEntrega: null,
            usuarioId: $usuarioId,
            // Corto y sin letras confundibles: se dicta al repartidor.
            codigo: $usuarioId === null ? null : self::codigoNuevo(),
            tipoEntrega: $tipoEntrega,
            tipoCliente: $tipoCliente,
        );
    }

    /**
     * @param  ItemPedido[]  $items
     */
    public static function reconstituir(
        int $id,
        string $clienteNombre,
        string $clienteTelefono,
        string $clienteDireccion,
        DateTimeImmutable $fecha,
        EstadoPedido $estado,
        ?string $observaciones,
        array $items,
        ?int $repartidorId = null,
        ?MetodoPago $metodoPago = null,
        ?float $montoCobrado = null,
        ?DateTimeImmutable $fechaEntrega = null,
        ?int $usuarioId = null,
        ?string $codigo = null,
        ?TipoEntrega $tipoEntrega = null,
        TipoCliente $tipoCliente = TipoCliente::Externo,
        ?string $referenciaPago = null,
    ): self {
        return new self($id, $clienteNombre, $clienteTelefono, $clienteDireccion, $fecha, $estado, $observaciones, $items, $repartidorId, $metodoPago, $montoCobrado, $fechaEntrega, $usuarioId, $codigo, $tipoEntrega, $tipoCliente, $referenciaPago);
    }

    /**
     * Codigo corto del comprobante: se dicta al repartidor o se muestra en
     * planta al recoger. Sin 0/O ni 1/I/L para no confundir al leerlo.
     */
    private static function codigoNuevo(): string
    {
        $alfabeto = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $codigo = '';

        for ($i = 0; $i < 6; $i++) {
            $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }

        return $codigo;
    }

    public function total(): float
    {
        return round(array_sum(array_map(
            static fn (ItemPedido $item): float => $item->subtotal(),
            $this->items,
        )), 2);
    }

    public function confirmar(): self
    {
        $this->exigirPendiente();

        return $this->conEstado(EstadoPedido::Confirmado);
    }

    /**
     * Se cancela mientras no se haya entregado. Tambien un confirmado: el
     * cliente puede arrepentirse o no estar al llegar el repartidor, y como
     * confirmar reserva mercaderia, sin esto quedaria bloqueada para siempre.
     */
    public function cancelar(): self
    {
        if (! in_array($this->estado, [EstadoPedido::Pendiente, EstadoPedido::Confirmado], true)) {
            throw new PedidoYaRevisadoException('Solo se puede cancelar un pedido pendiente o confirmado.');
        }

        return $this->conEstado(EstadoPedido::Cancelado);
    }

    /**
     * Asigna quien va a repartir el pedido. Solo tiene sentido una vez que
     * Ventas ya confirmo el pedido; se puede reasignar mientras siga
     * confirmado (sin entregar todavia).
     */
    public function asignarRepartidor(int $repartidorId): self
    {
        if ($this->estado !== EstadoPedido::Confirmado) {
            throw new EstadoPedidoInvalidoException('Solo se puede asignar reparto a un pedido confirmado.');
        }

        return new self(
            $this->id,
            $this->clienteNombre,
            $this->clienteTelefono,
            $this->clienteDireccion,
            $this->fecha,
            $this->estado,
            $this->observaciones,
            $this->items,
            $repartidorId,
            $this->metodoPago,
            $this->montoCobrado,
            $this->fechaEntrega,
            $this->usuarioId,
            $this->codigo,
            $this->tipoEntrega,
            $this->tipoCliente,
            $this->referenciaPago,
        );
    }

    /**
     * Marca el pedido como entregado y deja constancia de como se cobro.
     * Requiere un repartidor ya asignado: es quien reparte el que cobra y
     * confirma la entrega en el momento.
     */
    public function entregar(
        MetodoPago $metodoPago,
        float $montoCobrado,
        DateTimeImmutable $fechaEntrega,
        ?string $referenciaPago = null,
    ): self {
        if ($this->estado !== EstadoPedido::Confirmado) {
            throw new EstadoPedidoInvalidoException('Solo se puede entregar un pedido confirmado.');
        }

        if ($this->repartidorId === null) {
            throw new EstadoPedidoInvalidoException('Asigna un repartidor antes de registrar la entrega.');
        }

        if (! is_finite($montoCobrado) || $montoCobrado <= 0) {
            throw new EstadoPedidoInvalidoException('El monto cobrado debe ser mayor a 0.');
        }

        $referencia = $referenciaPago === null ? null : trim($referenciaPago);

        // Sin el numero de operacion el cobro no se puede cotejar despues.
        if ($metodoPago->exigeReferencia() && ($referencia === null || $referencia === '')) {
            throw new EstadoPedidoInvalidoException(sprintf(
                'Anota el número de operación del pago por %s.',
                $metodoPago->etiqueta(),
            ));
        }

        return new self(
            $this->id,
            $this->clienteNombre,
            $this->clienteTelefono,
            $this->clienteDireccion,
            $this->fecha,
            EstadoPedido::Entregado,
            $this->observaciones,
            $this->items,
            $this->repartidorId,
            $metodoPago,
            round($montoCobrado, 2),
            $fechaEntrega,
            $this->usuarioId,
            $this->codigo,
            $this->tipoEntrega,
            $this->tipoCliente,
            // El efectivo no deja referencia aunque llegue una por error.
            $metodoPago->exigeReferencia() ? $referencia : null,
        );
    }

    private function conEstado(EstadoPedido $estado): self
    {
        return new self(
            $this->id,
            $this->clienteNombre,
            $this->clienteTelefono,
            $this->clienteDireccion,
            $this->fecha,
            $estado,
            $this->observaciones,
            $this->items,
            $this->repartidorId,
            $this->metodoPago,
            $this->montoCobrado,
            $this->fechaEntrega,
            $this->usuarioId,
            $this->codigo,
            $this->tipoEntrega,
            $this->tipoCliente,
            $this->referenciaPago,
        );
    }

    private function exigirPendiente(): void
    {
        if ($this->estado !== EstadoPedido::Pendiente) {
            throw new PedidoYaRevisadoException('Este pedido ya fue revisado.');
        }
    }
}
