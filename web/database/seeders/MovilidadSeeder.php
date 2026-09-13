<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Movilidad\Models\MovilidadModel;
use App\Infrastructure\Proveedor\Models\RutaModel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MovilidadSeeder extends Seeder
{
    /**
     * Las 5 movilidades reales de la planta: 3 camiones, 1 motocarga y la
     * recepcion directa en planta, cada una con su propia ruta y su propio
     * acopiador titular. Nombres de contacto de marcador de posicion: se
     * pueden renombrar despues, uno por uno, cuando el cliente confirme los
     * nombres reales de cada persona.
     */
    private const CAMIONES = [
        [
            'nombre' => 'Camión 01', 'ruta' => 'Huata Centro', 'tipo' => 'camion',
            'usuario' => ['name' => 'Acopiador Camión 01', 'email' => 'camion1@ecolactea.test'],
        ],
        [
            'nombre' => 'Camión 02', 'ruta' => 'Achacachi', 'tipo' => 'camion',
            'usuario' => ['name' => 'Acopiador Camión 02', 'email' => 'camion2@ecolactea.test'],
        ],
        [
            'nombre' => 'Camión 03', 'ruta' => 'Copacabana', 'tipo' => 'camion',
            'usuario' => ['name' => 'Acopiador Camión 03', 'email' => 'camion3@ecolactea.test'],
        ],
        [
            'nombre' => 'Motocarga', 'ruta' => 'Puerto Perez', 'tipo' => 'motocarga',
            'usuario' => ['name' => 'Acopiador Motocarga', 'email' => 'motocarga@ecolactea.test'],
        ],
    ];

    public function run(): void
    {
        foreach (self::CAMIONES as $datos) {
            $ruta = RutaModel::query()->firstOrCreate(['nombre' => $datos['ruta']]);
            $usuario = $this->usuarioAcopiador($datos['usuario']);

            MovilidadModel::query()->updateOrCreate(
                ['nombre' => $datos['nombre']],
                ['tipo' => $datos['tipo'], 'ruta_id' => $ruta->id, 'activa' => true, 'usuario_id' => $usuario->id],
            );
        }

        $rutaPlanta = RutaModel::query()->firstOrCreate(['nombre' => 'Entrega directa en planta']);
        $usuarioPlanta = $this->usuarioAcopiador([
            'name' => 'Encargado de Recepción en Planta',
            'email' => 'recepcion@ecolactea.test',
        ]);

        MovilidadModel::query()->updateOrCreate(
            ['nombre' => 'Entrega directa en planta'],
            ['tipo' => 'planta', 'ruta_id' => $rutaPlanta->id, 'activa' => true, 'usuario_id' => $usuarioPlanta->id],
        );
    }

    /**
     * @param  array{name: string, email: string}  $datos
     */
    private function usuarioAcopiador(array $datos): User
    {
        $usuario = User::query()->firstOrCreate(
            ['email' => $datos['email']],
            ['name' => $datos['name'], 'password' => Hash::make('password')],
        );

        $usuario->syncRoles(['acopiador']);

        return $usuario;
    }
}
