<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'finca' => ['nullable', 'string', 'max:120'],
            'litros_prom' => ['required', 'integer', 'min:0'],
            'precio_litro' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
