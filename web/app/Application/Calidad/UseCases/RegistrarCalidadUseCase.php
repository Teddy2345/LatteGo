<?php

declare(strict_types=1);

namespace App\Application\Calidad\UseCases;

use App\Application\Calidad\DTOs\CalidadData;
use App\Application\Calidad\DTOs\RegistrarCalidadData;
use App\Application\Notificacion\DTOs\RegistrarNotificacionData;
use App\Application\Notificacion\UseCases\RegistrarNotificacionUseCase;
use App\Domain\Acopio\Repositories\AcopioRepository;
use App\Domain\Calidad\Entities\Calidad;
use App\Domain\Calidad\Exceptions\AcopioDeOtroProveedorException;
use App\Domain\Calidad\Exceptions\AcopioYaAnalizadoException;
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
 * alcohol es rechazada. Cuando el ganadero añadio agua se le sanciona
 * segun su historial: la primera vez no se le paga esa entrega; si
 * reincide, queda suspendido esa semana sin pago.
 *
 * La sancion no toca el estado del proveedor: la suspension se deduce de la
 * fecha del analisis y vence sola al terminar la semana, sin que nadie tenga
 * que reactivarlo. El efecto en el pago lo aplica GenerarPlanillaPagoUseCase.
 *
 * Todo rechazo genera una notificacion para quien supervisa la planta.
 * Guardar el analisis y notificar ocurren en una sola transaccion.
 */
final class RegistrarCalidadUseCase
{
    private const ETIQUETAS_MOTIVO = [
        MotivoRechazo::Adulteracion->value => 'Adulteración',
        MotivoRechazo::Acidez->value => 'Acidez',
        MotivoRechazo::Otro->value => 'Otro',
    ];

    public function __construct(
        private readonly CalidadRepository $analisis,
        private readonly RegistrarNotificacionUseCase $notificar,
        private readonly ProveedorRepository $proveedores,
        private readonly RutaRepository $rutas,
        private readonly TransactionManager $transacciones,
        private readonly AcopioRepository $acopios,
    ) {
    }

    public function ejecutar(RegistrarCalidadData $datos): CalidadData
    {
        // La sancion se cobra al proveedor y el descuento a la entrega: ambos
        // tienen que ser del mismo ganadero.
        $acopio = $this->acopios->buscarPorId($datos->acopioId);

        if ($acopio === null) {
            throw new AcopioDeOtroProveedorException("No existe el acopio {$datos->acopioId}.");
        }

        if ($acopio->proveedorId !== $datos->proveedorId) {
            throw new AcopioDeOtroProveedorException('Ese acopio pertenece a otro proveedor.');
        }

        if ($this->analisis->existeParaAcopio($datos->acopioId)) {
            throw new AcopioYaAnalizadoException('Ese acopio ya tiene su análisis de calidad registrado.');
        }

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
            $datos['Sanción'] = $calidad->sancionAplicada->etiqueta();
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
