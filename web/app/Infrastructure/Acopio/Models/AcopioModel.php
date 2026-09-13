<?php

declare(strict_types=1);

namespace App\Infrastructure\Acopio\Models;

use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Infrastructure\Proveedor\Models\RutaModel;
use App\Models\User;
use Database\Factories\AcopioModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class AcopioModel extends Model
{
    /** @use HasFactory<AcopioModelFactory> */
    use HasFactory;
    use LogsActivity;

    protected $table = 'acopios';

    protected $fillable = [
        'proveedor_id',
        'acopiador_id',
        'ruta_id',
        'movilidad_id',
        'fecha',
        'cantidad_litros',
        'estado',
        'perdida_litros',
        'motivo_perdida',
        'observaciones',
        'latitud',
        'longitud',
        'precision_m',
        'capturado_en',
        'request_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'cantidad_litros' => 'decimal:2',
            'perdida_litros' => 'decimal:2',
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
            'precision_m' => 'decimal:2',
            'capturado_en' => 'datetime',
        ];
    }

    protected static function newFactory(): AcopioModelFactory
    {
        return AcopioModelFactory::new();
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(ProveedorModel::class, 'proveedor_id');
    }

    public function acopiador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acopiador_id');
    }

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(RutaModel::class, 'ruta_id');
    }

    public function movilidad(): BelongsTo
    {
        return $this->belongsTo(\App\Infrastructure\Movilidad\Models\MovilidadModel::class, 'movilidad_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('acopio');
    }
}
