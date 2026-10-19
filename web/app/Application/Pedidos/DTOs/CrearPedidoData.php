<?php

declare(strict_types=1);

namespace App\Application\Pedidos\DTOs;

use App\Domain\Inventario\ValueObjects\TipoCliente;
use App\Domain\Pedidos\ValueObjects\TipoEntrega;

final readonly class CrearPedidoData
{
    /**
     * @param  ItemCarritoData[]  $items
     */
    public function __construct(
        public string $clienteNombre,
        public string $clienteTelefono,
        public string $clienteDireccion,
        public string $fecha,
        public ?string $observaciones,
        public array $items,
        public ?int $usuarioId = null,
        public ?TipoEntrega $tipoEntrega = null,
        public TipoCliente $tipoCliente = TipoCliente::Externo,
    ) {
    }
}
