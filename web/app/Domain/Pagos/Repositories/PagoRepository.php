<?php

declare(strict_types=1);

namespace App\Domain\Pagos\Repositories;

use App\Domain\Pagos\Entities\Pago;
use DateTimeImmutable;

interface PagoRepository
{
    public function guardar(Pago $pago): Pago;

    public function buscarPorId(int $id): ?Pago;

    public function buscarPorProveedorYSemana(int $proveedorId, DateTimeImmutable $semanaInicio): ?Pago;

    /**
     * La planilla y los complementarios de ese proveedor en esa semana, por
     * secuencia.
     *
     * @return Pago[]
     */
    public function listarPorProveedorYSemana(int $proveedorId, DateTimeImmutable $semanaInicio): array;

    /**
     * @return Pago[]
     */
    public function listarTodos(): array;

    /**
     * @return Pago[]
     */
    public function listarPorProveedor(int $proveedorId): array;

    /**
     * @return Pago[]
     */
    public function listarPendientes(): array;
}
