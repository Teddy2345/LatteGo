<?php

declare(strict_types=1);

namespace App\Domain\Calidad\Repositories;

use App\Domain\Calidad\Entities\Calidad;
use DateTimeImmutable;

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
     * Cuenta cuantas veces este proveedor añadio agua, en cualquier momento,
     * para escalar la sancion (entrega sin pago -> suspension de la semana).
     */
    public function contarAdulteracionesPrevias(int $proveedorId): int;

    /** Cada entrega se analiza una sola vez: un segundo analisis contaria como reincidencia. */
    public function existeParaAcopio(int $acopioId): bool;

    /**
     * De estos acopios, los que dieron positivo a agua añadida. Esas entregas
     * no se pagan.
     *
     * @param  int[]  $acopioIds
     * @return int[]
     */
    public function acopiosConAguaAnadida(array $acopioIds): array;

    /**
     * Proveedores suspendidos por reincidir con agua dentro de ese rango de
     * fechas: esa semana no pueden entregar ni cobran.
     *
     * @return int[]
     */
    public function proveedoresSuspendidosEntre(DateTimeImmutable $inicio, DateTimeImmutable $fin): array;
}
