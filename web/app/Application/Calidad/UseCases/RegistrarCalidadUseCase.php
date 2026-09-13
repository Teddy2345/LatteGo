<?php

declare(strict_types=1);

namespace App\Application\Calidad\UseCases;

use App\Application\Calidad\DTOs\CalidadData;
use App\Application\Calidad\DTOs\RegistrarCalidadData;
use App\Application\Notificacion\DTOs\RegistrarNotificacionData;
use App\Application\Notificacion\UseCases\RegistrarNotificacionUseCase;
use App\Application\Proveedor\UseCases\CambiarEstadoProveedorUseCase;
use App\Domain\Calidad\Entities\Calidad;
use App\Domain\Calidad\Repositories\CalidadRepository;
use App\Domain\Calidad\ValueObjects\Densidad;
use App\Domain\Calidad\ValueObjects\MotivoRechazo;
use App\Domain\Calidad\ValueObjects\Ph;
use App\Domain\Calidad\ValueObjects\PorcentajeAguaAgregada;
use App\Domain\Calidad\ValueObjects\PruebaAlcohol;
use App\Domain\Calidad\ValueObjects\ResultadoCalidad;
use App\Domain\Calidad\ValueObjects\SancionAplicada;
use App\Domain\Notificacion\ValueObjects\NivelNotificacion;
use App\Domain\Proveedor\Repositories\ProveedorRepository;
use App\Domain\Proveedor\Repositories\RutaRepository;
use App\Domain\Shared\Contracts\TransactionManager;
use DateTimeImmutable;

/**
 * Registra un analisis de calidad (LactoScan). La leche se rechaza
 * automaticamente si el agua añadida es mayor a 0% o si la prueba de
 * alcohol es rechazada. Cuando el rechazo es por adulteracion, se escala
 * la sancion segun el historial del proveedor: primera vez, descuento de
 * toda la semana; de la segunda vez en adelante, retiro temporal (que
 * ademas desactiva al proveedor via CambiarEstadoProveedorUseCase).
 *
 * Todo rechazo (adulteracion u otro motivo) genera una notificacion para
 * quien supervisa la planta. Guardar el analisis, aplicar la sancion y
 * registrar la notificacion ocurren en una sola transaccion.
 */
final class RegistrarCalidadUseCase
{
    private const ETIQUETAS_MOTIVO = [
        MotivoRechazo::Adulteracion->value => 'Adulteración',
        MotivoRechazo::Acidez->value => 'Acidez',
        MotivoRechazo::Otro->value => 'Otro',
    ];

    private const ETIQUETAS_SANCION = [
        SancionAplicada::DescuentoSemana->value => 'Descuento de la semana',
        SancionAplicada::RetiroTemporal->value => 'Retiro temporal del proveedor',
    ];

    public function __construct(
        private readonly CalidadRepository $analisis,
        private readonly CambiarEstadoProveedorUseCase $cambiarEstadoProveedor,
        private readonly RegistrarNotificacionUseCase $notificar,
        private readonly ProveedorRepository $proveedores,
        private readonly RutaRepository $rutas,
        private readonly TransactionManager $transacciones,
    ) {
    }

    public function ejecutar(RegistrarCalidadData $datos): CalidadData
    {
        $calidad = Calidad::crear(
            proveedorId: $datos->proveedorId,
            acopioId: $datos->acopioId,
            fecha: new DateTimeImmutable($datos->fecha),
            temperatura: $datos->temperatura,
            grasa: $datos->grasa,
            solidosNoGrasos: $datos->solidosNoGrasos,
            densidad: new Densidad($datos->densidad),
            proteina: $datos->proteina,
            lactosa: $datos->lactosa,
            sales: $datos->sales,
            aguaAgregada: new PorcentajeAguaAgregada($datos->aguaAgregada),
            ph: new Ph($datos->ph),
            pruebaAlcohol: $datos->pruebaAlcoholAceptada ? PruebaAlcohol::Aceptada : PruebaAlcohol::Rechazada,
            motivoRechazoManual: $datos->motivoRechazoManual !== null
                ? MotivoRechazo::from($datos->motivoRechazoManual)
                : null,
            fotoPath: $datos->fotoPath,
        );

        if ($calidad->motivoRechazo === MotivoRechazo::Adulteracion) {
            $adulteracionesPrevias = $this->analisis->contarAdulteracionesPrevias($datos->proveedorId);
            $calidad = $calidad->conSancion($calidad->determinarSancion($adulteracionesPrevias));
        }

        return $this->transacciones->run(function () use ($calidad): CalidadData {
            if ($calidad->sancionAplicada === SancionAplicada::RetiroTemporal) {
                $this->cambiarEstadoProveedor->ejecutar($calidad->proveedorId, false);
            }

            $guardado = $this->analisis->guardar($calidad);

            if ($guardado->resultado === ResultadoCalidad::Rechazada) {
                $this->notificarRechazo($guardado);
            }

            return CalidadData::desdeEntidad($guardado);
        });
    }

    private function notificarRechazo(Calidad $calidad): void
    {
        $proveedor = $this->proveedores->buscarPorId($calidad->proveedorId);
        $nombreProveedor = $proveedor !== null ? (string) $proveedor->nombre : 'Proveedor #'.$calidad->proveedorId;

        $zona = null;
        if ($proveedor?->rutaId !== null) {
            foreach ($this->rutas->listarTodas() as $ruta) {
                if ($ruta->id === $proveedor->rutaId) {
                    $zona = $ruta->nombre;
                    break;
                }
            }
        }

        $motivo = self::ETIQUETAS_MOTIVO[$calidad->motivoRechazo?->value ?? ''] ?? 'No especificado';

        $datos = [
            'Proveedor' => $nombreProveedor,
            'Fecha' => $calidad->fecha->format('Y-m-d'),
            'Zona / ruta' => $zona,
            'Motivo' => $motivo,
        ];

        if ($calidad->aguaAgregada->valor > 0.0) {
            $datos['Agua añadida'] = number_format($calidad->aguaAgregada->valor, 2).' %';
        }

        if ($calidad->sancionAplicada !== SancionAplicada::Ninguna) {
            $datos['Sanción'] = self::ETIQUETAS_SANCION[$calidad->sancionAplicada->value] ?? $calidad->sancionAplicada->value;
        }

        $this->notificar->ejecutar(new RegistrarNotificacionData(
            tipo: 'calidad_rechazo',
            titulo: 'Alerta de calidad',
            mensaje: "Leche de {$nombreProveedor} rechazada por {$motivo}.",
            nivel: NivelNotificacion::Alerta,
            datos: $datos,
        ));
    }
}
