<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('cedula', 30)->unique();
            $table->string('telefono', 20)->nullable();
            $table->string('finca', 120)->nullable();
            $table->unsignedInteger('litros_prom');
            $table->decimal('precio_litro', 8, 2);
            $table->boolean('activo')->default(true);
            $table->foreignId('ruta_id')->nullable()->constrained('rutas')->nullOnDelete();
            $table->timestamps();

            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
