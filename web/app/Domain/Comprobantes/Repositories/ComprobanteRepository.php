<?php

declare(strict_types=1);

namespace App\Domain\Comprobantes\Repositories;

interface ComprobanteRepository
{
    /**
     * Emite el comprobante tomando el siguiente numero de su serie.
     *
     * @return array<string, mixed> el comprobante ya numerado
     */
    public function emitir(array $datos, int $usuarioId): array;

    /** @return array<string, mixed>|null */
    public function porVenta(int $movimientoId): ?array;

    /** @return array<string, mixed>|null */
    public function porPedido(int $pedidoId): ?array;

    /**
     * Los comprobantes de varias ventas de una vez, para no consultar uno
     * por fila al pintar la lista.
     *
     * @param  int[]  $movimientoIds
     * @return array<int, array<string, mixed>> indexados por movimiento
     */
    public function deVentas(array $movimientoIds): array;

    /** @return array<string, mixed> */
    public function buscar(int $id): array;

    /** @return array<int, array<string, mixed>> lineas del comprobante */
    public function lineas(int $id): array;
}
