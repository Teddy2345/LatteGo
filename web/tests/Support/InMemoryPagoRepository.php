<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Pagos\Entities\Pago;
use App\Domain\Pagos\Repositories\PagoRepository;
use App\Domain\Pagos\ValueObjects\EstadoPago;
use DateTimeImmutable;

final class InMemoryPagoRepository implements PagoRepository
{
    /** @var array<int, Pago> */
    private array $pagos = [];

    private int $siguienteId = 1;

    public function guardar(Pago $pago): Pago
    {
        $guardado = $pago->id === null
            ? Pago::reconstituir(
                id: $this->siguienteId++,
                proveedorId: $pago->proveedorId,
                semanaInicio: $pago->semanaInicio,
                semanaFin: $pago->semanaFin,
                totalLitros: $pago->totalLitros,
                precioLitro: $pago->precioLitro,
                totalPagar: $pago->totalPagar,
                fechaPago: $pago->fechaPago,
                estado: $pago->estado,
            )
            : $pago;

        $this->pagos[$guardado->id] = $guardado;

        return $guardado;
    }

    public function buscarPorId(int $id): ?Pago
    {
        return $this->pagos[$id] ?? null;
    }

    public function buscarPorProveedorYSemana(int $proveedorId, DateTimeImmutable $semanaInicio): ?Pago
    {
        foreach ($this->pagos as $pago) {
            if ($pago->proveedorId === $proveedorId && $pago->semanaInicio == $semanaInicio) {
                return $pago;
            }
        }

        return null;
    }

    public function listarTodos(): array
    {
        return array_values($this->pagos);
    }

    public function listarPorProveedor(int $proveedorId): array
    {
        return array_values(array_filter(
            $this->pagos,
            static fn (Pago $p): bool => $p->proveedorId === $proveedorId,
        ));
    }

    public function listarPendientes(): array
    {
        return array_values(array_filter(
            $this->pagos,
            static fn (Pago $p): bool => $p->estado === EstadoPago::Pendiente,
        ));
    }
}
