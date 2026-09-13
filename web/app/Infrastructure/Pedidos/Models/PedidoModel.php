<?php

declare(strict_types=1);

namespace App\Infrastructure\Pedidos\Models;

use App\Models\User;
use Database\Factories\PedidoModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class PedidoModel extends Model
{
    /** @use HasFactory<PedidoModelFactory> */
    use HasFactory;
    use LogsActivity;

    protected $table = 'pedidos';

    protected $fillable = [
        'cliente_nombre',
        'cliente_telefono',
        'cliente_direccion',
        'fecha',
        'estado',
        'observaciones',
        'repartidor_id',
        'metodo_pago',
        'monto_cobrado',
        'fecha_entrega',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'fecha_entrega' => 'date',
            'monto_cobrado' => 'decimal:2',
        ];
    }

    protected static function newFactory(): PedidoModelFactory
    {
        return PedidoModelFactory::new();
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItemPedidoModel::class, 'pedido_id');
    }

    public function repartidor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'repartidor_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('pedido');
    }
}
