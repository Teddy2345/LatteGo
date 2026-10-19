<?php

declare(strict_types=1);

namespace App\Application\Produccion\UseCases;

use App\Application\Produccion\DTOs\DespachoData;
use App\Application\Produccion\DTOs\RegistrarDespachoData;
use App\Domain\Produccion\Entities\Despacho;
use App\Domain\Produccion\Exceptions\CantidadDespachoInvalidaException;
use App\Domain\Produccion\Exceptions\ProduccionNoEncontradaException;
use App\Domain\Produccion\Repositories\DespachoRepository;
use App\Domain\Produccion\Repositories\ProduccionRepository;

/**
 * Registra el despacho de una produccion. El despachador confirma cuantos
 * quesos recibio; si difiere de lo producido, la diferencia se registra
 * automaticamente como merma.
 */
final class RegistrarDespachoUseCase
{
    public function __construct(
        private readonly DespachoRepository $despachos,
        private readonly ProduccionRepository $producciones,
    ) {
    }

    public function ejecutar(RegistrarDespachoData $datos): DespachoData
    {
        $produccion = $this->producciones->buscarPorId($datos->produccionId);

        if ($produccion === null) {
            throw new ProduccionNoEncontradaException("No existe una produccion con id {$datos->produccionId}.");
        }

        // El despacho historico cuenta quesos. Un lote dinamico ya ingreso a
        // Almacen con su propia unidad: su salida corresponde a Almacen o Ventas.
        if ($produccion->resultados !== []) {
            throw new CantidadDespachoInvalidaException('Este lote ya ingresó a Almacén. Registra su salida desde Almacén o Ventas con la unidad del producto.');
        }

        $despacho = Despacho::crear(
            produccionId: $datos->produccionId,
            despachadorId: $datos->despachadorId,
            quesosProducidosReferencia: $produccion->quesosProducidos->valor,
            quesosRecibidos: $datos->quesosRecibidos,
            quesosDespachados: $datos->quesosDespachados,
            observaciones: $datos->observaciones,
        );

        return DespachoData::desdeEntidad($this->despachos->guardar($despacho));
    }
}
