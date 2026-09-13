<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Orden de dependencias: roles/permisos y usuarios primero; luego
     * rutas -> proveedores -> acopios -> calidad -> produccion/despacho;
     * pagos al final porque calcula planillas sobre los acopios ya
     * sembrados.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            RutaSeeder::class,
            ProveedorSeeder::class,
            MovilidadSeeder::class,
            AcopioSeeder::class,
            CalidadSeeder::class,
            ProduccionSeeder::class,
            PagoSeeder::class,
            InventarioSeeder::class,
        ]);
    }
}
