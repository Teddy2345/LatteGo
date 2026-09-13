<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Registra que el acopiador visito a un proveedor y no pudo recoger
     * leche, sin forzar un Acopio con 0 litros (CantidadLitros exige > 0).
     * Separado de Acopio: uno es "leche recibida", esto es "incidencia del
     * recorrido".
     */
    public function up(): void
    {
        Schema::create('incidencias_recorrido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('movilidad_id')->nullable()->constrained('movilidades')->nullOnDelete();
            $table->date('fecha');
            $table->string('tipo', 20);
            $table->text('motivo')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['proveedor_id', 'fecha']);
            $table->index(['movilidad_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidencias_recorrido');
    }
};
