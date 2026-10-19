<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // El personal entra con su correo; el proveedor, con su cedula.
            'email' => ['required_without:cedula', 'nullable', 'email'],
            'cedula' => ['required_without:email', 'nullable', 'string', 'max:30'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ];
    }
}
