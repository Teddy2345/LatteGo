<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Proveedor;

use App\Application\Proveedor\DTOs\ProveedorData;
use App\Application\Proveedor\UseCases\ActualizarPrecioLitroGeneralUseCase;
use App\Application\Proveedor\UseCases\CambiarEstadoProveedorUseCase;
use App\Application\Proveedor\UseCases\EliminarProveedorUseCase;
use App\Application\Proveedor\UseCases\ListarProveedoresUseCase;
use App\Domain\Proveedor\Exceptions\PrecioLitroInvalidoException;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
final class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';
    public string $busqueda = '';

    public bool $mostrarPrecioTemporada = false;
    public ?float $precioTemporada = null;
    public string $avisoPrecio = '';
    public string $errorPrecio = '';

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->authorize('viewAny', ProveedorModel::class);
    }

    public function cambiarEstado(int $id, bool $activo, CambiarEstadoProveedorUseCase $useCase): void
    {
        $this->authorize('update', ProveedorModel::class);

        $useCase->ejecutar($id, $activo);
    }

    public function eliminar(int $id, EliminarProveedorUseCase $useCase): void
    {
        $this->authorize('delete', ProveedorModel::class);

        $useCase->ejecutar($id);
    }

    public function abrirPrecioTemporada(): void
    {
        $this->authorize('update', ProveedorModel::class);

        $this->precioTemporada = (float) ProveedorModel::query()->max('precio_litro');
        $this->avisoPrecio = '';
        $this->errorPrecio = '';
        $this->mostrarPrecioTemporada = true;
    }

    public function cerrarPrecioTemporada(): void
    {
        $this->mostrarPrecioTemporada = false;
    }

    /**
     * Fija el precio de la temporada para todo el padron. Las planillas ya
     * generadas guardan su propio precio, asi que esto solo rige para las
     * siguientes.
     */
    public function aplicarPrecioTemporada(ActualizarPrecioLitroGeneralUseCase $actualizar): void
    {
        $this->authorize('update', ProveedorModel::class);

        $this->avisoPrecio = '';
        $this->errorPrecio = '';

        try {
            $actualizados = $actualizar->ejecutar((float) $this->precioTemporada);
        } catch (PrecioLitroInvalidoException $e) {
            $this->errorPrecio = $e->getMessage();

            return;
        }

        $this->avisoPrecio = $actualizados === 0
            ? 'Todos los proveedores ya tenían ese precio.'
            : "Precio actualizado en {$actualizados} proveedores.";
    }

    public function render(ListarProveedoresUseCase $listar): View
    {
        $query = ProveedorModel::query()->orderBy('nombre');

        if ($this->busqueda !== '') {
            $termino = '%'.mb_strtolower($this->busqueda).'%';
            $query->where(function ($q) use ($termino) {
                $q->whereRaw('lower(nombre) like ?', [$termino])
                    ->orWhereRaw('lower(cedula) like ?', [$termino]);
            });
        }

        $paginador = $query->paginate(8);

        $mapped = $paginador->getCollection()->map(function (ProveedorModel $m): ProveedorData {
            return new ProveedorData(
                id: $m->id,
                nombre: (string) $m->nombre,
                cedula: (string) $m->cedula,
                telefono: $m->telefono,
                finca: $m->finca,
                litrosProm: (int) $m->litros_prom,
                precioLitro: (float) $m->precio_litro,
                activo: (bool) $m->activo,
                rutaId: $m->ruta_id,
            );
        });

        $paginador->setCollection($mapped);

        return view('livewire.proveedor.index', ['proveedores' => $paginador]);
    }
}
