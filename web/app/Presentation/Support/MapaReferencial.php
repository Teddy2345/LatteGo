<?php

declare(strict_types=1);

namespace App\Presentation\Support;

/**
 * Enlaces de Google Maps para el recorrido de referencia de una ruta: sale
 * de la planta y pasa por sus paradas en orden. No usa clave de API: el mapa
 * incrustado es el embed publico de Google y el enlace abre la app o la web
 * de Google Maps con todas las paradas.
 */
final class MapaReferencial
{
    /**
     * @param  string[]  $paradas
     */
    public static function embed(array $paradas): string
    {
        $origen = self::origen();

        if ($paradas === []) {
            return 'https://maps.google.com/maps?'.http_build_query(['q' => $origen, 'z' => 14, 'output' => 'embed']);
        }

        return 'https://maps.google.com/maps?'.http_build_query([
            'saddr' => $origen,
            'daddr' => implode(' to:', $paradas),
            'output' => 'embed',
        ]);
    }

    /**
     * @param  string[]  $paradas
     */
    public static function enlace(array $paradas): string
    {
        $origen = self::origen();

        if ($paradas === []) {
            return 'https://www.google.com/maps/search/?'.http_build_query(['api' => 1, 'query' => $origen]);
        }

        $destino = $paradas[count($paradas) - 1];
        $intermedias = array_slice($paradas, 0, -1);

        return 'https://www.google.com/maps/dir/?'.http_build_query(array_filter([
            'api' => 1,
            'origin' => $origen,
            'destination' => $destino,
            'waypoints' => $intermedias === [] ? null : implode('|', $intermedias),
            'travelmode' => 'driving',
        ], static fn ($valor): bool => $valor !== null));
    }

    private static function origen(): string
    {
        return (string) config('ecolactea.planta.ubicacion');
    }
}
