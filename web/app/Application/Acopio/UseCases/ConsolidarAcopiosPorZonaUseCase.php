<?php

declare(strict_types=1);

namespace App\Application\Acopio\UseCases;

use App\Application\Acopio\DTOs\ConsolidadoDiarioData;
use App\Application\Acopio\DTOs\ConsolidadoZonaData;
use App\Domain\Acopio\Entities\Acopio;
use App\Domain\Acopio\Repositories\AcopioRepository;
use App\Domain\Proveedor\Entities\Ruta;
use App\Domain\Proveedor\Repositories\RutaRepository;
use DateTimeImmutable;

/**
 * Resume el litraje recibido en una fecha, agrupado por zona de acopio (la
 * ruta del acopiador). Es el tablero que la planta consulta durante la
 * jornada para saber cuanta leche va llegando de cada zona.
 *
 * Los acopios sin ruta asignada se agrupan aparte para que el total del
 * reporte siempre cuadre con lo recibido ese dia.
 */
final class ConsolidarAcopiosPorZonaUseCase
{
    private const ZONA_SIN_ASIGNAR = 'Sin zona asignada';

    public function __construct(
        private readonly AcopioRepository $acopios,
        private readonly RutaRepository $rutas,
    ) {
    }

    public function ejecutar(DateTimeImmutable $fecha): ConsolidadoDiarioData
    {
        $acopios = $this->acopios->listarPorFecha($fecha);

        $nombres = [];
        foreach ($this->rutas->listarTodas() as $ruta) {
            /** @var Ruta $ruta */
            $nombres[$ruta->id] = $ruta->nombre;
        }

        /** @var array<string, array{rutaId: ?int, litros: float, registros: int}> $agrupado */
        $agrupado = [];
        $totalLitros = 0.0;

        foreach ($acopios as $acopio) {
            /** @var Acopio $acopio */
            $clave = $acopio->rutaId === null ? 'sin-zona' : (string) $acopio->rutaId;

            $agrupado[$clave] ??= ['rutaId' => $acopio->rutaId, 'litros' => 0.0, 'registros' => 0];
            $agrupado[$clave]['litros'] += $acopio->cantidadLitros->valor;
            $agrupado[$clave]['registros']++;

            $totalLitros += $acopio->cantidadLitros->valor;
        }

        $zonas = array_map(
            fn (array $fila): ConsolidadoZonaData => new ConsolidadoZonaData(
                rutaId: $fila['rutaId'],
                zona: $fila['rutaId'] === null
                    ? self::ZONA_SIN_ASIGNAR
                    : ($nombres[$fila['rutaId']] ?? self::ZONA_SIN_ASIGNAR),
                litros: round($fila['litros'], 2),
                registros: $fila['registros'],
            ),
            array_values($agrupado),
        );

        usort($zonas, static fn (ConsolidadoZonaData $a, ConsolidadoZonaData $b): int => $b->litros <=> $a->litros);

        return new ConsolidadoDiarioData(
            fecha: $fecha->format('Y-m-d'),
            totalLitros: round($totalLitros, 2),
            totalRegistros: count($acopios),
            actualizadoEn: (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            zonas: $zonas,
        );
    }
}
