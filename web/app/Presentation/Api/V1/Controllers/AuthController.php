<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers;

use App\Http\Controllers\Controller;
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
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->toString())->first();

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
