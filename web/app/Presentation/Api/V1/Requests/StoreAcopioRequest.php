<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreAcopioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'ruta_id' => ['nullable', 'integer', 'exists:rutas,id'],
            'fecha' => ['required', 'date'],
            'cantidad_litros' => ['required', 'numeric', 'min:0.01'],
            'perdida_litros' => ['nullable', 'numeric', 'min:0'],
            'motivo_perdida' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
