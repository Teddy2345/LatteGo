<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Produccion;

use App\Application\Inventario\UseCases\ListarProductosConStockUseCase;
use App\Application\Produccion\DTOs\InsumoUtilizadoData;
use App\Application\Produccion\DTOs\RegistrarProduccionData;
use App\Application\Produccion\UseCases\RegistrarProduccionUseCase;
use App\Domain\Inventario\ValueObjects\TipoProducto;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Infrastructure\Produccion\Models\ProduccionModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
final class Formulario extends Component
{
    #[Validate('required|date')]
    public string $fecha = '';

    #[Validate('required|numeric|min:0.01')]
    public float $litrosProcesados = 0.0;

    #[Validate('required|integer|min:0')]
    public int $quesosProducidos = 0;

    #[Validate('nullable|string|max:1000')]
    public ?string $observaciones = null;

    /** Producto del catálogo al que corresponde este lote (opcional). */
    #[Validate('nullable|integer|exists:productos,id')]
    public ?int $productoId = null;

    /** @var array<int, array{productoId: ?int, cantidad: ?float}> */
    public array $insumos = [];

    public string $error = '';

    public function mount(): void
    {
        $this->authorize('create', ProduccionModel::class);

        $this->fecha = now()->format('Y-m-d');
    }

    public function agregarInsumo(): void
    {
        $this->insumos[] = ['productoId' => null, 'cantidad' => null];
    }

    public function quitarInsumo(int $indice): void
    {
        unset($this->insumos[$indice]);
        $this->insumos = array_values($this->insumos);
    }

    public function guardar(RegistrarProduccionUseCase $registrar): void
    {
        $this->authorize('create', ProduccionModel::class);

        $this->validate();
        $this->error = '';

        $insumosUtilizados = [];
        foreach ($this->insumos as $insumo) {
            if ($insumo['productoId'] !== null && $insumo['cantidad'] !== null && $insumo['cantidad'] !== '') {
                $insumosUtilizados[] = new InsumoUtilizadoData(
                    productoId: (int) $insumo['productoId'],
                    cantidad: (float) $insumo['cantidad'],
                );
            }
        }

        try {
            $registrar->ejecutar(new RegistrarProduccionData(
                fecha: $this->fecha,
                litrosProcesados: $this->litrosProcesados,
                quesosProducidos: $this->quesosProducidos,
                jefaProduccionId: auth()->id(),
                observaciones: $this->observaciones,
                productoId: $this->productoId,
                insumosUtilizados: $insumosUtilizados,
            ));
        } catch (DomainRuleException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->redirect(route('produccion.index'), navigate: true);
    }

    public function render(ListarProductosConStockUseCase $listar): View
    {
        return view('livewire.produccion.formulario', [
            'productosTerminados' => $listar->ejecutar(tipo: TipoProducto::ProductoTerminado),
            'insumosDisponibles' => $listar->ejecutar(tipo: TipoProducto::Insumo),
        ]);
    }
}
