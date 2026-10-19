<?php

declare(strict_types=1);

namespace App\Application\Inventario\DTOs;

use App\Domain\Inventario\ValueObjects\TipoCliente;

final readonly class RegistrarVentaData
{
    public function __construct(
        public int $productoId,
        public string $fecha,
        public float $cantidad,
        public float $precioUnitario,
        public ?string $cliente,
        public ?int $usuarioId,
        public ?string $requestId = null,
        public TipoCliente $tipoCliente = TipoCliente::Externo,
    ) {
    }
}
