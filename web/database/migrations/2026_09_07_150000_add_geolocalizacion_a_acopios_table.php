<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('acopios', function (Blueprint $table) {
            $table->decimal('latitud', 10, 7)->nullable()->after('observaciones');
            $table->decimal('longitud', 10, 7)->nullable()->after('latitud');
            $table->decimal('precision_m', 7, 2)->nullable()->after('longitud');
            $table->timestamp('capturado_en')->nullable()->after('precision_m');

            // Clave de idempotencia enviada por la app movil: permite reintentar
            // el envio de un acopio encolado sin conexion sin duplicar el registro.
            $table->string('request_id', 64)->nullable()->unique()->after('capturado_en');
        });
    }

    public function down(): void
    {
        Schema::table('acopios', function (Blueprint $table) {
            $table->dropUnique(['request_id']);
            $table->dropColumn(['latitud', 'longitud', 'precision_m', 'capturado_en', 'request_id']);
        });
    }
};
