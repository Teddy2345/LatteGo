<?php

declare(strict_types=1);

namespace App\Domain\Auditoria\Entities;

use DateTimeImmutable;

/**
 * Una linea del historial de actividad del sistema: quien hizo que, sobre
 * que modulo y cuando. Es un registro de solo lectura; la auditoria nunca
 * se edita ni se borra desde la aplicacion.
 */
final readonly class RegistroActividad
{
    public function __construct(
        public int $id,
        public string $modulo,
        public string $descripcion,
        public ?string $evento,
        public ?string $usuario,
        public DateTimeImmutable $ocurridoEn,
    ) {
    }
}
