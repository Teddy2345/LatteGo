<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Compras;

use App\Application\Compras\UseCases\RegistrarCompraUseCase;
use App\Application\Inventario\UseCases\ListarProductosConStockUseCase;
use App\Domain\Inventario\ValueObjects\TipoProducto;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
final class Formulario extends Component
{
    public string $fecha = '';

    public string $proveedor = '';

    public string $documento = '';

    public string $observaciones = '';

    /** @var array<int, array{productoId: int|null, cantidad: mixed, costoUnitario: mixed}> */
    public array $items = [];

    /** Identifica la compra: reintentar el guardado no la duplica. */
    #[Locked]
    public string $requestId = '';

    public function mount(): void
    {
        $this->authorize('registrarEntrada', MovimientoInventarioModel::class);

        $this->fecha = now()->format('Y-m-d');
        $this->requestId = (string) Str::uuid();
        $this->items = [['productoId' => null, 'cantidad' => null, 'costoUnitario' => null]];
    }

    public function agregarItem(): void
    {
        $this->items[] = ['productoId' => null, 'cantidad' => null, 'costoUnitario' => null];
    }

    public function quitarItem(int $indice): void
    {
        unset($this->items[$indice]);
        $this->items = array_values($this->items);

        if ($this->items === []) {
            $this->agregarItem();
        }
    }

    /** Lo que va a costar la compra, para verlo antes de guardar. */
    public function total(): float
    {
        $total = 0.0;

        foreach ($this->items as $item) {
            $total += (float) ($item['cantidad'] ?? 0) * (float) ($item['costoUnitario'] ?? 0);
        }

        return round($total, 2);
    }

    public function guardar(RegistrarCompraUseCase $registrar): void
    {
        $this->authorize('registrarEntrada', MovimientoInventarioModel::class);

        $id = $registrar->ejecutar([
            'fecha' => $this->fecha,
            'proveedor' => $this->proveedor,
            'documento' => $this->documento,
            'observaciones' => $this->observaciones,
            'items' => $this->items,
            'requestId' => $this->requestId,
        ], (int) auth()->id());

        session()->flash('compraGuardada', 'Compra #'.$id.' registrada. Los insumos ya están en almacén.');

        $this->redirect(route('compras.index'), navigate: true);
    }

    public function render(ListarProductosConStockUseCase $listar): View
    {
        return view('livewire.compras.formulario', [
            'insumos' => $listar->ejecutar(tipo: TipoProducto::Insumo),
        ]);
    }
}
