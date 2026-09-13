<?php

declare(strict_types=1);

namespace App\Application\Movilidad\UseCases;

use App\Application\Movilidad\DTOs\MovilidadTarjetaData;
use App\Domain\Acopio\Repositories\AcopioRepository;
use App\Domain\Movilidad\Entities\Movilidad;
use App\Domain\Movilidad\Repositories\MovilidadRepository;
use App\Domain\Proveedor\Entities\Ruta;
use App\Domain\Proveedor\Repositories\ProveedorRepository;
use App\Domain\Proveedor\Repositories\RutaRepository;
use DateTimeImmutable;

/**
 * Tarjetas de la pantalla principal del acopiador: una por movilidad, con
 * cuantos proveedores tiene su ruta, cuantos ya entregaron hoy y los litros
 * recolectados. La movilidad tipo "planta" agrupa a los proveedores que
 * entregan directo en planta (sin ruta asignada a un vehiculo).
 */
final class ListarMovilidadesParaAcopiadorUseCase
{
    public function __construct(
        private readonly MovilidadRepository $movilidades,
        private readonly ProveedorRepository $proveedores,
        private readonly AcopioRepository $acopios,
        private readonly RutaRepository $rutas,
    ) {
    }

    /**
     * @param  int|null  $usuarioId  Cuando se pasa, solo se devuelve la movilidad de la que
     *                                ese usuario es el acopiador titular (o ninguna, si no
     *                                tiene una asignada). Null trae todas: es para quien ve
     *                                reportes de toda la planta, no para un acopiador de campo.
     * @return MovilidadTarjetaData[]
     */
    public function ejecutar(?DateTimeImmutable $fecha = null, ?int $usuarioId = null): array
    {
        $fecha ??= new DateTimeImmutable();

        $nombresRuta = [];
        foreach ($this->rutas->listarTodas() as $ruta) {
            /** @var Ruta $ruta */
            $nombresRuta[$ruta->id] = $ruta->nombre;
        }

        $proveedoresPorRuta = [];
        foreach ($this->proveedores->listarActivos() as $proveedor) {
            if ($proveedor->rutaId !== null) {
                $proveedoresPorRuta[$proveedor->rutaId][] = $proveedor->id;
            }
        }

        return array_map(
            function (Movilidad $movilidad) use ($nombresRuta, $proveedoresPorRuta, $fecha): MovilidadTarjetaData {
                $proveedorIds = $movilidad->rutaId !== null ? ($proveedoresPorRuta[$movilidad->rutaId] ?? []) : [];
                $acopiosHoy = $this->acopios->listarPorMovilidadYFecha((int) $movilidad->id, $fecha);

                $litros = array_sum(array_map(
                    static fn ($acopio): float => $acopio->cantidadLitros->valor,
                    $acopiosHoy,
                ));

                $atendidos = count(array_unique(array_map(
                    static fn ($acopio): int => $acopio->proveedorId,
                    $acopiosHoy,
                )));

                $total = count($proveedorIds);

                return new MovilidadTarjetaData(
                    id: (int) $movilidad->id,
                    nombre: $movilidad->nombre,
                    tipo: $movilidad->tipo->value,
                    rutaNombre: $movilidad->rutaId !== null ? ($nombresRuta[$movilidad->rutaId] ?? null) : null,
                    totalProveedores: $total,
                    proveedoresAtendidos: $atendidos,
                    litrosRecolectadosHoy: round($litros, 2),
                    progresoPorcentaje: $total > 0 ? (int) round(min($atendidos, $total) / $total * 100) : 0,
                );
            },
            $usuarioId === null
                ? $this->movilidades->listarTodas()
                : array_values(array_filter(
                    $this->movilidades->listarTodas(),
                    static fn (Movilidad $m): bool => $m->usuarioId === $usuarioId,
                )),
        );
    }
}
