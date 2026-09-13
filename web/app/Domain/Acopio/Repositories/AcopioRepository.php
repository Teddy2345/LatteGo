<?php

declare(strict_types=1);

namespace App\Domain\Acopio\Repositories;

use App\Domain\Acopio\Entities\Acopio;
use DateTimeImmutable;

interface AcopioRepository
{
    public function guardar(Acopio $acopio): Acopio;

    public function buscarPorId(int $id): ?Acopio;

    /**
     * Recupera el acopio creado con esa clave de idempotencia, si existe. La
     * app movil reintenta los envios encolados sin conexion y esta busqueda
     * evita duplicar la recepcion cuando la respuesta anterior se perdio.
     */
    public function buscarPorRequestId(string $requestId): ?Acopio;

    /**
     * @return Acopio[]
     */
    public function listarTodos(): array;

    /**
     * @return Acopio[]
     */
    public function listarPorProveedor(int $proveedorId): array;

    /**
     * @return Acopio[]
     */
    public function listarPorAcopiador(int $acopiadorId): array;

    /**
     * @return Acopio[]
     */
    public function listarPorFecha(DateTimeImmutable $fecha): array;

    /**
     * Acopios de esa movilidad en esa fecha: alimenta las tarjetas y el
     * detalle del recorrido del acopiador (quien ya fue recogido hoy).
     *
     * @return Acopio[]
     */
    public function listarPorMovilidadYFecha(int $movilidadId, DateTimeImmutable $fecha): array;

    /**
     * @return Acopio[]
     */
    public function listarPendientesSincronizacion(): array;

    /**
     * @return Acopio[]
     */
    public function listarPorSemana(DateTimeImmutable $inicio, DateTimeImmutable $fin): array;
}
