<?php

declare(strict_types=1);

namespace App\Infrastructure\Pagos\Repositories;

use App\Domain\Pagos\Entities\Pago;
use App\Domain\Pagos\Repositories\PagoRepository;
use App\Domain\Pagos\ValueObjects\EstadoPago;
use App\Domain\Pagos\ValueObjects\TotalLitros;
use App\Infrastructure\Pagos\Models\PagoModel;
use DateTimeImmutable;

final class EloquentPagoRepository implements PagoRepository
{
    public function guardar(Pago $pago): Pago
    {
        $modelo = $pago->id === null
            ? new PagoModel()
            : PagoModel::query()->findOrFail($pago->id);

        $modelo->fill([
            'proveedor_id' => $pago->proveedorId,
            'semana_inicio' => $pago->semanaInicio->format('Y-m-d'),
            'semana_fin' => $pago->semanaFin->format('Y-m-d'),
            'total_litros' => $pago->totalLitros->valor,
            'precio_litro' => $pago->precioLitro,
            'total_pagar' => $pago->totalPagar,
            'fecha_pago' => $pago->fechaPago?->format('Y-m-d'),
            'estado' => $pago->estado->value,
        ]);

        $modelo->save();

        return $this->aDominio($modelo);
    }

    public function buscarPorId(int $id): ?Pago
    {
        $modelo = PagoModel::query()->find($id);

        return $modelo === null ? null : $this->aDominio($modelo);
    }

    public function buscarPorProveedorYSemana(int $proveedorId, DateTimeImmutable $semanaInicio): ?Pago
    {
        $modelo = PagoModel::query()
            ->where('proveedor_id', $proveedorId)
            ->whereDate('semana_inicio', $semanaInicio->format('Y-m-d'))
            ->first();

        return $modelo === null ? null : $this->aDominio($modelo);
    }

    public function listarTodos(): array
    {
        return PagoModel::query()
            ->orderByDesc('semana_inicio')
            ->get()
            ->map(fn (PagoModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function listarPorProveedor(int $proveedorId): array
    {
        return PagoModel::query()
            ->where('proveedor_id', $proveedorId)
            ->orderByDesc('semana_inicio')
            ->get()
            ->map(fn (PagoModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function listarPendientes(): array
    {
        return PagoModel::query()
            ->where('estado', EstadoPago::Pendiente->value)
            ->orderBy('semana_inicio')
            ->get()
            ->map(fn (PagoModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    private function aDominio(PagoModel $modelo): Pago
    {
        return Pago::reconstituir(
            id: $modelo->id,
            proveedorId: $modelo->proveedor_id,
            semanaInicio: new DateTimeImmutable($modelo->semana_inicio->format('Y-m-d')),
            semanaFin: new DateTimeImmutable($modelo->semana_fin->format('Y-m-d')),
            totalLitros: new TotalLitros((float) $modelo->total_litros),
            precioLitro: (float) $modelo->precio_litro,
            totalPagar: (float) $modelo->total_pagar,
            fechaPago: $modelo->fecha_pago === null ? null : new DateTimeImmutable($modelo->fecha_pago->format('Y-m-d')),
            estado: EstadoPago::from($modelo->estado),
        );
    }
}
