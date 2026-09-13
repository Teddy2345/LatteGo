<?php

declare(strict_types=1);

namespace App\Application\Acopio\DTOs;

use App\Domain\Acopio\Entities\Acopio;

final readonly class AcopioData
{
    public function __construct(
        public int $id,
        public int $proveedorId,
        public ?int $acopiadorId,
        public ?int $rutaId,
        public string $fecha,
        public float $cantidadLitros,
        public string $estado,
        public ?string $observaciones,
        public ?float $perdidaLitros,
        public ?string $motivoPerdida,
        public string $semanaPagoInicio,
        public string $semanaPagoFin,
        public ?float $latitud = null,
        public ?float $longitud = null,
        public ?float $precisionMetros = null,
        public ?string $capturadoEn = null,
        public ?string $requestId = null,
        public ?int $movilidadId = null,
    ) {
    }

    public static function desdeEntidad(Acopio $acopio): self
    {
        $semana = $acopio->semanaPago();

        return new self(
            id: $acopio->id,
            proveedorId: $acopio->proveedorId,
            acopiadorId: $acopio->acopiadorId,
            rutaId: $acopio->rutaId,
            fecha: $acopio->fecha->format('Y-m-d'),
            cantidadLitros: $acopio->cantidadLitros->valor,
            estado: $acopio->estado->value,
            observaciones: $acopio->observaciones,
            perdidaLitros: $acopio->perdidaLitros,
            motivoPerdida: $acopio->motivoPerdida,
            semanaPagoInicio: $semana->inicio->format('Y-m-d'),
            semanaPagoFin: $semana->fin->format('Y-m-d'),
            latitud: $acopio->ubicacion?->latitud,
            longitud: $acopio->ubicacion?->longitud,
            precisionMetros: $acopio->ubicacion?->precisionMetros,
            capturadoEn: $acopio->ubicacion?->capturadoEn->format('Y-m-d H:i:s'),
            requestId: $acopio->requestId,
            movilidadId: $acopio->movilidadId,
        );
    }
}
