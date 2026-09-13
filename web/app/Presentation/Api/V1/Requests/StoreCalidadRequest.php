<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCalidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'acopio_id' => ['required', 'integer', 'exists:acopios,id'],
            'fecha' => ['required', 'date'],
            'temperatura' => ['required', 'numeric'],
            'grasa' => ['required', 'numeric'],
            'solidos_no_grasos' => ['required', 'numeric'],
            'densidad' => ['required', 'numeric'],
            'proteina' => ['required', 'numeric'],
            'lactosa' => ['required', 'numeric'],
            'sales' => ['required', 'numeric'],
            'agua_agregada' => ['required', 'numeric', 'min:0'],
            'ph' => ['required', 'numeric', 'min:0', 'max:14'],
            'prueba_alcohol_aceptada' => ['required', 'boolean'],
            'motivo_rechazo_manual' => ['nullable', 'string', 'in:acidez,otro'],
        ];
    }
}
