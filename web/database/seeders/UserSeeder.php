<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Un usuario por rol. Los acopiadores no estan aqui: son cinco personas
     * reales, cada una con su movilidad y su ruta, y las crea MovilidadSeeder
     * junto con la movilidad de la que cada una es titular.
     */
    private const USUARIOS = [
        ['name' => 'Administrador', 'email' => 'admin@ecolactea.test', 'role' => 'admin'],
        ['name' => 'Supervisor de Planta', 'email' => 'supervisor@ecolactea.test', 'role' => 'supervisor'],
        ['name' => 'Tecnico de Calidad', 'email' => 'calidad@ecolactea.test', 'role' => 'calidad'],
        ['name' => 'Operador de Produccion', 'email' => 'produccion@ecolactea.test', 'role' => 'produccion'],
        ['name' => 'Encargada de Ventas', 'email' => 'ventas@ecolactea.test', 'role' => 'ventas'],
        ['name' => 'Encargado de Almacén', 'email' => 'almacen@ecolactea.test', 'role' => 'almacen'],
        ['name' => 'Repartidor de Planta', 'email' => 'repartidor@ecolactea.test', 'role' => 'repartidor'],
    ];

    public function run(): void
    {
        foreach (self::USUARIOS as $datos) {
            $user = User::query()->firstOrCreate(
                ['email' => $datos['email']],
                ['name' => $datos['name'], 'password' => Hash::make('password')],
            );

            $user->syncRoles([$datos['role']]);
        }
    }
}
