<?php

declare(strict_types=1);

namespace App\Application\Acopio\DTOs;

use App\Domain\Acopio\Entities\Acopio;

/**
 * Vista de un acopio pensada para la app movil: trae ya resueltos los
 * nombres del proveedor y de la zona, porque en campo el telefono no puede
 * pedir catalogos adicionales para poder pintar una lista.
 */
final readonly class AcopioMovilData
{
    public function __construct(
        public int $id,
        public string $nombre,
        public string $fecha,
        public string $proveedor,
        public string $zona,
        public float $cantidadLitros,
        public ?float $perdidaLitros,
        public ?string $motivoPerdida,
        public ?string $observaciones,
        public string $estado,
        public ?float $latitud,
        public ?float $longitud,
        public ?float $precisionMetros,
        public ?string $capturadoEn,
    ) {
    }

    public static function desde(Acopio $acopio, string $proveedor, string $zona): self
    {
        $litros = $acopio->cantidadLitros->valor;

        return new self(
            id: (int) $acopio->id,
            nombre: $proveedor.' · '.number_format($litros, 2).' L',
            fecha: $acopio->fecha->format('Y-m-d'),
            proveedor: $proveedor,
            zona: $zona,
            cantidadLitros: $litros,
            perdidaLitros: $acopio->perdidaLitros,
            motivoPerdida: $acopio->motivoPerdida,
            observaciones: $acopio->observaciones,
            estado: $acopio->estado->value,
            latitud: $acopio->ubicacion?->latitud,
            longitud: $acopio->ubicacion?->longitud,
            precisionMetros: $acopio->ubicacion?->precisionMetros,
            capturadoEn: $acopio->ubicacion?->capturadoEn->format('Y-m-d H:i:s'),
        );
    }
}
