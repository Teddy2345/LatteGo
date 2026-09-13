<?php

declare(strict_types=1);

namespace App\Infrastructure\Movilidad\Models;

use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class IncidenciaRecorridoModel extends Model
{
    use LogsActivity;

    protected $table = 'incidencias_recorrido';

    protected $fillable = [
        'proveedor_id',
        'movilidad_id',
        'fecha',
        'tipo',
        'motivo',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(ProveedorModel::class, 'proveedor_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('incidencia_recorrido');
    }
}
