<?php

declare(strict_types=1);

namespace App\Application\Produccion\UseCases;

use App\Domain\Produccion\Repositories\RecetaRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class RegistrarLoteDinamicoUseCase
{
    public function __construct(private RecetaRepository $recetas)
    {
    }

    public function preparar(int $recetaId, float $cantidad): array
    {
        Validator::make(['cantidadPlanificada' => $cantidad], ['cantidadPlanificada' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99'])->validate();
        $receta = $this->recetas->buscar($recetaId);
        if (!$receta['cantidad_base'] || !$receta['unidad_medida_id']) {
            throw ValidationException::withMessages(['recetaId' => 'Esta receta antigua no tiene cantidad de salida. Crea una nueva versión indicando cuánto producto se obtiene.']);
        }
        $factor = $cantidad / (float) $receta['cantidad_base'];
        $insumos = array_map(fn ($i) => ['productoId' => (int) $i['insumo_id'], 'cantidad' => round((float) $i['cantidad'] * $factor, 2)], $receta['insumos']);
        foreach ($insumos as $insumo) {
            if ($insumo['cantidad'] < 0.01 || $insumo['cantidad'] > 99999999.99) {
                throw ValidationException::withMessages(['cantidadPlanificada' => 'La cantidad calculada de un insumo queda fuera del rango admitido. Ajusta el lote o crea una receta con una unidad más precisa, por ejemplo gramos.']);
            }
        }
        $leche = round((float) $receta['litros_base'] * $factor, 2);
        if ($leche > 99999999.99 || ((float) $receta['litros_base'] > 0 && $leche < 0.01)) {
            throw ValidationException::withMessages(['cantidadPlanificada' => 'Los litros calculados quedan fuera del rango admitido. Ajusta la cantidad del lote.']);
        }
        return ['productoId' => (int) $receta['producto_id'], 'insumos' => $insumos, 'litrosProcesados' => $leche];
    }

    /**
     * Una jornada puede sacar varios productos de la misma tanda de leche
     * —queso y, con el suero, ricotta— asi que el lote lleva una lista de
     * productos. Los insumos y los litros son del lote entero: se descuentan
     * una sola vez, no por producto.
     *
     * Sigue aceptando la forma de un solo producto (productoId suelto), que
     * es como la guarda la app del telefono.
     */
    public function ejecutar(array $datos, int $usuarioId): int
    {
        $datos = $this->conListaDeProductos($datos);

        $datos = Validator::make($datos, [
            'fecha' => 'required|date_format:Y-m-d|before_or_equal:today',
            'productos' => 'required|array|min:1|max:20',
            'productos.*.productoId' => ['required', 'integer', 'distinct', Rule::exists('productos', 'id')->where('tipo', 'producto_terminado')->where('activo', true)],
            'productos.*.recetaId' => 'nullable|integer|exists:recetas,id',
            'productos.*.cantidadPlanificada' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99',
            'productos.*.cantidadProducida' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99',
            'litrosProcesados' => 'required|numeric|decimal:0,2|min:0|max:99999999.99',
            'observaciones' => 'nullable|string|max:1000', 'requestId' => 'required|uuid',
            'insumos' => 'present|array|max:100',
            'insumos.*.productoId' => ['required', 'integer', 'distinct', Rule::exists('productos', 'id')->where('tipo', 'insumo')->where('activo', true)],
            'insumos.*.cantidad' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99',
        ], [
            'productos.*.productoId.distinct' => 'Un mismo producto no puede ir dos veces en el lote; suma sus cantidades.',
            'productos.*.productoId.required' => 'Elige el producto de cada línea.',
        ])->validate();

        if ($datos['litrosProcesados'] == 0 && count($datos['insumos']) === 0) {
            throw ValidationException::withMessages(['insumos' => 'Registra al menos un insumo o la leche de acopio utilizada.']);
        }

        return $this->recetas->registrarDinamico($datos, $usuarioId);
    }

    /** Normaliza la forma de un solo producto a la lista que usa el repositorio. */
    private function conListaDeProductos(array $datos): array
    {
        if (isset($datos['productos'])) {
            unset($datos['productoId'], $datos['recetaId'], $datos['cantidadPlanificada'], $datos['cantidadProducida']);

            return $datos;
        }

        $datos['productos'] = [[
            'productoId' => $datos['productoId'] ?? null,
            'recetaId' => $datos['recetaId'] ?? null,
            'cantidadPlanificada' => $datos['cantidadPlanificada'] ?? null,
            'cantidadProducida' => $datos['cantidadProducida'] ?? null,
        ]];

        unset($datos['productoId'], $datos['recetaId'], $datos['cantidadPlanificada'], $datos['cantidadProducida']);

        return $datos;
    }
}
