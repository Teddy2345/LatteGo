<?php

declare(strict_types=1);

namespace App\Application\Acopio\UseCases;

use App\Application\Acopio\DTOs\AcopioData;
use App\Domain\Acopio\Exceptions\AcopioNoEncontradoException;
use App\Domain\Acopio\Repositories\AcopioRepository;

/**
 * Confirma que un acopio registrado sin conexion ya llego al servidor.
 * Corresponde al permiso acopios.sincronizar, distinto de acopios.registrar.
 */
final class SincronizarAcopioUseCase
{
    public function __construct(
        private readonly AcopioRepository $acopios,
    ) {
    }

    public function ejecutar(int $acopioId): AcopioData
    {
        $acopio = $this->acopios->buscarPorId($acopioId);

        if ($acopio === null) {
            throw new AcopioNoEncontradoException("No existe un acopio con id {$acopioId}.");
        }

        return AcopioData::desdeEntidad($this->acopios->guardar($acopio->marcarSincronizado()));
    }
}
