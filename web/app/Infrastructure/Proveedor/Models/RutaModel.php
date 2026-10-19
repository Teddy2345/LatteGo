<?php

declare(strict_types=1);

namespace App\Infrastructure\Proveedor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RutaModel extends Model
{
    protected $table = 'rutas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'paradas_referencia',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'paradas_referencia' => 'array',
        ];
    }

    public function proveedores(): HasMany
    {
        return $this->hasMany(ProveedorModel::class, 'ruta_id');
    }
}
