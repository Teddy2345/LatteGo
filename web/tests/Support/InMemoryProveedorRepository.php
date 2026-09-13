<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Proveedor\Entities\Proveedor;
use App\Domain\Proveedor\Repositories\ProveedorRepository;

final class InMemoryProveedorRepository implements ProveedorRepository
{
    /** @var array<int, Proveedor> */
    private array $proveedores = [];

    private int $siguienteId = 1;

    public function guardar(Proveedor $proveedor): Proveedor
    {
        $guardado = $proveedor->id === null
            ? Proveedor::reconstituir(
                id: $this->siguienteId++,
                nombre: $proveedor->nombre,
                cedula: $proveedor->cedula,
                telefono: $proveedor->telefono,
                finca: $proveedor->finca,
                litrosProm: $proveedor->litrosProm,
                precioLitro: $proveedor->precioLitro,
                activo: $proveedor->activo,
                rutaId: $proveedor->rutaId,
            )
            : $proveedor;

        $this->proveedores[$guardado->id] = $guardado;

        return $guardado;
    }

    public function buscarPorId(int $id): ?Proveedor
    {
        return $this->proveedores[$id] ?? null;
    }

    public function buscarPorCedula(string $cedula): ?Proveedor
    {
        foreach ($this->proveedores as $proveedor) {
            if ((string) $proveedor->cedula === $cedula) {
                return $proveedor;
            }
        }

        return null;
    }

    public function existeCedula(string $cedula, ?int $ignorarId = null): bool
    {
        $encontrado = $this->buscarPorCedula($cedula);

        return $encontrado !== null && $encontrado->id !== $ignorarId;
    }

    public function listarActivos(): array
    {
        return array_values(array_filter(
            $this->proveedores,
            static fn (Proveedor $proveedor): bool => $proveedor->activo,
        ));
    }

    public function listarTodos(): array
    {
        return array_values($this->proveedores);
    }

    public function eliminar(int $id): void
    {
        unset($this->proveedores[$id]);
    }
}
