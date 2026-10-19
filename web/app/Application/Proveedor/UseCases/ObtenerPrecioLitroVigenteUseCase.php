<?php

declare(strict_types=1);

namespace App\Application\Proveedor\UseCases;

use App\Domain\Proveedor\Entities\Proveedor;
use App\Domain\Proveedor\Repositories\ProveedorRepository;

/**
 * El precio por litro que rige hoy para toda la planta.
 *
 * El precio es general: lo fija el administrador para todos con
 * ActualizarPrecioLitroGeneralUseCase, y un proveedor nuevo toma este mismo.
 * Como nadie cambia el precio de un proveedor por separado, todos comparten
 * el mismo valor; se toma el mas repetido para que un dato suelto
 * inconsistente no lo altere.
 */
final class ObtenerPrecioLitroVigenteUseCase
{
    /** Precio con el que arranca la planta cuando todavia no hay proveedores. */
    public const PRECIO_INICIAL = 1.70;

    public function __construct(
        private readonly ProveedorRepository $proveedores,
    ) {
    }

    public function ejecutar(): float
    {
        $conteo = [];

        foreach ($this->proveedores->listarTodos() as $proveedor) {
            /** @var Proveedor $proveedor */
            $clave = (string) $proveedor->precioLitro;
            $conteo[$clave] = ($conteo[$clave] ?? 0) + 1;
        }

        if ($conteo === []) {
            return self::PRECIO_INICIAL;
        }

        arsort($conteo);

        return (float) array_key_first($conteo);
    }
}
