<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Resources;

use App\Application\Auditoria\DTOs\RegistroActividadData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RegistroActividadData
 */
final class RegistroActividadResource extends JsonResource
{
    /**
     * activitylog guarda descripciones tecnicas ("created", "updated"). En la
     * pantalla de auditoria se muestra una frase legible en su lugar.
     */
    private const MODULOS = [
        'acopio' => 'Acopio',
        'proveedor' => 'Proveedor',
        'calidad' => 'Analisis de calidad',
        'produccion' => 'Produccion',
        'despacho' => 'Despacho',
        'pago' => 'Pago',
    ];

    private const EVENTOS = [
        'created' => 'registrado',
        'updated' => 'actualizado',
        'deleted' => 'eliminado',
        'restored' => 'restaurado',
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->titulo(),
            'modulo' => self::MODULOS[$this->modulo] ?? ucfirst($this->modulo),
            'evento' => $this->evento,
            'usuario' => $this->usuario ?? 'Sistema',
            'fecha' => $this->ocurridoEn,
        ];
    }

    private function titulo(): string
    {
        $modulo = self::MODULOS[$this->modulo] ?? ucfirst($this->modulo);
        $evento = self::EVENTOS[$this->evento ?? ''] ?? null;

        return $evento === null ? $modulo.': '.$this->descripcion : $modulo.' '.$evento;
    }
}
