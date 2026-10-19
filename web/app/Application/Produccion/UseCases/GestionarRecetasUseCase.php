<?php

declare(strict_types=1);

namespace App\Application\Produccion\UseCases;

use App\Domain\Produccion\Repositories\RecetaRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class GestionarRecetasUseCase
{
    public function __construct(private RecetaRepository $recetas)
    {
    }
    public function listar(): array
    {
        return $this->recetas->listar();
    }
    public function buscar(int $id): array
    {
        return $this->recetas->buscar($id);
    }
    private function reglasInsumos(): array
    {
        return ['insumos' => 'array|max:100', 'insumos.*.productoId' => ['required','integer','distinct', Rule::exists('productos', 'id')->where('tipo', 'insumo')->where('activo', true)],
            'insumos.*.cantidad' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99'];
    }
    public function crear(array $datos, int $usuarioId): int
    {
        $datos = Validator::make($datos, array_merge($this->reglasInsumos(), [
            'nombre' => 'required|string|max:120|unique:recetas,nombre',
            'productoId' => ['required','integer', Rule::exists('productos', 'id')->where('tipo', 'producto_terminado')->where('activo', true)],
            'litrosBase' => isset($datos['cantidadBase']) ? 'required|numeric|decimal:0,2|min:0|max:99999999.99' : 'required|numeric|decimal:0,2|min:0.01|max:99999999.99',
            'cantidadBase' => 'sometimes|required|numeric|decimal:0,2|min:0.01|max:99999999.99',
            'descripcion' => 'nullable|string|max:1000', 'insumos' => 'present|array|max:100',
        ]))->validate();
        if ((float) $datos['litrosBase'] === 0.0 && count($datos['insumos']) === 0) {
            throw \Illuminate\Validation\ValidationException::withMessages(['insumos' => 'La receta necesita insumos o leche de acopio.']);
        }
        return $this->recetas->crear($datos, $usuarioId);
    }
}
