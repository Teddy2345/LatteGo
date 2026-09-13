<?php

declare(strict_types=1);

namespace App\Application\Produccion\UseCases;

use App\Application\Produccion\DTOs\AptitudPasteurizadaData;
use App\Domain\Calidad\Entities\Calidad;
use App\Domain\Calidad\Repositories\CalidadRepository;
use App\Domain\Calidad\ValueObjects\ResultadoCalidad;

/**
 * Clasifica a un proveedor como apto o no para leche pasteurizada, segun
 * su historial de calidad: es apto si su analisis mas reciente fue
 * aceptado, o si aun no tiene analisis registrados (sin evidencia de
 * problemas).
 */
final class ClasificarProveedorAptoPasteurizadoUseCase
{
    public function __construct(
        private readonly CalidadRepository $analisis,
    ) {
    }

    public function ejecutar(int $proveedorId): AptitudPasteurizadaData
    {
        $historial = $this->analisis->listarPorProveedor($proveedorId);

        if ($historial === []) {
            return new AptitudPasteurizadaData($proveedorId, true, 'Sin historial de analisis de calidad.');
        }

        $masReciente = array_reduce(
            $historial,
            static fn (?Calidad $actual, Calidad $candidato) => $actual === null || $candidato->fecha > $actual->fecha ? $candidato : $actual,
        );

        $apto = $masReciente->resultado === ResultadoCalidad::Aceptada;

        return new AptitudPasteurizadaData(
            proveedorId: $proveedorId,
            apto: $apto,
            motivo: $apto
                ? 'Ultimo analisis de calidad aceptado.'
                : "Ultimo analisis de calidad rechazado ({$masReciente->motivoRechazo?->value}).",
        );
    }
}
