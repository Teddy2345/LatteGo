<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Produccion;

use App\Application\Inventario\UseCases\ListarProductosConStockUseCase;
use App\Application\Produccion\UseCases\GestionarRecetasUseCase;
use App\Domain\Inventario\ValueObjects\TipoProducto;
use App\Infrastructure\Produccion\Models\ProduccionModel;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Recetas extends Component
{
    public string $nombre = '';
    public ?int $productoId = null;
    public $litrosBase = 0;
    public $cantidadBase = 1;
    public string $descripcion = '';
    public array $insumos = [['productoId' => null, 'cantidad' => null]];
    public function mount(): void
    {
        $this->authorize('viewAny', ProduccionModel::class);
    }
    public function agregarInsumo(): void
    {
        $this->insumos[] = ['productoId' => null, 'cantidad' => null];
    }
    public function quitarInsumo(int $i): void
    {
        unset($this->insumos[$i]);
        $this->insumos = array_values($this->insumos);
    }
    public function guardar(GestionarRecetasUseCase $recetas): void
    {
        $this->authorize('create', ProduccionModel::class);
        $recetas->crear(['nombre' => trim($this->nombre), 'productoId' => $this->productoId,
            'cantidadBase' => $this->cantidadBase, 'litrosBase' => $this->litrosBase, 'descripcion' => $this->descripcion, 'insumos' => $this->insumos], auth()->id());
        $this->reset('nombre', 'productoId', 'cantidadBase', 'litrosBase', 'descripcion', 'insumos');
        session()->flash('recetaCreada', 'Receta guardada. Ya puedes utilizarla en Nueva producción.');
    }
    public function render(GestionarRecetasUseCase $recetas, ListarProductosConStockUseCase $productos)
    {
        $this->authorize('viewAny', ProduccionModel::class);
        $terminados = $productos->ejecutar(tipo: TipoProducto::ProductoTerminado);
        $seleccionado = collect($terminados)->firstWhere('id', $this->productoId);
        return view('livewire.produccion.recetas', ['recetas' => $recetas->listar(),
            'unidad' => $seleccionado?->unidad ?? 'del producto',
            'productos' => $terminados,
            'catalogoInsumos' => $productos->ejecutar(tipo: TipoProducto::Insumo)]);
    }
}
