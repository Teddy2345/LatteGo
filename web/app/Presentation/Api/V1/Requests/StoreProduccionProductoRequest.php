<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProduccionProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_id' => ['nullable', 'string', 'max:64'],
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'fecha' => ['required', 'date'],
            'cantidad' => ['required', 'numeric', 'min:0.01'],
            'litros_procesados' => ['required', 'numeric', 'min:0.01'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
