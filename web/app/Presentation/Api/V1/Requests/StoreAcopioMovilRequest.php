<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Requests;

use App\Domain\Acopio\ValueObjects\UbicacionCaptura;
use Illuminate\Foundation\Http\FormRequest;

final class StoreAcopioMovilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_id' => ['required', 'string', 'max:64'],
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'ruta_id' => ['nullable', 'integer', 'exists:rutas,id'],
            'fecha' => ['required', 'date'],
            'cantidad_litros' => ['required', 'numeric', 'min:0.01'],
            // La merma se descuenta de lo recibido, asi que nunca puede superarlo.
            'perdida_litros' => ['nullable', 'numeric', 'min:0', 'lte:cantidad_litros'],
            'motivo_perdida' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
            'precision_m' => ['required', 'numeric', 'gt:0', 'max:'.UbicacionCaptura::PRECISION_MAXIMA_METROS],
            'capturado_en' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'perdida_litros.lte' => 'La perdida no puede superar los litros recibidos.',
            'precision_m.max' => 'La ubicacion es poco precisa. Captura el punto otra vez al aire libre.',
        ];
    }
}
