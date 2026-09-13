<?php

declare(strict_types=1);

namespace App\Application\Pedidos\DTOs;

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
    ) {
    }
}
