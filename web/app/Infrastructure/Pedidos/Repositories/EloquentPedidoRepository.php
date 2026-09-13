<?php

declare(strict_types=1);

namespace App\Infrastructure\Pedidos\Repositories;

use App\Domain\Pedidos\Entities\ItemPedido;
use App\Domain\Pedidos\Entities\Pedido;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\ValueObjects\EstadoPedido;
use App\Domain\Pedidos\ValueObjects\MetodoPago;
use App\Infrastructure\Pedidos\Models\ItemPedidoModel;
use App\Infrastructure\Pedidos\Models\PedidoModel;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;

final class EloquentPedidoRepository implements PedidoRepository
{
    public function guardar(Pedido $pedido): Pedido
    {
        if ($pedido->id === null) {
            $modelo = new PedidoModel();
            $modelo->fill($this->atributos($pedido));
            $modelo->save();

            foreach ($pedido->items as $item) {
                $modelo->items()->create([
                    'producto_id' => $item->productoId,
                    'nombre_producto' => $item->nombreProducto,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $item->precioUnitario,
                ]);
            }

            return $this->aDominio($modelo->fresh('items'));
        }

        $modelo = PedidoModel::query()->findOrFail($pedido->id);
        $modelo->fill($this->atributos($pedido));
        $modelo->save();

        return $this->aDominio($modelo->fresh('items'));
    }

    public function buscarPorId(int $id): ?Pedido
    {
        $modelo = $this->conItems()->find($id);

        return $modelo === null ? null : $this->aDominio($modelo);
    }

    public function listarTodos(): array
    {
        return $this->conItems()
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PedidoModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function listarPorEstado(EstadoPedido $estado): array
    {
        return $this->conItems()
            ->where('estado', $estado->value)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PedidoModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function listarPorRepartidor(int $repartidorId): array
    {
        return $this->conItems()
            ->where('repartidor_id', $repartidorId)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PedidoModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    /**
     * @return Builder<PedidoModel>
     */
    private function conItems(): Builder
    {
        return PedidoModel::query()->with('items');
    }

    /**
     * @return array<string, mixed>
     */
    private function atributos(Pedido $pedido): array
    {
        return [
            'cliente_nombre' => $pedido->clienteNombre,
            'cliente_telefono' => $pedido->clienteTelefono,
            'cliente_direccion' => $pedido->clienteDireccion,
            'fecha' => $pedido->fecha->format('Y-m-d'),
            'estado' => $pedido->estado->value,
            'observaciones' => $pedido->observaciones,
            'repartidor_id' => $pedido->repartidorId,
            'metodo_pago' => $pedido->metodoPago?->value,
            'monto_cobrado' => $pedido->montoCobrado,
            'fecha_entrega' => $pedido->fechaEntrega?->format('Y-m-d'),
        ];
    }

    private function aDominio(PedidoModel $modelo): Pedido
    {
        return Pedido::reconstituir(
            id: $modelo->id,
            clienteNombre: $modelo->cliente_nombre,
            clienteTelefono: $modelo->cliente_telefono,
            clienteDireccion: $modelo->cliente_direccion,
            fecha: new DateTimeImmutable($modelo->fecha->format('Y-m-d')),
            estado: EstadoPedido::from($modelo->estado),
            observaciones: $modelo->observaciones,
            items: $modelo->items->map(static fn (ItemPedidoModel $item): ItemPedido => ItemPedido::crear(
                productoId: (int) $item->producto_id,
                nombreProducto: $item->nombre_producto,
                cantidad: (float) $item->cantidad,
                precioUnitario: (float) $item->precio_unitario,
            ))->all(),
            repartidorId: $modelo->repartidor_id,
            metodoPago: $modelo->metodo_pago === null ? null : MetodoPago::from($modelo->metodo_pago),
            montoCobrado: $modelo->monto_cobrado === null ? null : (float) $modelo->monto_cobrado,
            fechaEntrega: $modelo->fecha_entrega === null ? null : new DateTimeImmutable($modelo->fecha_entrega->format('Y-m-d')),
        );
    }
}
