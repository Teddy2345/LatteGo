<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreDespachoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'produccion_id' => ['required', 'integer', 'exists:produccion,id'],
            'quesos_recibidos' => ['required', 'integer', 'min:0'],
            'quesos_despachados' => ['required', 'integer', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
