<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Calidad;

use App\Application\Calidad\DTOs\CalidadData;
use App\Application\Calidad\UseCases\ListarCalidadUseCase;
use App\Application\Proveedor\UseCases\ListarProveedoresUseCase;
use App\Infrastructure\Calidad\Models\CalidadModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
final class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';
    public function mount(): void
    {
        $this->authorize('viewAny', CalidadModel::class);
    }

    public function render(ListarCalidadUseCase $listar, ListarProveedoresUseCase $listarProveedores): View
    {
        // Obtener paginador directo desde el modelo y mapear a CalidadData
        $paginador = CalidadModel::query()
            ->orderByDesc('fecha')
            ->paginate(8);

        $mapped = $paginador->getCollection()->map(function (CalidadModel $modelo): CalidadData {
            return new CalidadData(
                id: $modelo->id,
                proveedorId: $modelo->proveedor_id,
                acopioId: $modelo->acopio_id ?? 0,
                fecha: $modelo->fecha->format('Y-m-d'),
                temperatura: (float) $modelo->temperatura,
                grasa: (float) $modelo->grasa,
                solidosNoGrasos: (float) $modelo->solidos_no_grasos,
                densidad: (float) $modelo->densidad,
                densidadClasificacion: (string) ($modelo->densidad ?? ''),
                proteina: (float) $modelo->proteina,
                lactosa: (float) $modelo->lactosa,
                sales: (float) $modelo->sales,
                aguaAgregada: (float) $modelo->agua_agregada,
                ph: (float) $modelo->ph,
                pruebaAlcohol: (string) $modelo->prueba_alcohol,
                resultado: (string) $modelo->resultado,
                motivoRechazo: $modelo->motivo_rechazo,
                sancionAplicada: (string) $modelo->sancion_aplicada,
            );
        });

        $paginador->setCollection($mapped);

        return view('livewire.calidad.index', [
            'analisis' => $paginador,
            'proveedores' => collect($listarProveedores->ejecutar())->keyBy('id'),
        ]);
    }
}
