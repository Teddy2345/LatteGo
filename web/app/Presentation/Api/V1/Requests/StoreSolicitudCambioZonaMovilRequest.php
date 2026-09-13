<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSolicitudCambioZonaMovilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ruta_solicitada_id' => ['required', 'integer', 'exists:rutas,id'],
            'fecha_cambio' => ['required', 'date'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }
}
