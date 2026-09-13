<?php

declare(strict_types=1);

namespace App\Application\Proveedor\UseCases;

use App\Domain\Proveedor\Entities\Proveedor;
use App\Domain\Proveedor\Repositories\ProveedorRepository;
use App\Domain\Proveedor\ValueObjects\PrecioLitro;
use App\Domain\Shared\Contracts\TransactionManager;

/**
 * Fija el precio por litro de la temporada para todo el padron de una vez.
 *
 * El precio del acopio cambia por temporada y es el mismo para toda la
 * planta, asi que cambiarlo proveedor por proveedor es inviable. Las
 * planillas ya generadas conservan el precio con el que se pagaron: esto
 * solo rige para las siguientes.
 */
final class ActualizarPrecioLitroGeneralUseCase
{
    public function __construct(
        private readonly ProveedorRepository $proveedores,
        private readonly TransactionManager $transacciones,
    ) {
    }

    /** @return int cuantos proveedores quedaron con el precio nuevo */
    public function ejecutar(float $precioLitro): int
    {
        $precio = new PrecioLitro($precioLitro);

        return $this->transacciones->run(function () use ($precio): int {
            $actualizados = 0;

            foreach ($this->proveedores->listarTodos() as $proveedor) {
                /** @var Proveedor $proveedor */
                if ($proveedor->precioLitro->valor === $precio->valor) {
                    continue;
                }

                $this->proveedores->guardar($proveedor->cambiarPrecioLitro($precio));
                $actualizados++;
            }

            return $actualizados;
        });
    }
}
