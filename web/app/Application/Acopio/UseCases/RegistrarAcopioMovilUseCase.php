<?php

declare(strict_types=1);

namespace App\Application\Acopio\UseCases;

use App\Application\Acopio\DTOs\AcopioData;
use App\Application\Acopio\DTOs\RegistrarAcopioMovilData;
use App\Domain\Acopio\Entities\Acopio;
use App\Domain\Acopio\Exceptions\ProveedorSuspendidoException;
use App\Domain\Acopio\Repositories\AcopioRepository;
use App\Domain\Acopio\ValueObjects\CantidadLitros;
use App\Domain\Acopio\ValueObjects\SemanaPago;
use App\Domain\Acopio\ValueObjects\UbicacionCaptura;
use App\Domain\Calidad\Repositories\CalidadRepository;
use DateTimeImmutable;

/**
 * Recibe un acopio enviado desde la app movil del acopiador, junto con el
 * punto GPS donde se midio la leche.
 *
 * A diferencia de RegistrarAcopioUseCase, aqui el acopio nace Sincronizado:
 * la app lo encola sin conexion y este envio es, precisamente, el momento en
 * que llega al sistema central. El requestId generado por el telefono hace
 * idempotente la operacion, de modo que reintentar un envio cuya respuesta se
 * perdio devuelve el acopio ya registrado en lugar de duplicar la recepcion.
 *
 * Un proveedor suspendido por reincidir con agua no puede entregar hasta que
 * termine esa semana de pago. La comprobacion va despues de la idempotencia:
 * un envio que ya se habia aceptado debe seguir devolviendose aunque despues
 * suspendieran al proveedor, o la app lo descartaria de su cola y se perderia.
 */
final class RegistrarAcopioMovilUseCase
{
    public function __construct(
        private readonly AcopioRepository $acopios,
        private readonly CalidadRepository $calidad,
    ) {
    }

    public function ejecutar(RegistrarAcopioMovilData $datos): AcopioData
    {
        $yaRegistrado = $this->acopios->buscarPorRequestId($datos->requestId);

        if ($yaRegistrado !== null) {
            return AcopioData::desdeEntidad($yaRegistrado);
        }

        $fecha = new DateTimeImmutable($datos->fecha);
        $semana = SemanaPago::desde($fecha);

        if (in_array($datos->proveedorId, $this->calidad->proveedoresSuspendidosEntre($semana->inicio, $semana->fin), true)) {
            throw new ProveedorSuspendidoException(
                'Este proveedor está suspendido por reincidir con agua en la leche. '
                .'No puede entregar hasta el '.$semana->fin->format('Y-m-d').'.'
            );
        }

        $acopio = Acopio::crear(
            proveedorId: $datos->proveedorId,
            acopiadorId: $datos->acopiadorId,
            rutaId: $datos->rutaId,
            fecha: $fecha,
            cantidadLitros: new CantidadLitros($datos->cantidadLitros),
            observaciones: $datos->observaciones,
            perdidaLitros: $datos->perdidaLitros,
            motivoPerdida: $datos->motivoPerdida,
            ubicacion: new UbicacionCaptura(
                latitud: $datos->latitud,
                longitud: $datos->longitud,
                precisionMetros: $datos->precisionMetros,
                capturadoEn: new DateTimeImmutable($datos->capturadoEn),
            ),
            requestId: $datos->requestId,
        )->marcarSincronizado();

        return AcopioData::desdeEntidad($this->acopios->guardar($acopio));
    }
}
