<?php

declare(strict_types=1);

namespace App\Application\Movilidad\UseCases;

use App\Application\Movilidad\DTOs\CoberturaMovilidadData;
use App\Application\Movilidad\DTOs\ProveedorCoberturaData;
use App\Domain\Movilidad\Exceptions\MovilidadNoEncontradaException;
use App\Domain\Movilidad\Repositories\MovilidadRepository;
use App\Domain\Proveedor\Entities\Proveedor;
use App\Domain\Proveedor\Entities\Ruta;
use App\Domain\Proveedor\Repositories\ProveedorRepository;
use App\Domain\Proveedor\Repositories\RutaRepository;

/**
 * Lo que cubre una movilidad: su ruta, las paradas de referencia para el mapa
 * y los proveedores activos de esa ruta, ordenados por nombre. A diferencia
 * del detalle del recorrido, no mira lo entregado hoy: es la foto fija de a
 * quien atiende cada vehiculo.
 */
final class ObtenerCoberturaMovilidadUseCase
{
    public function __construct(
        private readonly MovilidadRepository $movilidades,
        private readonly RutaRepository $rutas,
        private readonly ProveedorRepository $proveedores,
    ) {
    }

    public function ejecutar(int $movilidadId): CoberturaMovilidadData
    {
        $movilidad = $this->movilidades->buscarPorId($movilidadId);

        if ($movilidad === null) {
            throw new MovilidadNoEncontradaException("No existe una movilidad con id {$movilidadId}.");
        }

        $ruta = null;
        foreach ($this->rutas->listarTodas() as $candidata) {
            /** @var Ruta $candidata */
            if ($candidata->id === $movilidad->rutaId) {
                $ruta = $candidata;
                break;
            }
        }

        $proveedores = $ruta === null ? [] : array_values(array_filter(
            $this->proveedores->listarActivos(),
            static fn (Proveedor $proveedor): bool => $proveedor->rutaId === $ruta->id,
        ));

        usort($proveedores, static fn (Proveedor $a, Proveedor $b): int => strcasecmp((string) $a->nombre, (string) $b->nombre));

        return new CoberturaMovilidadData(
            id: (int) $movilidad->id,
            nombre: $movilidad->nombre,
            tipo: $movilidad->tipo->value,
            usuarioId: $movilidad->usuarioId,
            rutaNombre: $ruta?->nombre,
            paradasReferencia: $ruta?->paradasReferencia ?? [],
            proveedores: array_map(
                static fn (Proveedor $proveedor): ProveedorCoberturaData => new ProveedorCoberturaData(
                    id: (int) $proveedor->id,
                    nombre: (string) $proveedor->nombre,
                    finca: $proveedor->finca,
                    telefono: $proveedor->telefono,
                    litrosPromedio: $proveedor->litrosProm->valor,
                ),
                $proveedores,
            ),
        );
    }
}
