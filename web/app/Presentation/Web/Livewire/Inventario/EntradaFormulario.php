<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Inventario;

use App\Application\Inventario\DTOs\RegistrarEntradaInsumoData;
use App\Application\Inventario\UseCases\ListarProductosConStockUseCase;
use App\Application\Inventario\UseCases\RegistrarEntradaInsumoUseCase;
use App\Domain\Inventario\Exceptions\InventarioException;
use App\Domain\Inventario\ValueObjects\TipoProducto;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
final class EntradaFormulario extends Component
{
    #[Validate('required|integer|exists:productos,id')]
    public ?int $productoId = null;

    #[Validate('required|date')]
    public string $fecha = '';

    #[Validate('required|numeric|min:0.01')]
    public float $cantidad = 0.0;

    #[Validate('nullable|string|max:60')]
    public ?string $lote = null;

    #[Validate('nullable|string|max:255')]
    public ?string $motivo = null;

    /** Costo pagado por unidad en esta compra (opcional): alimenta el costeo de producción. */
    #[Validate('nullable|numeric|min:0')]
    public ?float $costoUnitario = null;

    public string $error = '';

    public function mount(): void
    {
        $this->authorize('registrarEntrada', MovimientoInventarioModel::class);

        $this->fecha = now()->format('Y-m-d');
    }

    public function guardar(RegistrarEntradaInsumoUseCase $registrar): void
    {
        $this->authorize('registrarEntrada', MovimientoInventarioModel::class);

        $this->validate();
        $this->error = '';

        try {
            $registrar->ejecutar(new RegistrarEntradaInsumoData(
                productoId: (int) $this->productoId,
                fecha: $this->fecha,
                cantidad: $this->cantidad,
                lote: $this->lote,
                motivo: $this->motivo,
                usuarioId: auth()->id(),
                costoUnitario: $this->costoUnitario,
            ));
        } catch (InventarioException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->redirect(route('inventario.index'), navigate: true);
    }

    public function render(ListarProductosConStockUseCase $listar): View
    {
        return view('livewire.inventario.entrada-formulario', [
            'productos' => $listar->ejecutar(tipo: TipoProducto::Insumo),
        ]);
    }
}
