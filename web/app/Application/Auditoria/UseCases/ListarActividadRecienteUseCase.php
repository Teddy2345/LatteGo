<?php

declare(strict_types=1);

namespace App\Application\Auditoria\UseCases;

use App\Application\Auditoria\DTOs\RegistroActividadData;
use App\Domain\Auditoria\Entities\RegistroActividad;
use App\Domain\Auditoria\Repositories\RegistroActividadRepository;

/**
 * Lista la actividad reciente del sistema para la pantalla de auditoria.
 * El limite acota la consulta porque el historial crece de forma indefinida.
 */
final class ListarActividadRecienteUseCase
{
    public const LIMITE_POR_DEFECTO = 100;

    private const LIMITE_MAXIMO = 300;

    public function __construct(
        private readonly RegistroActividadRepository $registros,
    ) {
    }

    /**
     * @return RegistroActividadData[]
     */
    public function ejecutar(int $limite = self::LIMITE_POR_DEFECTO): array
    {
        $acotado = max(1, min($limite, self::LIMITE_MAXIMO));

        return array_map(
            static fn (RegistroActividad $registro): RegistroActividadData => RegistroActividadData::desdeEntidad($registro),
            $this->registros->listarRecientes($acotado),
        );
    }
}
