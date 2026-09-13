<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Historial de solicitudes de cambio de zona de un proveedor. Se
     * conserva completo (aprobadas y rechazadas incluidas): es a la vez la
     * cola de aprobacion y el historial de cambios de zona.
     */
    public function up(): void
    {
        Schema::create('solicitudes_cambio_zona', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('ruta_actual_id')->nullable()->constrained('rutas')->nullOnDelete();
            $table->foreignId('ruta_solicitada_id')->constrained('rutas')->restrictOnDelete();
            $table->date('fecha_cambio');
            $table->text('motivo')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->foreignId('solicitado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_revision')->nullable();
            $table->text('observacion_revision')->nullable();
            $table->timestamps();

            $table->index(['proveedor_id', 'estado']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_cambio_zona');
    }
};
