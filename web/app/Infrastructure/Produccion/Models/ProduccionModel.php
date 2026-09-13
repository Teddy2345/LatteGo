<?php

declare(strict_types=1);

namespace App\Infrastructure\Produccion\Models;

use App\Models\User;
use Database\Factories\ProduccionModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class ProduccionModel extends Model
{
    /** @use HasFactory<ProduccionModelFactory> */
    use HasFactory;
    use LogsActivity;

    protected $table = 'produccion';

    protected $fillable = [
        'fecha',
        'litros_procesados',
        'quesos_producidos',
        'producto_id',
        'rendimiento_porcentaje',
        'jefa_produccion_id',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'litros_procesados' => 'decimal:2',
            'rendimiento_porcentaje' => 'decimal:2',
        ];
    }

    protected static function newFactory(): ProduccionModelFactory
    {
        return ProduccionModelFactory::new();
    }

    public function jefaProduccion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'jefa_produccion_id');
    }

    public function despachos(): HasMany
    {
        return $this->hasMany(DespachoModel::class, 'produccion_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(\App\Infrastructure\Inventario\Models\ProductoModel::class, 'producto_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('produccion');
    }
}
