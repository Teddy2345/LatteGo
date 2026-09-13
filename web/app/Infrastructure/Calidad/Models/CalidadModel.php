<?php

declare(strict_types=1);

namespace App\Infrastructure\Calidad\Models;

use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use Database\Factories\CalidadModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class CalidadModel extends Model
{
    /** @use HasFactory<CalidadModelFactory> */
    use HasFactory;
    use LogsActivity;

    protected $table = 'analisis_calidad';

    protected $fillable = [
        'proveedor_id',
        'acopio_id',
        'fecha',
        'temperatura',
        'grasa',
        'solidos_no_grasos',
        'densidad',
        'proteina',
        'lactosa',
        'sales',
        'agua_agregada',
        'ph',
        'prueba_alcohol',
        'resultado',
        'motivo_rechazo',
        'sancion_aplicada',
        'foto_path',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'temperatura' => 'decimal:2',
            'grasa' => 'decimal:2',
            'solidos_no_grasos' => 'decimal:2',
            'densidad' => 'decimal:2',
            'proteina' => 'decimal:2',
            'lactosa' => 'decimal:2',
            'sales' => 'decimal:2',
            'agua_agregada' => 'decimal:2',
            'ph' => 'decimal:2',
        ];
    }

    protected static function newFactory(): CalidadModelFactory
    {
        return CalidadModelFactory::new();
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(ProveedorModel::class, 'proveedor_id');
    }

    public function acopio(): BelongsTo
    {
        return $this->belongsTo(AcopioModel::class, 'acopio_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('calidad');
    }
}
