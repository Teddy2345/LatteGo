<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('acopios', function (Blueprint $table) {
            $table->foreignId('movilidad_id')->nullable()->after('ruta_id')
                ->constrained('movilidades')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('acopios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('movilidad_id');
        });
    }
};
