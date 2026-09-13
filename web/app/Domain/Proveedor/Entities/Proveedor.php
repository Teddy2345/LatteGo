<?php

declare(strict_types=1);

namespace App\Domain\Proveedor\Entities;

use App\Domain\Proveedor\Exceptions\FincaInvalidaException;
use App\Domain\Proveedor\Exceptions\TelefonoInvalidoException;
use App\Domain\Proveedor\ValueObjects\Cedula;
use App\Domain\Proveedor\ValueObjects\LitrosPromedio;
use App\Domain\Proveedor\ValueObjects\Nombre;
use App\Domain\Proveedor\ValueObjects\PrecioLitro;

final class Proveedor
{
    private function __construct(
        public readonly ?int $id,
        public readonly Nombre $nombre,
        public readonly Cedula $cedula,
        public readonly ?string $telefono,
        public readonly ?string $finca,
        public readonly LitrosPromedio $litrosProm,
        public readonly PrecioLitro $precioLitro,
        public readonly bool $activo,
        public readonly ?int $rutaId,
    ) {
    }

    public static function crear(
        Nombre $nombre,
        Cedula $cedula,
        ?string $telefono,
        ?string $finca,
        LitrosPromedio $litrosProm,
        PrecioLitro $precioLitro,
        ?int $rutaId,
    ): self {
        self::validarTelefono($telefono);
        self::validarFinca($finca);

        return new self(
            id: null,
            nombre: $nombre,
            cedula: $cedula,
            telefono: $telefono,
            finca: $finca,
            litrosProm: $litrosProm,
            precioLitro: $precioLitro,
            activo: true,
            rutaId: $rutaId,
        );
    }

    public static function reconstituir(
        int $id,
        Nombre $nombre,
        Cedula $cedula,
        ?string $telefono,
        ?string $finca,
        LitrosPromedio $litrosProm,
        PrecioLitro $precioLitro,
        bool $activo,
        ?int $rutaId,
    ): self {
        return new self($id, $nombre, $cedula, $telefono, $finca, $litrosProm, $precioLitro, $activo, $rutaId);
    }

    public function actualizarDatos(
        Nombre $nombre,
        ?string $telefono,
        ?string $finca,
        LitrosPromedio $litrosProm,
        PrecioLitro $precioLitro,
    ): self {
        self::validarTelefono($telefono);
        self::validarFinca($finca);

        return new self(
            id: $this->id,
            nombre: $nombre,
            cedula: $this->cedula,
            telefono: $telefono,
            finca: $finca,
            litrosProm: $litrosProm,
            precioLitro: $precioLitro,
            activo: $this->activo,
            rutaId: $this->rutaId,
        );
    }

    /**
     * Reasigna al proveedor a otra ruta de acopio. Los registros de Acopio
     * ya existentes no se ven afectados: el historial queda intacto porque
     * Acopio referencia al proveedor por id, no por ruta.
     */
    public function reasignarRuta(?int $rutaId): self
    {
        return new self(
            id: $this->id,
            nombre: $this->nombre,
            cedula: $this->cedula,
            telefono: $this->telefono,
            finca: $this->finca,
            litrosProm: $this->litrosProm,
            precioLitro: $this->precioLitro,
            activo: $this->activo,
            rutaId: $rutaId,
        );
    }

    /**
     * Cambia lo que se le paga por litro. Las planillas ya generadas guardan
     * su propio precio, asi que un cambio de temporada no reescribe pagos
     * anteriores: solo rige de la siguiente planilla en adelante.
     */
    public function cambiarPrecioLitro(PrecioLitro $precioLitro): self
    {
        return new self(
            id: $this->id,
            nombre: $this->nombre,
            cedula: $this->cedula,
            telefono: $this->telefono,
            finca: $this->finca,
            litrosProm: $this->litrosProm,
            precioLitro: $precioLitro,
            activo: $this->activo,
            rutaId: $this->rutaId,
        );
    }

    public function activar(): self
    {
        return $this->conEstado(true);
    }

    public function desactivar(): self
    {
        return $this->conEstado(false);
    }

    private function conEstado(bool $activo): self
    {
        return new self(
            id: $this->id,
            nombre: $this->nombre,
            cedula: $this->cedula,
            telefono: $this->telefono,
            finca: $this->finca,
            litrosProm: $this->litrosProm,
            precioLitro: $this->precioLitro,
            activo: $activo,
            rutaId: $this->rutaId,
        );
    }

    private static function validarTelefono(?string $telefono): void
    {
        if ($telefono !== null && mb_strlen($telefono) > 20) {
            throw new TelefonoInvalidoException('El telefono no puede superar los 20 caracteres.');
        }
    }

    private static function validarFinca(?string $finca): void
    {
        if ($finca !== null && mb_strlen($finca) > 120) {
            throw new FincaInvalidaException('El nombre de la finca no puede superar los 120 caracteres.');
        }
    }
}
