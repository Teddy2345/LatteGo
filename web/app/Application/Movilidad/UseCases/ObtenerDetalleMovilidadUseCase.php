<?php

declare(strict_types=1);

namespace App\Application\Movilidad\UseCases;

use App\Application\Movilidad\DTOs\MovilidadDetalleData;
use App\Application\Movilidad\DTOs\ProveedorRecorridoData;
use App\Domain\Acopio\Repositories\AcopioRepository;
use App\Domain\Movilidad\Exceptions\MovilidadNoEncontradaException;
use App\Domain\Movilidad\Repositories\IncidenciaRecorridoRepository;
use App\Domain\Movilidad\Repositories\MovilidadRepository;
use App\Domain\Proveedor\Entities\Proveedor;
use App\Domain\Proveedor\Entities\SolicitudCambioZona;
use App\Domain\Proveedor\Repositories\ProveedorRepository;
use App\Domain\Proveedor\Repositories\RutaRepository;
use App\Domain\Proveedor\Repositories\SolicitudCambioZonaRepository;
use DateTimeImmutable;

/**
 * El recorrido de una movilidad: sus proveedores en orden, con el estado de
 * hoy (pendiente/recogido/no entrego) segun exista o no un Acopio o una
 * IncidenciaRecorrido para ese proveedor en la fecha.
 */
final class ObtenerDetalleMovilidadUseCase
{
    public function __construct(
        private readonly MovilidadRepository $movilidades,
        private readonly ProveedorRepository $proveedores,
        private readonly AcopioRepository $acopios,
        private readonly IncidenciaRecorridoRepository $incidencias,
        private readonly RutaRepository $rutas,
        private readonly SolicitudCambioZonaRepository $solicitudesCambioZona,
    ) {
    }

    public function ejecutar(int $movilidadId, ?DateTimeImmutable $fecha = null): MovilidadDetalleData
    {
        $fecha ??= new DateTimeImmutable();
        $movilidad = $this->movilidades->buscarPorId($movilidadId);

        if ($movilidad === null) {
            throw new MovilidadNoEncontradaException("No existe una movilidad con id {$movilidadId}.");
        }

        $nombresRuta = [];
        foreach ($this->rutas->listarTodas() as $ruta) {
            $nombresRuta[$ruta->id] = $ruta->nombre;
        }

        $rutaNombre = $movilidad->rutaId !== null ? ($nombresRuta[$movilidad->rutaId] ?? null) : null;

        $proveedoresDeLaRuta = $movilidad->rutaId === null
            ? []
            : array_values(array_filter(
                $this->proveedores->listarActivos(),
                static fn (Proveedor $p): bool => $p->rutaId === $movilidad->rutaId,
            ));

        $acopiosHoy = [];
        foreach ($this->acopios->listarPorMovilidadYFecha($movilidadId, $fecha) as $acopio) {
            $acopiosHoy[$acopio->proveedorId] = $acopio;
        }

        $incidenciasHoy = $this->incidencias->listarPorMovilidadYFecha($movilidadId, $fecha);
        $solicitudesPendientes = $this->solicitudesCambioZona->listarPendientesIndexadasPorProveedor();

        $proveedoresData = array_map(
            static function (Proveedor $proveedor) use ($acopiosHoy, $incidenciasHoy, $solicitudesPendientes, $nombresRuta): ProveedorRecorridoData {
                $solicitud = $solicitudesPendientes[$proveedor->id] ?? null;
                $cambioZonaPendiente = $solicitud !== null;
                /** @var SolicitudCambioZona|null $solicitud */
                $zonaSolicitada = $solicitud !== null ? ($nombresRuta[$solicitud->rutaSolicitadaId] ?? null) : null;

                $acopio = $acopiosHoy[$proveedor->id] ?? null;

                if ($acopio !== null) {
                    return new ProveedorRecorridoData(
                        proveedorId: $proveedor->id,
                        nombre: (string) $proveedor->nombre,
                        finca: $proveedor->finca,
                        estado: 'recogido',
                        litros: $acopio->cantidadLitros->valor,
                        horaRegistro: null,
                        cambioZonaPendiente: $cambioZonaPendiente,
                        zonaSolicitada: $zonaSolicitada,
                    );
                }

                if (isset($incidenciasHoy[$proveedor->id])) {
                    return new ProveedorRecorridoData(
                        proveedorId: $proveedor->id,
                        nombre: (string) $proveedor->nombre,
                        finca: $proveedor->finca,
                        estado: 'no_entrego',
                        litros: null,
                        horaRegistro: null,
                        cambioZonaPendiente: $cambioZonaPendiente,
                        zonaSolicitada: $zonaSolicitada,
                    );
                }

                return new ProveedorRecorridoData(
                    proveedorId: $proveedor->id,
                    nombre: (string) $proveedor->nombre,
                    finca: $proveedor->finca,
                    estado: 'pendiente',
                    litros: null,
                    horaRegistro: null,
                    cambioZonaPendiente: $cambioZonaPendiente,
                    zonaSolicitada: $zonaSolicitada,
                );
            },
            $proveedoresDeLaRuta,
        );

        $litros = array_sum(array_map(static fn (ProveedorRecorridoData $p): float => $p->litros ?? 0.0, $proveedoresData));
        $total = count($proveedoresData);
        $atendidos = count(array_filter($proveedoresData, static fn (ProveedorRecorridoData $p): bool => $p->estado !== 'pendiente'));

        return new MovilidadDetalleData(
            id: (int) $movilidad->id,
            nombre: $movilidad->nombre,
            tipo: $movilidad->tipo->value,
            rutaNombre: $rutaNombre,
            litrosRecolectadosHoy: round($litros, 2),
            progresoPorcentaje: $total > 0 ? (int) round($atendidos / $total * 100) : 0,
            proveedores: $proveedoresData,
        );
    }
}
