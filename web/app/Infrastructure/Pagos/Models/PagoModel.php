<?php

declare(strict_types=1);

namespace App\Infrastructure\Pagos\Models;

use App\Infrastructure\Proveedor\Models\ProveedorModel;
use Database\Factories\PagoModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class PagoModel extends Model
{
    /** @use HasFactory<PagoModelFactory> */
    use HasFactory;
    use LogsActivity;

    protected $table = 'pagos';

    protected $fillable = [
        'proveedor_id',
        'semana_inicio',
        'semana_fin',
        'total_litros',
        'precio_litro',
        'total_pagar',
        'fecha_pago',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'semana_inicio' => 'date',
            'semana_fin' => 'date',
            'fecha_pago' => 'date',
            'total_litros' => 'decimal:2',
            'precio_litro' => 'decimal:2',
            'total_pagar' => 'decimal:2',
        ];
    }

    protected static function newFactory(): PagoModelFactory
    {
        return PagoModelFactory::new();
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(ProveedorModel::class, 'proveedor_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('pago');
    }
}
