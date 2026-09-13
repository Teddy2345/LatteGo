<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Pagos;

use App\Application\Pagos\DTOs\PagoData;
use App\Application\Pagos\UseCases\GenerarPlanillaPagoUseCase;
use App\Application\Pagos\UseCases\ListarPagosUseCase;
use App\Application\Pagos\UseCases\MarcarPagoComoPagadoUseCase;
use App\Application\Proveedor\UseCases\ListarProveedoresUseCase;
use App\Infrastructure\Pagos\Models\PagoModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
final class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';
    public string $fechaReferencia = '';

    public string $mensaje = '';

    public function mount(): void
    {
        $this->authorize('viewAny', PagoModel::class);

        $this->fechaReferencia = now()->format('Y-m-d');
    }

    public function generarPlanilla(GenerarPlanillaPagoUseCase $generar): void
    {
        $this->authorize('generarPlanilla', PagoModel::class);

        $generados = $generar->ejecutar($this->fechaReferencia);

        $this->mensaje = count($generados) > 0
            ? 'Se generaron '.count($generados).' pago(s) para la semana.'
            : 'No hay pagos nuevos para generar en esa semana (ya generados o sin acopios sincronizados).';
    }

    public function marcarPagado(int $id, MarcarPagoComoPagadoUseCase $marcar): void
    {
        $this->authorize('generarPlanilla', PagoModel::class);

        $marcar->ejecutar($id);
    }

    public function render(ListarPagosUseCase $listar, ListarProveedoresUseCase $listarProveedores): View
    {
        $paginador = PagoModel::query()->orderByDesc('semana_inicio')->paginate(8);

        $mapped = $paginador->getCollection()->map(function (PagoModel $m): PagoData {
            return new PagoData(
                id: $m->id,
                proveedorId: (int) $m->proveedor_id,
                semanaInicio: $m->semana_inicio->format('Y-m-d'),
                semanaFin: $m->semana_fin->format('Y-m-d'),
                totalLitros: (float) $m->total_litros,
                precioLitro: (float) $m->precio_litro,
                totalPagar: (float) $m->total_pagar,
                fechaPago: $m->fecha_pago?->format('Y-m-d'),
                estado: (string) $m->estado,
            );
        });

        $paginador->setCollection($mapped);

        return view('livewire.pagos.index', [
            'pagos' => $paginador,
            'proveedores' => collect($listarProveedores->ejecutar())->keyBy('id'),
        ]);
    }
}
