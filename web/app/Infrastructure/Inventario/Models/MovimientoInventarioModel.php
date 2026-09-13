<?php

declare(strict_types=1);

namespace App\Infrastructure\Inventario\Models;

use App\Models\User;
use Database\Factories\MovimientoInventarioModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class MovimientoInventarioModel extends Model
{
    /** @use HasFactory<MovimientoInventarioModelFactory> */
    use HasFactory;
    use LogsActivity;

    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'producto_id',
        'produccion_id',
        'tipo',
        'fecha',
        'cantidad',
        'lote',
        'litros_procesados',
        'precio_unitario',
        'total',
        'cliente',
        'observaciones',
        'usuario_id',
        'request_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'cantidad' => 'decimal:2',
            'litros_procesados' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    protected static function newFactory(): MovimientoInventarioModelFactory
    {
        return MovimientoInventarioModelFactory::new();
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoModel::class, 'producto_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('inventario');
    }
}
