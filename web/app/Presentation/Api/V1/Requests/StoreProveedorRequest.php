<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'cedula' => ['required', 'string', 'min:6', 'max:30', 'unique:proveedores,cedula'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'finca' => ['nullable', 'string', 'max:120'],
            'litros_prom' => ['required', 'integer', 'min:0'],
            'precio_litro' => ['required', 'numeric', 'min:0.01'],
            'ruta_id' => ['nullable', 'integer', 'exists:rutas,id'],
        ];
    }
}
