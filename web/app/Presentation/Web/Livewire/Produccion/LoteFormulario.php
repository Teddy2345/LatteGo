<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Produccion;

use App\Application\Inventario\UseCases\ListarProductosConStockUseCase;
use App\Application\Produccion\UseCases\GestionarRecetasUseCase;
use App\Application\Produccion\UseCases\RegistrarLoteDinamicoUseCase;
use App\Domain\Inventario\ValueObjects\TipoProducto;
use App\Infrastructure\Produccion\Models\ProduccionModel;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
final class LoteFormulario extends Component
{
    public string $fecha = '';

    /**
     * Los productos que sale el lote. De una misma tanda pueden salir
     * varios —queso y, con el suero, ricotta—, cada uno con su receta.
     *
     * @var array<int, array{recetaId: int|null, productoId: int|null, cantidadPlanificada: mixed, cantidadProducida: mixed}>
     */
    public array $productos = [];

    public $litrosProcesados = 0;
    public string $observaciones = '';

    /** Insumos del lote entero: se descuentan una sola vez, no por producto. */
    public array $insumos = [];

    #[Locked] public string $requestId = '';
    #[Locked] public string $recetaAplicada = '';

    public function mount(): void
    {
        $this->authorize('create', ProduccionModel::class);
        $this->fecha = now()->format('Y-m-d');
        $this->requestId = (string) Str::uuid();
        $this->productos = [$this->lineaVacia()];
    }

    private function lineaVacia(): array
    {
        return ['recetaId' => null, 'productoId' => null, 'cantidadPlanificada' => 1, 'cantidadProducida' => 1];
    }

    public function agregarProducto(): void
    {
        $this->productos[] = $this->lineaVacia();
        // Al sumar un producto el calculo anterior ya no cubre el lote.
        $this->recetaAplicada = '';
    }

    public function quitarProducto(int $indice): void
    {
        unset($this->productos[$indice]);
        $this->productos = array_values($this->productos);

        if ($this->productos === []) {
            $this->productos = [$this->lineaVacia()];
        }

        $this->recetaAplicada = '';
    }

    /**
     * Calcula los insumos de todo el lote: suma lo que pide la receta de
     * cada producto, de modo que un insumo compartido salga una sola vez.
     */
    public function aplicarRecetas(RegistrarLoteDinamicoUseCase $lotes): void
    {
        $this->authorize('create', ProduccionModel::class);

        $this->validate([
            'productos' => 'required|array|min:1',
            'productos.*.cantidadPlanificada' => 'required|numeric|min:0.01',
        ]);

        $insumos = [];
        $litros = 0.0;

        foreach ($this->productos as $indice => $linea) {
            if (empty($linea['recetaId'])) {
                continue;
            }

            $datos = $lotes->preparar((int) $linea['recetaId'], (float) $linea['cantidadPlanificada']);

            $this->productos[$indice]['productoId'] = $datos['productoId'];
            $this->productos[$indice]['cantidadProducida'] = $linea['cantidadPlanificada'];
            $litros += $datos['litrosProcesados'];

            foreach ($datos['insumos'] as $insumo) {
                $id = $insumo['productoId'];
                $insumos[$id] = round(($insumos[$id] ?? 0) + $insumo['cantidad'], 2);
            }
        }

        $this->insumos = [];

        foreach ($insumos as $productoId => $cantidad) {
            $this->insumos[] = ['productoId' => $productoId, 'cantidad' => $cantidad];
        }

        $this->litrosProcesados = round($litros, 2);
        $this->recetaAplicada = $this->firmaCalculo();
        $this->resetValidation();
    }

    private function firmaCalculo(): string
    {
        return json_encode(array_map(static fn (array $l): array => [
            $l['recetaId'] ?? null,
            (string) ($l['cantidadPlanificada'] ?? ''),
        ], $this->productos));
    }

    /** Alguna linea del lote sigue una receta. */
    private function hayRecetas(): bool
    {
        foreach ($this->productos as $linea) {
            if (! empty($linea['recetaId'])) {
                return true;
            }
        }

        return false;
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

    public function guardar(RegistrarLoteDinamicoUseCase $lotes): void
    {
        $this->authorize('create', ProduccionModel::class);

        if ($this->hayRecetas() && $this->recetaAplicada !== $this->firmaCalculo()) {
            $this->addError('productos', 'Pulsa Aplicar recetas para calcular los insumos con las cantidades actuales.');

            return;
        }

        $id = $lotes->ejecutar([
            'fecha' => $this->fecha,
            'productos' => $this->productos,
            'litrosProcesados' => $this->litrosProcesados,
            'insumos' => $this->insumos,
            'observaciones' => $this->observaciones,
            'requestId' => $this->requestId,
        ], auth()->id());

        session()->flash('loteGuardado', 'Lote #'.$id.' registrado. Almacén actualizado.');
        $this->redirect(route('produccion.index'), navigate: true);
    }

    public function render(ListarProductosConStockUseCase $productos, GestionarRecetasUseCase $recetas)
    {
        $this->authorize('create', ProduccionModel::class);
        $terminados = $productos->ejecutar(tipo: TipoProducto::ProductoTerminado);

        // La unidad de cada linea, para rotular sus cantidades con lo que
        // realmente se mide: piezas de queso, litros de yogur.
        $unidades = [];

        foreach ($this->productos as $indice => $linea) {
            $elegido = collect($terminados)->firstWhere('id', $linea['productoId']);
            $unidades[$indice] = $elegido?->unidad ?? 'del producto';
        }

        return view('livewire.produccion.lote-formulario', [
            'productosCatalogo' => $terminados,
            'unidades' => $unidades,
            'recetas' => $recetas->listar(),
            'catalogoInsumos' => $productos->ejecutar(tipo: TipoProducto::Insumo),
        ]);
    }
}
