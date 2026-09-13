<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreVentaRequest extends FormRequest
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
            'precio_unitario' => ['required', 'numeric', 'min:0.01'],
            'cliente' => ['nullable', 'string', 'max:160'],
        ];
    }
}
