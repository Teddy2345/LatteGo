<?php

declare(strict_types=1);

namespace App\Application\Acopio\UseCases;

use App\Application\Acopio\DTOs\AcopioData;
use App\Application\Acopio\DTOs\RegistrarAcopioData;
use App\Domain\Acopio\Entities\Acopio;
use App\Domain\Acopio\Exceptions\ProveedorDadoDeBajaException;
use App\Domain\Acopio\Exceptions\ProveedorSuspendidoException;
use App\Domain\Acopio\Repositories\AcopioRepository;
use App\Domain\Acopio\ValueObjects\CantidadLitros;
use App\Domain\Acopio\ValueObjects\SemanaPago;
use App\Domain\Calidad\Repositories\CalidadRepository;
use App\Domain\Proveedor\Repositories\ProveedorRepository;
use DateTimeImmutable;

/**
 * Registra un acopio de leche en campo. La cantidad debe ser mayor a 0 y,
 * si hubo perdida de leche, el motivo es obligatorio (ambas via el Value
 * Object y la Entidad de Domain). El acopio queda pendiente_sincronizar
 * hasta que se confirme la sincronizacion con SincronizarAcopioUseCase,
 * reflejando que la app movil lo registra sin conexion.
 *
 * Un proveedor suspendido por reincidir con agua no puede entregar hasta que
 * termine esa semana de pago, y a uno dado de baja no se le recibe nada.
 */
final class RegistrarAcopioUseCase
{
    public function __construct(
        private readonly AcopioRepository $acopios,
        private readonly CalidadRepository $calidad,
        private readonly ProveedorRepository $proveedores,
    ) {
    }

    public function ejecutar(RegistrarAcopioData $datos): AcopioData
    {
        $fecha = new DateTimeImmutable($datos->fecha);
        $this->impedirSiEstaDadoDeBaja($datos->proveedorId);
        $this->impedirSiEstaSuspendido($datos->proveedorId, $fecha);

        $acopio = Acopio::crear(
            proveedorId: $datos->proveedorId,
            acopiadorId: $datos->acopiadorId,
            rutaId: $datos->rutaId,
            fecha: $fecha,
            cantidadLitros: new CantidadLitros($datos->cantidadLitros),
            observaciones: $datos->observaciones,
            perdidaLitros: $datos->perdidaLitros,
            motivoPerdida: $datos->motivoPerdida,
            movilidadId: $datos->movilidadId,
        );

        return AcopioData::desdeEntidad($this->acopios->guardar($acopio));
    }

    /**
     * La regla vivia solo en el desplegable del formulario, asi que por la
     * API se le podia seguir registrando leche —y pagandosela— a alguien
     * que ya estaba dado de baja.
     */
    private function impedirSiEstaDadoDeBaja(int $proveedorId): void
    {
        $proveedor = $this->proveedores->buscarPorId($proveedorId);

        if ($proveedor !== null && ! $proveedor->activo) {
            throw new ProveedorDadoDeBajaException(
                'Este proveedor está dado de baja. Reactiva su ficha antes de recibirle leche.'
            );
        }
    }

    private function impedirSiEstaSuspendido(int $proveedorId, DateTimeImmutable $fecha): void
    {
        $semana = SemanaPago::desde($fecha);

        if (in_array($proveedorId, $this->calidad->proveedoresSuspendidosEntre($semana->inicio, $semana->fin), true)) {
            throw new ProveedorSuspendidoException(
                'Este proveedor está suspendido por reincidir con agua en la leche. '
                .'No puede entregar hasta el '.$semana->fin->format('Y-m-d').'.'
            );
        }
    }
}
