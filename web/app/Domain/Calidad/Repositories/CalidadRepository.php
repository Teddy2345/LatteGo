<?php

declare(strict_types=1);

namespace App\Domain\Calidad\Repositories;

use App\Domain\Calidad\Entities\Calidad;

interface CalidadRepository
{
    public function guardar(Calidad $calidad): Calidad;

    public function buscarPorId(int $id): ?Calidad;

    /**
     * @return Calidad[]
     */
    public function listarTodos(): array;

    /**
     * @return Calidad[]
     */
    public function listarPorProveedor(int $proveedorId): array;

    /**
     * Cuenta cuantos analisis de este proveedor fueron rechazados por
     * adulteracion, usado para escalar la sancion (descuento -> retiro).
     */
    public function contarAdulteracionesPrevias(int $proveedorId): int;
}
