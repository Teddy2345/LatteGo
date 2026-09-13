<?php

declare(strict_types=1);

namespace App\Domain\Notificacion\Entities;

use App\Domain\Notificacion\ValueObjects\NivelNotificacion;
use DateTimeImmutable;

/**
 * Una alerta del sistema. "datos" lleva el detalle propio de cada tipo
 * (por ejemplo, para 'calidad_rechazo': proveedor, resultado, agua
 * añadida, sancion) sin forzar una columna por caso de uso: cada pantalla
 * que la consume sabe que claves esperar segun el tipo.
 */
final readonly class Notificacion
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public function __construct(
        public ?int $id,
        public string $tipo,
        public string $titulo,
        public string $mensaje,
        public NivelNotificacion $nivel,
        public array $datos,
        public ?DateTimeImmutable $creadaEn,
    ) {
    }
}
