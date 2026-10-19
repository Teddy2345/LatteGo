<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Proveedor;

use App\Application\Proveedor\DTOs\ProveedorData;
use App\Application\Proveedor\UseCases\ActualizarPrecioLitroGeneralUseCase;
use App\Application\Proveedor\UseCases\CambiarEstadoProveedorUseCase;
use App\Application\Proveedor\UseCases\CrearCuentaProveedorUseCase;
use App\Application\Proveedor\UseCases\EliminarProveedorUseCase;
use App\Application\Proveedor\UseCases\ListarProveedoresUseCase;
use App\Domain\Proveedor\Exceptions\PrecioLitroInvalidoException;
use App\Domain\Proveedor\Exceptions\ProveedorException;
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

    /** Clave recien generada; se muestra una vez y no se guarda en claro. */
    public string $claveGenerada = '';
    public string $proveedorDeLaClave = '';
    public string $errorCuenta = '';

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

    /**
     * Abre la cuenta de la app del proveedor. La clave se muestra una sola
     * vez en pantalla: no se guarda en claro en ningun lado, asi que si se
     * pierde hay que restablecerla.
     */
    public function abrirCuentaApp(int $proveedorId, CrearCuentaProveedorUseCase $cuentas): void
    {
        $this->authorize('update', ProveedorModel::class);

        $this->errorCuenta = '';

        try {
            $resultado = $cuentas->ejecutar($proveedorId);
        } catch (ProveedorException $e) {
            $this->errorCuenta = $e->getMessage();

            return;
        }

        $this->claveGenerada = $resultado['clave'];
        $this->proveedorDeLaClave = $resultado['proveedor']->nombre;
    }

    public function restablecerClaveApp(int $proveedorId, CrearCuentaProveedorUseCase $cuentas): void
    {
        $this->authorize('update', ProveedorModel::class);

        $this->errorCuenta = '';

        try {
            $this->claveGenerada = $cuentas->restablecerClave($proveedorId);
        } catch (ProveedorException $e) {
            $this->errorCuenta = $e->getMessage();

            return;
        }

        $this->proveedorDeLaClave = (string) ProveedorModel::query()->whereKey($proveedorId)->value('nombre');
    }

    public function cerrarClave(): void
    {
        $this->claveGenerada = '';
        $this->proveedorDeLaClave = '';
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
                tieneCuentaApp: $m->user_id !== null,
            );
        });

        $paginador->setCollection($mapped);

        return view('livewire.proveedor.index', ['proveedores' => $paginador]);
    }
}
