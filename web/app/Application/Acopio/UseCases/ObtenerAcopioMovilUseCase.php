<?php

declare(strict_types=1);

namespace App\Application\Acopio\UseCases;

use App\Application\Acopio\DTOs\AcopioMovilData;
use App\Domain\Acopio\Exceptions\AcopioNoEncontradoException;
use App\Domain\Acopio\Repositories\AcopioRepository;
use App\Domain\Proveedor\Entities\Ruta;
use App\Domain\Proveedor\Repositories\ProveedorRepository;
use App\Domain\Proveedor\Repositories\RutaRepository;

/**
 * Detalle de un acopio para la app movil, con proveedor y zona resueltos.
 */
final class ObtenerAcopioMovilUseCase
{
    private const SIN_ZONA = 'Sin zona asignada';

    public function __construct(
        private readonly AcopioRepository $acopios,
        private readonly ProveedorRepository $proveedores,
        private readonly RutaRepository $rutas,
    ) {
    }

    public function ejecutar(int $id): AcopioMovilData
    {
        $acopio = $this->acopios->buscarPorId($id);

        if ($acopio === null) {
            throw new AcopioNoEncontradoException("No existe un acopio con id {$id}.");
        }

        $proveedor = $this->proveedores->buscarPorId($acopio->proveedorId);

        $zona = self::SIN_ZONA;
        if ($acopio->rutaId !== null) {
            foreach ($this->rutas->listarTodas() as $ruta) {
                /** @var Ruta $ruta */
                if ($ruta->id === $acopio->rutaId) {
                    $zona = $ruta->nombre;
                    break;
                }
            }
        }

        return AcopioMovilData::desde(
            $acopio,
            $proveedor === null ? 'Proveedor #'.$acopio->proveedorId : (string) $proveedor->nombre,
            $zona,
        );
    }
}
