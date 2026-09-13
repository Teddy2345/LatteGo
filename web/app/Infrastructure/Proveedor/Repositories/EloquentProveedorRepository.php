<?php

declare(strict_types=1);

namespace App\Infrastructure\Proveedor\Repositories;

use App\Domain\Proveedor\Entities\Proveedor;
use App\Domain\Proveedor\Repositories\ProveedorRepository;
use App\Domain\Proveedor\ValueObjects\Cedula;
use App\Domain\Proveedor\ValueObjects\LitrosPromedio;
use App\Domain\Proveedor\ValueObjects\Nombre;
use App\Domain\Proveedor\ValueObjects\PrecioLitro;
use App\Infrastructure\Proveedor\Models\ProveedorModel;

final class EloquentProveedorRepository implements ProveedorRepository
{
    public function guardar(Proveedor $proveedor): Proveedor
    {
        $modelo = $proveedor->id === null
            ? new ProveedorModel()
            : ProveedorModel::query()->findOrFail($proveedor->id);

        $modelo->fill([
            'nombre' => (string) $proveedor->nombre,
            'cedula' => (string) $proveedor->cedula,
            'telefono' => $proveedor->telefono,
            'finca' => $proveedor->finca,
            'litros_prom' => $proveedor->litrosProm->valor,
            'precio_litro' => $proveedor->precioLitro->valor,
            'activo' => $proveedor->activo,
            'ruta_id' => $proveedor->rutaId,
        ]);

        $modelo->save();

        return $this->aDominio($modelo);
    }

    public function buscarPorId(int $id): ?Proveedor
    {
        $modelo = ProveedorModel::query()->find($id);

        return $modelo === null ? null : $this->aDominio($modelo);
    }

    public function buscarPorCedula(string $cedula): ?Proveedor
    {
        $modelo = ProveedorModel::query()->where('cedula', $cedula)->first();

        return $modelo === null ? null : $this->aDominio($modelo);
    }

    public function existeCedula(string $cedula, ?int $ignorarId = null): bool
    {
        return ProveedorModel::query()
            ->where('cedula', $cedula)
            ->when($ignorarId !== null, fn ($query) => $query->where('id', '!=', $ignorarId))
            ->exists();
    }

    public function listarActivos(): array
    {
        return ProveedorModel::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (ProveedorModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function listarTodos(): array
    {
        return ProveedorModel::query()
            ->orderBy('nombre')
            ->get()
            ->map(fn (ProveedorModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function eliminar(int $id): void
    {
        ProveedorModel::query()->whereKey($id)->delete();
    }

    private function aDominio(ProveedorModel $modelo): Proveedor
    {
        return Proveedor::reconstituir(
            id: $modelo->id,
            nombre: new Nombre($modelo->nombre),
            cedula: new Cedula($modelo->cedula),
            telefono: $modelo->telefono,
            finca: $modelo->finca,
            litrosProm: new LitrosPromedio($modelo->litros_prom),
            precioLitro: new PrecioLitro((float) $modelo->precio_litro),
            activo: $modelo->activo,
            rutaId: $modelo->ruta_id,
        );
    }
}
