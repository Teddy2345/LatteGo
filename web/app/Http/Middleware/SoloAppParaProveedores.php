<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * La cuenta del proveedor es solo para su app.
 *
 * Tiene correo y clave porque la tabla de usuarios los exige, asi que sin
 * esto podria entrar al portal de la planta y quedarse mirando pantallas
 * que no le corresponden. Se le cierra la sesion y se le explica por que.
 */
final class SoloAppParaProveedores
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->hasRole('proveedor')) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta de proveedor es para la app de Ecolactea, no para esta página.',
            ]);
        }

        return $next($request);
    }
}
