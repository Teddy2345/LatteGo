<?php

declare(strict_types=1);

namespace App\Infrastructure\Proveedor\Models;

use Database\Factories\ProveedorModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class ProveedorModel extends Model
{
    /** @use HasFactory<ProveedorModelFactory> */
    use HasFactory;
    use LogsActivity;

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'cedula',
        'telefono',
        'finca',
        'litros_prom',
        'precio_litro',
        'activo',
        'ruta_id',
    ];

    protected function casts(): array
    {
        return [
            'litros_prom' => 'integer',
            'precio_litro' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    protected static function newFactory(): ProveedorModelFactory
    {
        return ProveedorModelFactory::new();
    }

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(RutaModel::class, 'ruta_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('proveedor');
    }
}
