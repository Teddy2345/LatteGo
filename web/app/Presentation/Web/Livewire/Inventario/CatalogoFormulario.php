<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Inventario;

use App\Application\Inventario\DTOs\ActualizarProductoData;
use App\Application\Inventario\DTOs\RegistrarProductoData;
use App\Application\Inventario\UseCases\ActualizarProductoUseCase;
use App\Application\Inventario\UseCases\ObtenerProductoUseCase;
use App\Application\Inventario\UseCases\RegistrarProductoUseCase;
use App\Domain\Inventario\ValueObjects\TipoProducto;
use App\Infrastructure\Inventario\Models\ProductoModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
final class CatalogoFormulario extends Component
{
    use WithFileUploads;

    public ?int $productoId = null;

    /** Ruta ya guardada de la foto; se conserva si no se sube una nueva. */
    public ?string $fotoPath = null;

    #[Validate('required|string|max:120')]
    public string $nombre = '';

    #[Validate('required|in:producto_terminado,insumo')]
    public string $tipo = 'insumo';

    #[Validate('nullable|string|max:60')]
    public ?string $categoria = null;

    #[Validate('required|string|max:20')]
    public string $unidad = '';

    #[Validate('required|numeric|min:0')]
    public float $precioReferencia = 0.0;

    #[Validate('nullable|numeric|min:0')]
    public ?float $stockMinimo = null;

    #[Validate('nullable|string|max:500')]
    public ?string $descripcion = null;

    #[Validate('nullable|image|max:2048')]
    public $foto = null;

    public string $error = '';

    public function mount(ObtenerProductoUseCase $obtener, ?int $productoId = null): void
    {
        if ($productoId === null) {
            $this->authorize('gestionarCatalogo', ProductoModel::class);

            return;
        }

        $this->authorize('gestionarCatalogo', ProductoModel::class);

        $producto = $obtener->ejecutar($productoId);

        $this->productoId = $producto->id;
        $this->nombre = $producto->nombre;
        $this->tipo = $producto->tipo;
        $this->categoria = $producto->categoria;
        $this->unidad = $producto->unidad;
        $this->precioReferencia = $producto->precioReferencia;
        $this->stockMinimo = $producto->stockMinimo;
        $this->descripcion = $producto->descripcion;
        $this->fotoPath = $producto->fotoPath;
    }

    public function guardar(RegistrarProductoUseCase $registrar, ActualizarProductoUseCase $actualizar): void
    {
        $this->authorize('gestionarCatalogo', ProductoModel::class);

        $this->validate();

        $rutaFoto = $this->foto !== null
            ? $this->foto->store('productos', 'public')
            : $this->fotoPath;

        if ($this->productoId === null) {
            $registrar->ejecutar(new RegistrarProductoData(
                nombre: $this->nombre,
                tipo: $this->tipo,
                categoria: $this->categoria,
                unidad: $this->unidad,
                precioReferencia: $this->precioReferencia,
                stockMinimo: $this->stockMinimo,
                descripcion: $this->descripcion,
                fotoPath: $rutaFoto,
            ));
        } else {
            if ($this->foto !== null && $this->fotoPath !== null) {
                Storage::disk('public')->delete($this->fotoPath);
            }

            $actualizar->ejecutar(new ActualizarProductoData(
                id: $this->productoId,
                nombre: $this->nombre,
                categoria: $this->categoria,
                unidad: $this->unidad,
                precioReferencia: $this->precioReferencia,
                stockMinimo: $this->stockMinimo,
                descripcion: $this->descripcion,
                fotoPath: $rutaFoto,
            ));
        }

        $this->redirect(route('inventario.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.inventario.catalogo-formulario', [
            'tipos' => TipoProducto::cases(),
        ]);
    }
}
