<?php

declare(strict_types=1);

namespace App\Application\Acopio\UseCases;

use App\Application\Acopio\DTOs\AcopioData;
use App\Application\Acopio\DTOs\RegistrarAcopioData;
use App\Domain\Acopio\Entities\Acopio;
use App\Domain\Acopio\Repositories\AcopioRepository;
use App\Domain\Acopio\ValueObjects\CantidadLitros;
use DateTimeImmutable;

/**
 * Registra un acopio de leche en campo. La cantidad debe ser mayor a 0 y,
 * si hubo perdida de leche, el motivo es obligatorio (ambas via el Value
 * Object y la Entidad de Domain). El acopio queda pendiente_sincronizar
 * hasta que se confirme la sincronizacion con SincronizarAcopioUseCase,
 * reflejando que la app movil lo registra sin conexion.
 */
final class RegistrarAcopioUseCase
{
    public function __construct(
        private readonly AcopioRepository $acopios,
    ) {
    }

    public function ejecutar(RegistrarAcopioData $datos): AcopioData
    {
        $acopio = Acopio::crear(
            proveedorId: $datos->proveedorId,
            acopiadorId: $datos->acopiadorId,
            rutaId: $datos->rutaId,
            fecha: new DateTimeImmutable($datos->fecha),
            cantidadLitros: new CantidadLitros($datos->cantidadLitros),
            observaciones: $datos->observaciones,
            perdidaLitros: $datos->perdidaLitros,
            motivoPerdida: $datos->motivoPerdida,
            movilidadId: $datos->movilidadId,
        );

        return AcopioData::desdeEntidad($this->acopios->guardar($acopio));
    }
}
