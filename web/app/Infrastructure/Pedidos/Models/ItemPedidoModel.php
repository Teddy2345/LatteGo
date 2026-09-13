<?php

declare(strict_types=1);

namespace App\Infrastructure\Pedidos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ItemPedidoModel extends Model
{
    protected $table = 'items_pedido';

    protected $fillable = [
        'pedido_id',
        'producto_id',
        'nombre_producto',
        'cantidad',
        'precio_unitario',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
        ];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoModel::class, 'pedido_id');
    }
}
