<?php

declare(strict_types=1);

namespace App\Domain\Produccion\Entities;

use App\Domain\Produccion\Exceptions\CantidadDespachoInvalidaException;

final class Despacho
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $produccionId,
        public readonly ?int $despachadorId,
        public readonly int $quesosRecibidos,
        public readonly int $quesosDespachados,
        public readonly int $merma,
        public readonly ?string $observaciones,
    ) {
    }

    /**
     * El despachador confirma cuantos quesos recibio de produccion. Si esa
     * cantidad difiere de lo producido, la diferencia se registra como
     * merma (nunca negativa).
     */
    public static function crear(
        int $produccionId,
        ?int $despachadorId,
        int $quesosProducidosReferencia,
        int $quesosRecibidos,
        int $quesosDespachados,
        ?string $observaciones,
    ): self {
        self::validarNoNegativo($quesosRecibidos, 'Los quesos recibidos no pueden ser negativos.');
        self::validarNoNegativo($quesosDespachados, 'Los quesos despachados no pueden ser negativos.');

        $merma = max(0, $quesosProducidosReferencia - $quesosRecibidos);

        return new self(
            id: null,
            produccionId: $produccionId,
            despachadorId: $despachadorId,
            quesosRecibidos: $quesosRecibidos,
            quesosDespachados: $quesosDespachados,
            merma: $merma,
            observaciones: $observaciones,
        );
    }

    public static function reconstituir(
        int $id,
        int $produccionId,
        ?int $despachadorId,
        int $quesosRecibidos,
        int $quesosDespachados,
        int $merma,
        ?string $observaciones,
    ): self {
        return new self($id, $produccionId, $despachadorId, $quesosRecibidos, $quesosDespachados, $merma, $observaciones);
    }

    private static function validarNoNegativo(int $valor, string $mensaje): void
    {
        if ($valor < 0) {
            throw new CantidadDespachoInvalidaException($mensaje);
        }
    }
}
