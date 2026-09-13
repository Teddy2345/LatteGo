<?php

declare(strict_types=1);

namespace App\Application\Acopio\UseCases;

use App\Application\Acopio\DTOs\AcopioMovilData;
use App\Domain\Acopio\Entities\Acopio;
use App\Domain\Acopio\Repositories\AcopioRepository;
use App\Domain\Proveedor\Entities\Proveedor;
use App\Domain\Proveedor\Entities\Ruta;
use App\Domain\Proveedor\Repositories\ProveedorRepository;
use App\Domain\Proveedor\Repositories\RutaRepository;

/**
 * Lista acopios para la app movil con los nombres de proveedor y zona ya
 * resueltos. Cuando se recibe un acopiadorId solo devuelve sus propias
 * recepciones; sin el, devuelve las de toda la planta.
 *
 * Los catalogos se cargan una sola vez y se indexan en memoria para no
 * disparar una consulta por fila.
 */
final class ListarAcopiosMovilUseCase
{
    private const SIN_ZONA = 'Sin zona asignada';

    public function __construct(
        private readonly AcopioRepository $acopios,
        private readonly ProveedorRepository $proveedores,
        private readonly RutaRepository $rutas,
    ) {
    }

    /**
     * @return AcopioMovilData[]
     */
    public function ejecutar(?int $acopiadorId = null): array
    {
        $acopios = $acopiadorId === null
            ? $this->acopios->listarTodos()
            : $this->acopios->listarPorAcopiador($acopiadorId);

        $nombresProveedor = [];
        foreach ($this->proveedores->listarTodos() as $proveedor) {
            /** @var Proveedor $proveedor */
            $nombresProveedor[$proveedor->id] = (string) $proveedor->nombre;
        }

        $nombresZona = [];
        foreach ($this->rutas->listarTodas() as $ruta) {
            /** @var Ruta $ruta */
            $nombresZona[$ruta->id] = $ruta->nombre;
        }

        return array_map(
            static fn (Acopio $acopio): AcopioMovilData => AcopioMovilData::desde(
                $acopio,
                $nombresProveedor[$acopio->proveedorId] ?? 'Proveedor #'.$acopio->proveedorId,
                $acopio->rutaId === null
                    ? self::SIN_ZONA
                    : ($nombresZona[$acopio->rutaId] ?? self::SIN_ZONA),
            ),
            $acopios,
        );
    }
}
