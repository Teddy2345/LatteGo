<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('produccion', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->decimal('litros_procesados', 10, 2);
            $table->unsignedInteger('quesos_producidos');
            $table->decimal('rendimiento_porcentaje', 5, 2);
            $table->foreignId('jefa_produccion_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produccion');
    }
};
