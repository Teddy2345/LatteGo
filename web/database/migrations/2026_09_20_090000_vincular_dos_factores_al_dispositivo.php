<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La vinculacion pasa a colgar del telefono y no de la sesion.
 *
 * Antes colgaba del token de Sanctum, que se renueva en cada inicio de
 * sesion y se borraba en cascada al cerrarla: el telefono perdia la
 * vinculacion y volvia a pedir el codigo para vincular una y otra vez.
 * Ahora la app manda un identificador propio que guarda una sola vez, asi
 * que volver a entrar con la misma cuenta en el mismo telefono conserva la
 * vinculacion. El token queda solo como referencia de la ultima sesion.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('dispositivos_dos_factores', function (Blueprint $table) {
            $table->string('dispositivo_id', 64)->nullable()->after('user_id');
        });

        // El indice unico de token_id sostiene su clave foranea: hay que
        // soltar la foranea antes de poder retirarlo.
        Schema::table('dispositivos_dos_factores', function (Blueprint $table) {
            $table->dropForeign(['token_id']);
            $table->dropUnique(['token_id']);
        });

        Schema::table('dispositivos_dos_factores', function (Blueprint $table) {
            $table->foreignId('token_id')->nullable()->change();
        });

        Schema::table('dispositivos_dos_factores', function (Blueprint $table) {
            // Cerrar sesion ya no borra la vinculacion: solo la deja sin
            // token de referencia hasta el siguiente inicio de sesion.
            $table->foreign('token_id')->references('id')->on('personal_access_tokens')->nullOnDelete();
            $table->unique(['user_id', 'dispositivo_id']);
        });
    }

    public function down(): void
    {
        Schema::table('dispositivos_dos_factores', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'dispositivo_id']);
            $table->dropForeign(['token_id']);
        });

        // Sin token no se puede volver al esquema anterior, que lo exigia.
        DB::table('dispositivos_dos_factores')->whereNull('token_id')->delete();

        Schema::table('dispositivos_dos_factores', function (Blueprint $table) {
            $table->foreignId('token_id')->nullable(false)->change();
        });

        Schema::table('dispositivos_dos_factores', function (Blueprint $table) {
            $table->unique('token_id');
            $table->foreign('token_id')->references('id')->on('personal_access_tokens')->cascadeOnDelete();
            $table->dropColumn('dispositivo_id');
        });
    }
};
