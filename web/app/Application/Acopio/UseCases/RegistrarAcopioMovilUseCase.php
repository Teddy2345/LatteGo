<?php

declare(strict_types=1);

namespace App\Application\Acopio\UseCases;

use App\Application\Acopio\DTOs\AcopioData;
use App\Application\Acopio\DTOs\RegistrarAcopioMovilData;
use App\Domain\Acopio\Entities\Acopio;
use App\Domain\Acopio\Repositories\AcopioRepository;
use App\Domain\Acopio\ValueObjects\CantidadLitros;
use App\Domain\Acopio\ValueObjects\UbicacionCaptura;
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
 */
final class RegistrarAcopioMovilUseCase
{
    public function __construct(
        private readonly AcopioRepository $acopios,
    ) {
    }

    public function ejecutar(RegistrarAcopioMovilData $datos): AcopioData
    {
        $yaRegistrado = $this->acopios->buscarPorRequestId($datos->requestId);

        if ($yaRegistrado !== null) {
            return AcopioData::desdeEntidad($yaRegistrado);
        }

        $acopio = Acopio::crear(
            proveedorId: $datos->proveedorId,
            acopiadorId: $datos->acopiadorId,
            rutaId: $datos->rutaId,
            fecha: new DateTimeImmutable($datos->fecha),
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
