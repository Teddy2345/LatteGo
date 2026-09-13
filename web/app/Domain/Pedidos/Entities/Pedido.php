<?php

declare(strict_types=1);

namespace App\Domain\Pedidos\Entities;

use App\Domain\Pedidos\Exceptions\CarritoVacioException;
use App\Domain\Pedidos\Exceptions\EstadoPedidoInvalidoException;
use App\Domain\Pedidos\Exceptions\PedidoYaRevisadoException;
use App\Domain\Pedidos\ValueObjects\EstadoPedido;
use App\Domain\Pedidos\ValueObjects\MetodoPago;
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
    ): self {
        return new self($id, $clienteNombre, $clienteTelefono, $clienteDireccion, $fecha, $estado, $observaciones, $items, $repartidorId, $metodoPago, $montoCobrado, $fechaEntrega);
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

    public function cancelar(): self
    {
        $this->exigirPendiente();

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
        );
    }

    /**
     * Marca el pedido como entregado y deja constancia de como se cobro.
     * Requiere un repartidor ya asignado: es quien reparte el que cobra y
     * confirma la entrega en el momento.
     */
    public function entregar(MetodoPago $metodoPago, float $montoCobrado, DateTimeImmutable $fechaEntrega): self
    {
        if ($this->estado !== EstadoPedido::Confirmado) {
            throw new EstadoPedidoInvalidoException('Solo se puede entregar un pedido confirmado.');
        }

        if ($this->repartidorId === null) {
            throw new EstadoPedidoInvalidoException('Asigna un repartidor antes de registrar la entrega.');
        }

        if (! is_finite($montoCobrado) || $montoCobrado <= 0) {
            throw new EstadoPedidoInvalidoException('El monto cobrado debe ser mayor a 0.');
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
        );
    }

    private function exigirPendiente(): void
    {
        if ($this->estado !== EstadoPedido::Pendiente) {
            throw new PedidoYaRevisadoException('Este pedido ya fue revisado.');
        }
    }
}
