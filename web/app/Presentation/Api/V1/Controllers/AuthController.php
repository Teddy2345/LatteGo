<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Models\User;
use App\Presentation\Api\V1\Requests\LoginRequest;
use App\Presentation\Api\V1\Support\AccionesMoviles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    /**
     * Emite un token personal por dispositivo (app movil Android). No
     * usa cookies de sesion: cada dispositivo recibe su propio token.
     */
    /**
     * El proveedor no tiene correo: se le busca por la cedula de su ficha,
     * que es el dato con el que la planta ya lo identifica.
     */
    private function usuarioDe(LoginRequest $request): ?User
    {
        $cedula = trim($request->string('cedula')->toString());

        if ($cedula !== '') {
            $userId = ProveedorModel::query()->where('cedula', $cedula)->value('user_id');

            return $userId === null ? null : User::query()->find($userId);
        }

        return User::query()->where('email', $request->string('email')->toString())->first();
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->usuarioDe($request);

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $token = $user->createToken($request->string('device_name')->toString());

        return response()->json([
            'data' => [
                'token' => $token->plainTextToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'perfil' => AccionesMoviles::perfil($user),
                    'acciones' => AccionesMoviles::para($user),
                ],
            ],
        ], 201);
    }

    /**
     * Revoca unicamente el token usado en esta peticion (el dispositivo
     * actual), sin afectar la sesion de otros dispositivos del usuario.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesion cerrada.']);
    }
}
