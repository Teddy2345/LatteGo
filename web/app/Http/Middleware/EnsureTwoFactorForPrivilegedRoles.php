<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige 2FA confirmado para los roles admin y supervisor (seccion 4 del
 * spec: "Activa 2FA con Fortify solo para roles admin y supervisor").
 */
final class EnsureTwoFactorForPrivilegedRoles
{
    private const ROLES_CON_2FA_OBLIGATORIO = ['admin', 'supervisor'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $request->routeIs('dos-factores.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        $requiere2fa = $user->hasAnyRole(self::ROLES_CON_2FA_OBLIGATORIO);

        if ($requiere2fa && $user->two_factor_confirmed_at === null) {
            return redirect()->route('dos-factores.setup');
        }

        return $next($request);
    }
}
