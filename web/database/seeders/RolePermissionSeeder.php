<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    private const PERMISOS = [
        'proveedores.ver',
        'proveedores.crear',
        'proveedores.editar',
        'proveedores.eliminar',
        'acopios.registrar',
        'acopios.sincronizar',
        'calidad.registrar',
        'produccion.registrar',
        'pagos.generar',
        'reportes.ver',
        'inventario.ver',
        'inventario.registrar',
        'ventas.registrar',
        'pedidos.repartir',
    ];

    private const ROLES = [
        'admin' => self::PERMISOS,
        'supervisor' => [
            'reportes.ver',
            'proveedores.ver',
            'proveedores.crear',
            'proveedores.editar',
            'inventario.ver',
        ],
        'acopiador' => [
            'acopios.registrar',
            'acopios.sincronizar',
        ],
        'calidad' => [
            'calidad.registrar',
        ],
        'produccion' => [
            'produccion.registrar',
            'inventario.ver',
        ],
        // Comercializacion: necesita ver las existencias para poder vender,
        // pero no interviene en acopio, calidad ni produccion.
        'ventas' => [
            'ventas.registrar',
            'inventario.ver',
        ],
        // Almacen: da de alta insumos/productos y registra entradas y
        // salidas. No interviene en acopio, calidad, produccion ni ventas.
        'almacen' => [
            'inventario.ver',
            'inventario.registrar',
        ],
        // Reparto: entrega pedidos de la tienda y cobra en el momento. No
        // interviene en la confirmacion del pedido ni en el resto del
        // sistema.
        'repartidor' => [
            'pedidos.repartir',
        ],
    ];

    public function run(): void
    {
        foreach (self::PERMISOS as $permiso) {
            Permission::findOrCreate($permiso);
        }

        foreach (self::ROLES as $rol => $permisos) {
            Role::findOrCreate($rol)->syncPermissions($permisos);
        }
    }
}
