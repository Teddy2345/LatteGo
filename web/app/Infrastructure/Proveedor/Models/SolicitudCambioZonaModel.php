<?php

declare(strict_types=1);

namespace App\Infrastructure\Proveedor\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class SolicitudCambioZonaModel extends Model
{
    use LogsActivity;

    protected $table = 'solicitudes_cambio_zona';

    protected $fillable = [
        'proveedor_id',
        'ruta_actual_id',
        'ruta_solicitada_id',
        'fecha_cambio',
        'motivo',
        'estado',
        'solicitado_por',
        'revisado_por',
        'fecha_revision',
        'observacion_revision',
    ];

    protected function casts(): array
    {
        return [
            'fecha_cambio' => 'date',
            'fecha_revision' => 'datetime',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(ProveedorModel::class, 'proveedor_id');
    }

    public function rutaActual(): BelongsTo
    {
        return $this->belongsTo(RutaModel::class, 'ruta_actual_id');
    }

    public function rutaSolicitada(): BelongsTo
    {
        return $this->belongsTo(RutaModel::class, 'ruta_solicitada_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('solicitud_cambio_zona');
    }
}
