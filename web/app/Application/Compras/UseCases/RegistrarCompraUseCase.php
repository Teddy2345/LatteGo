<?php

declare(strict_types=1);

namespace App\Application\Compras\UseCases;

use App\Domain\Inventario\Repositories\CompraRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Compra de insumos: la boleta del proveedor con sus lineas. Cada linea
 * entra al almacen como movimiento de compra, que es lo que despues puede
 * consumir produccion.
 */
final class RegistrarCompraUseCase
{
    public function __construct(private readonly CompraRepository $compras)
    {
    }

    public function ejecutar(array $datos, int $usuarioId): int
    {
        $datos = Validator::make($datos, [
            'fecha' => 'required|date_format:Y-m-d|before_or_equal:today',
            'proveedor' => 'required|string|max:160',
            'documento' => 'nullable|string|max:60',
            'observaciones' => 'nullable|string|max:1000',
            'requestId' => 'required|uuid',
            'items' => 'required|array|min:1|max:50',
            'items.*.productoId' => ['required', 'integer', 'distinct', Rule::exists('productos', 'id')->where('tipo', 'insumo')->where('activo', true)],
            'items.*.cantidad' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99',
            'items.*.costoUnitario' => 'required|numeric|decimal:0,2|min:0|max:999999.99',
        ], [
            'items.*.productoId.distinct' => 'Un mismo insumo no puede ir dos veces en la misma compra; suma las cantidades.',
            'items.*.productoId.required' => 'Elige el insumo de cada línea.',
            'items.*.cantidad.required' => 'Indica cuánto se compró en cada línea.',
            'items.*.costoUnitario.required' => 'Indica cuánto costó cada unidad.',
        ])->validate();

        $total = 0.0;

        foreach ($datos['items'] as $item) {
            $total += (float) $item['cantidad'] * (float) $item['costoUnitario'];
        }

        if (round($total, 2) > 99999999.99) {
            throw ValidationException::withMessages(['items' => 'El total de la compra queda fuera del rango admitido.']);
        }

        return $this->compras->registrar($datos, $usuarioId);
    }
}
