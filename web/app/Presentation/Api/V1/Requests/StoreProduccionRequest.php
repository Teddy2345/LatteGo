<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProduccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date'],
            'litros_procesados' => ['required', 'numeric', 'min:0.01'],
            'quesos_producidos' => ['required', 'integer', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
