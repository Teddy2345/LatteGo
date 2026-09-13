<?php

declare(strict_types=1);

namespace App\Infrastructure\Inventario\Models;

use Database\Factories\ProductoModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class ProductoModel extends Model
{
    /** @use HasFactory<ProductoModelFactory> */
    use HasFactory;
    use LogsActivity;

    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'tipo',
        'categoria',
        'foto_path',
        'descripcion',
        'unidad',
        'precio_referencia',
        'stock_minimo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio_referencia' => 'decimal:2',
            'stock_minimo' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    protected static function newFactory(): ProductoModelFactory
    {
        return ProductoModelFactory::new();
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventarioModel::class, 'producto_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('producto');
    }
}
