<?php

declare(strict_types=1);

namespace App\Domain\Movilidad\Entities;

use App\Domain\Movilidad\ValueObjects\TipoMovilidad;

final class Movilidad
{
    /**
     * @param  array<int, array{id: int, nombre: string}>  $personas
     */
    private function __construct(
        public readonly ?int $id,
        public readonly string $nombre,
        public readonly TipoMovilidad $tipo,
        public readonly ?int $rutaId,
        public readonly bool $activa,
        public readonly array $personas,
        public readonly ?int $usuarioId,
    ) {
    }

    public static function crear(string $nombre, TipoMovilidad $tipo, ?int $rutaId): self
    {
        return new self(id: null, nombre: $nombre, tipo: $tipo, rutaId: $rutaId, activa: true, personas: [], usuarioId: null);
    }

    /**
     * @param  array<int, array{id: int, nombre: string}>  $personas
     */
    public static function reconstituir(
        int $id,
        string $nombre,
        TipoMovilidad $tipo,
        ?int $rutaId,
        bool $activa,
        array $personas,
        ?int $usuarioId = null,
    ): self {
        return new self($id, $nombre, $tipo, $rutaId, $activa, $personas, $usuarioId);
    }

    public function renombrar(string $nombre): self
    {
        return new self($this->id, $nombre, $this->tipo, $this->rutaId, $this->activa, $this->personas, $this->usuarioId);
    }

    /** El camion activo es el que hoy esta ejecutando el recorrido de su ruta. */
    public function activar(): self
    {
        return new self($this->id, $this->nombre, $this->tipo, $this->rutaId, true, $this->personas, $this->usuarioId);
    }

    public function desactivar(): self
    {
        return new self($this->id, $this->nombre, $this->tipo, $this->rutaId, false, $this->personas, $this->usuarioId);
    }

    /**
     * Asigna al acopiador titular de esta movilidad: la persona real que
     * hace este recorrido y a quien pertenecen sus proveedores. Null quita
     * la asignacion (movilidad sin acopiador fijo todavia).
     */
    public function asignarUsuario(?int $usuarioId): self
    {
        return new self($this->id, $this->nombre, $this->tipo, $this->rutaId, $this->activa, $this->personas, $usuarioId);
    }
}
