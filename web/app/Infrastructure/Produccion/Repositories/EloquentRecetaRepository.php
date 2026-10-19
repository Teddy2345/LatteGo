<?php

declare(strict_types=1);

namespace App\Infrastructure\Produccion\Repositories;

use App\Domain\Inventario\Repositories\MovimientoInventarioRepository;
use App\Domain\Produccion\Repositories\RecetaRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EloquentRecetaRepository implements RecetaRepository
{
    public function __construct(private MovimientoInventarioRepository $existencias)
    {
    }

    private function unidadId(string $nombre): int
    {
        DB::table('unidades_medida')->insertOrIgnore(['nombre' => $nombre, 'simbolo' => $nombre, 'created_at' => now(), 'updated_at' => now()]);
        return (int) DB::table('unidades_medida')->where('nombre', $nombre)->value('id');
    }

    public function registrarDinamico(array $datos, int $usuarioId): int
    {
        // Canonical numeric values make retries stable across JSON number/string representations.
        $datos['productos'] = array_map(static fn ($p) => [
            'productoId' => (int) $p['productoId'],
            'recetaId' => isset($p['recetaId']) ? (int) $p['recetaId'] : null,
            'cantidadPlanificada' => number_format((float) $p['cantidadPlanificada'], 2, '.', ''),
            'cantidadProducida' => number_format((float) $p['cantidadProducida'], 2, '.', ''),
        ], $datos['productos']);
        $datos['litrosProcesados'] = number_format((float) $datos['litrosProcesados'], 2, '.', '');
        $datos['observaciones'] = $datos['observaciones'] ?? '';
        $datos['insumos'] = array_map(fn ($i) => ['productoId' => (int) $i['productoId'], 'cantidad' => number_format((float) $i['cantidad'], 2, '.', '')], $datos['insumos']);
        usort($datos['insumos'], fn ($a, $b) => $a['productoId'] <=> $b['productoId']);
        ksort($datos);
        $huella = hash('sha256', json_encode($datos));
        return DB::transaction(function () use ($datos, $usuarioId, $huella) {
            DB::table('users')->where('id', $usuarioId)->lockForUpdate()->first();
            $requestId = 'lote:'.$datos['requestId'];
            $previo = DB::table('movimientos_inventario')->where('request_id', $requestId)->first();
            if ($previo) {
                $resultado = DB::table('produccion_productos')->where('movimiento_inventario_id', $previo->id)->first();
                if ((int) $previo->usuario_id !== $usuarioId || !$resultado || $resultado->huella_solicitud !== $huella) {
                    throw ValidationException::withMessages(['cantidadProducida' => 'Esta solicitud ya se guardó con otros datos. Abre Nueva producción para registrar otro lote.']);
                }
                return (int) $previo->produccion_id;
            }
            // Los productos del lote: cada uno con su receta, su unidad y lo
            // que finalmente salio. La leche y los insumos son del lote
            // entero, asi que se descuentan una sola vez mas abajo.
            $salidas = [];
            foreach ($datos['productos'] as $linea) {
                $producto = $this->producto($linea['productoId'], 'producto_terminado');
                $receta = $linea['recetaId'] === null ? null : $this->buscar($linea['recetaId']);
                $unidadId = $this->unidadId($producto->unidad);
                if ($receta && ((int) $receta['producto_id'] !== $producto->id || (int) $receta['unidad_medida_id'] !== $unidadId || !$receta['cantidad_base'])) {
                    throw ValidationException::withMessages(['productoId' => 'El producto y su unidad deben coincidir con la receta. Si cambió la unidad del catálogo, crea una nueva versión de la receta.']);
                }
                $salidas[] = ['producto' => $producto, 'receta' => $receta, 'unidadId' => $unidadId, 'linea' => $linea];
            }
            $unidades = [];
            foreach ($datos['insumos'] as $i) {
                $insumo = $this->producto($i['productoId'], 'insumo');
                $unidades[$insumo->id] = $insumo->unidad;
                // El saldo sale del repositorio de Almacen: alli los tipos de
                // movimiento se derivan del enum, asi no se desincroniza al agregar uno.
                $stock = $this->existencias->stockDe($insumo->id);
                if ((float) $i['cantidad'] > round($stock, 2)) {
                    throw ValidationException::withMessages(['insumos' => 'Stock insuficiente de '.$insumo->nombre.': disponible '.number_format($stock, 2).' '.$insumo->unidad.'.']);
                }
            }
            foreach ($salidas as $salida) {
                foreach ($salida['receta']['insumos'] ?? [] as $i) {
                    if (($unidades[$i['insumo_id']] ?? null) !== $i['unidad']) {
                        throw ValidationException::withMessages(['insumos' => 'Conserva los insumos y unidades de la receta; ajusta sus cantidades al consumo real.']);
                    }
                }
            }
            $primera = $salidas[0];
            $lote = \App\Infrastructure\Produccion\Models\ProduccionModel::create([
                'fecha' => $datos['fecha'], 'producto_id' => $primera['producto']->id, 'litros_procesados' => $datos['litrosProcesados'],
                'quesos_producidos' => 0, 'rendimiento_porcentaje' => 0,
                'jefa_produccion_id' => $usuarioId, 'observaciones' => $datos['observaciones'],
            ]);
            DB::table('produccion')->where('id', $lote->id)->update(['receta_id' => $primera['linea']['recetaId']]);
            foreach ($salidas as $indice => $salida) {
                $movimiento = \App\Infrastructure\Inventario\Models\MovimientoInventarioModel::create([
                    'producto_id' => $salida['producto']->id, 'produccion_id' => $lote->id, 'tipo' => 'produccion', 'fecha' => $datos['fecha'],
                    'cantidad' => $salida['linea']['cantidadProducida'],
                    // Los litros van en la primera linea: repetirlos por producto inflaria el rendimiento.
                    'litros_procesados' => $indice === 0 ? $datos['litrosProcesados'] : 0,
                    'usuario_id' => $usuarioId,
                    'request_id' => $indice === 0 ? $requestId : $requestId.'#'.$indice,
                    'observaciones' => 'Producción #'.$lote->id,
                ]);
                DB::table('produccion_productos')->insert([
                    'produccion_id' => $lote->id, 'producto_id' => $salida['producto']->id, 'receta_id' => $salida['linea']['recetaId'],
                    'unidad_medida_id' => $salida['unidadId'], 'cantidad_planificada' => $salida['linea']['cantidadPlanificada'], 'cantidad_producida' => $salida['linea']['cantidadProducida'],
                    'movimiento_inventario_id' => $movimiento->id, 'huella_solicitud' => $huella, 'descripcion' => $datos['observaciones'],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            foreach ($datos['insumos'] as $i) {
                \App\Infrastructure\Inventario\Models\MovimientoInventarioModel::create([
                    'producto_id' => $i['productoId'], 'produccion_id' => $lote->id, 'tipo' => 'uso_produccion',
                    'fecha' => $datos['fecha'], 'cantidad' => $i['cantidad'], 'usuario_id' => $usuarioId, 'observaciones' => 'Uso en producción #'.$lote->id,
                ]);
                DB::table('produccion_detalles')->insert(['produccion_id' => $lote->id, 'insumo_id' => $i['productoId'], 'cantidad' => $i['cantidad'], 'unidad' => $unidades[$i['productoId']]]);
            }
            activity('produccion')->causedBy($usuarioId)->performedOn($lote)->withProperties([
                'productos' => array_map(static fn ($s) => [
                    'producto_id' => $s['producto']->id, 'cantidad' => $s['linea']['cantidadProducida'],
                    'unidad' => $s['producto']->unidad, 'receta_id' => $s['linea']['recetaId'],
                ], $salidas),
            ])->log('Lote registrado y almacén actualizado');
            return $lote->id;
        }, 3);
    }

    public function listar(): array
    {
        return DB::table('recetas')->join('productos', 'productos.id', '=', 'recetas.producto_id')
            ->leftJoin('unidades_medida', 'unidades_medida.id', '=', 'recetas.unidad_medida_id')->select('recetas.*', 'productos.nombre as producto', 'unidades_medida.nombre as unidad_salida')->orderBy('recetas.nombre')->get()
            ->map(fn ($r) => (array) $r)->all();
    }
    public function buscar(int $id): array
    {
        $r = DB::table('recetas')->find($id);
        if (!$r) {
            throw ValidationException::withMessages(['recetaId' => 'La receta ya no está disponible.']);
        }
        $datos = (array) $r;
        $datos['insumos'] = DB::table('receta_detalles')->join('productos', 'productos.id', '=', 'receta_detalles.insumo_id')
            ->where('receta_id', $id)->select('insumo_id', 'cantidad', 'receta_detalles.unidad', 'productos.nombre')
            ->get()->map(fn ($i) => (array) $i)->all();
        return $datos;
    }
    private function producto(int $id, string $tipo): object
    {
        $p = DB::table('productos')->where('id', $id)->lockForUpdate()->first();
        if (!$p || !$p->activo || $p->tipo !== $tipo) {
            throw ValidationException::withMessages(['insumos' => 'Selecciona productos e insumos activos del tipo correspondiente.']);
        }
        return $p;
    }
    public function crear(array $datos, int $usuarioId): int
    {
        return DB::transaction(function () use ($datos, $usuarioId) {
            $producto = $this->producto((int) $datos['productoId'], 'producto_terminado');
            $id = DB::table('recetas')->insertGetId([
                'nombre' => $datos['nombre'], 'producto_id' => $datos['productoId'],
                'litros_base' => $datos['litrosBase'], 'descripcion' => $datos['descripcion'],
                'cantidad_base' => $datos['cantidadBase'] ?? null,
                'unidad_medida_id' => isset($datos['cantidadBase']) ? $this->unidadId($producto->unidad) : null,
                'usuario_id' => $usuarioId, 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($datos['insumos'] as $i) {
                $p = $this->producto((int) $i['productoId'], 'insumo');
                DB::table('receta_detalles')->insert(['receta_id' => $id, 'insumo_id' => $p->id,
                    'cantidad' => $i['cantidad'], 'unidad' => $p->unidad]);
            }
            activity('produccion')->causedBy($usuarioId)->withProperties(['receta_id' => $id])->log('Receta creada');
            return $id;
        });
    }

    public function fichaDeLote(int $produccionId): array
    {
        $productos = DB::table('produccion_productos')
            ->join('productos', 'productos.id', '=', 'produccion_productos.producto_id')
            ->leftJoin('unidades_medida', 'unidades_medida.id', '=', 'produccion_productos.unidad_medida_id')
            ->leftJoin('recetas', 'recetas.id', '=', 'produccion_productos.receta_id')
            ->where('produccion_productos.produccion_id', $produccionId)
            ->select(
                'produccion_productos.receta_id',
                'produccion_productos.cantidad_planificada',
                'produccion_productos.cantidad_producida',
                'productos.nombre as producto',
                'unidades_medida.nombre as unidad',
                'recetas.nombre as receta',
                'recetas.descripcion as receta_descripcion',
                'recetas.cantidad_base as receta_cantidad_base',
                'recetas.litros_base as receta_litros_base',
            )->get();

        return [
            'productos' => $productos->map(function ($p): array {
                $fila = (array) $p;
                // La receta se imprime tal como estaba al usarla: es la que
                // explica por que se consumio lo que se consumio.
                $fila['insumos'] = $p->receta_id === null ? [] : DB::table('receta_detalles')
                    ->join('productos', 'productos.id', '=', 'receta_detalles.insumo_id')
                    ->where('receta_id', $p->receta_id)
                    ->select('productos.nombre', 'receta_detalles.cantidad', 'receta_detalles.unidad')
                    ->get()->map(fn ($i) => (array) $i)->all();

                return $fila;
            })->all(),
            'consumos' => DB::table('produccion_detalles')
                ->join('productos', 'productos.id', '=', 'produccion_detalles.insumo_id')
                ->where('produccion_id', $produccionId)
                ->select('productos.nombre', 'produccion_detalles.cantidad', 'produccion_detalles.unidad')
                ->get()->map(fn ($i) => (array) $i)->all(),
        ];
    }
}
