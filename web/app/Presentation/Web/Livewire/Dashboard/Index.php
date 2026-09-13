<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Dashboard;

use App\Application\Acopio\DTOs\AcopioData;
use App\Application\Acopio\UseCases\ListarAcopiosPendientesUseCase;
use App\Application\Acopio\UseCases\ListarAcopiosUseCase;
use App\Application\Calidad\DTOs\CalidadData;
use App\Application\Calidad\UseCases\ListarCalidadUseCase;
use App\Application\Inventario\UseCases\ListarMovimientosUseCase;
use App\Application\Inventario\UseCases\ListarProductosConStockUseCase;
use App\Application\Pagos\DTOs\PagoData;
use App\Application\Pagos\UseCases\ListarPagosUseCase;
use App\Application\Pedidos\DTOs\PedidoData;
use App\Application\Pedidos\UseCases\ListarPedidosUseCase;
use App\Application\Produccion\DTOs\ProduccionData;
use App\Application\Produccion\UseCases\ListarProduccionUseCase;
use App\Application\Proveedor\UseCases\ListarProveedoresActivosUseCase;
use App\Application\Proveedor\UseCases\ListarProveedoresUseCase;
use App\Domain\Acopio\ValueObjects\SemanaPago;
use App\Domain\Inventario\ValueObjects\TipoMovimiento;
use DateTimeImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Index extends Component
{
    /** Cuantas semanas de tendencia se muestran en los graficos del dashboard. */
    private const SEMANAS_TENDENCIA = 8;

    public function render(
        ListarProveedoresUseCase $listarProveedores,
        ListarProveedoresActivosUseCase $listarProveedoresActivos,
        ListarAcopiosUseCase $listarAcopios,
        ListarAcopiosPendientesUseCase $listarAcopiosPendientes,
        ListarCalidadUseCase $listarCalidad,
        ListarProduccionUseCase $listarProduccion,
        ListarPagosUseCase $listarPagos,
        ListarProductosConStockUseCase $listarProductos,
        ListarMovimientosUseCase $listarMovimientos,
        ListarPedidosUseCase $listarPedidos,
    ): View {
        $user = Auth::user();

        $puedeProveedores = $user->can('proveedores.ver') || $user->can('reportes.ver');
        $puedeAcopios = $user->can('acopios.registrar') || $user->can('reportes.ver');
        $puedeCalidad = $user->can('calidad.registrar') || $user->can('reportes.ver');
        $puedeProduccion = $user->can('produccion.registrar') || $user->can('reportes.ver');
        $puedePagos = $user->can('pagos.generar') || $user->can('reportes.ver');
        $puedeInventario = $user->can('inventario.ver');
        $puedeVentas = $user->can('ventas.registrar') || $user->can('reportes.ver');
        $puedePedidos = $user->can('ventas.registrar') || $user->can('reportes.ver') || $user->can('pedidos.repartir');

        $datos = [
            'user' => $user,
            'puedeProveedores' => $puedeProveedores,
            'puedeAcopios' => $puedeAcopios,
            'puedeCalidad' => $puedeCalidad,
            'puedeProduccion' => $puedeProduccion,
            'puedePagos' => $puedePagos,
            'puedeInventario' => $puedeInventario,
            'puedeVentas' => $puedeVentas,
            'puedePedidos' => $puedePedidos,
        ];

        $semanas = $this->ultimasSemanas(self::SEMANAS_TENDENCIA);

        if ($puedeProveedores) {
            $datos['proveedoresActivos'] = count($listarProveedoresActivos->ejecutar());
            $datos['proveedoresTotal'] = count($listarProveedores->ejecutar());
        }

        if ($puedeAcopios) {
            $semana = SemanaPago::desde(new DateTimeImmutable());
            $acopios = collect($listarAcopios->ejecutar());

            $datos['semanaInicio'] = $semana->inicio->format('Y-m-d');
            $datos['semanaFin'] = $semana->fin->format('Y-m-d');
            $datos['litrosSemana'] = $acopios
                ->filter(fn (AcopioData $a) => $a->fecha >= $semana->inicio->format('Y-m-d') && $a->fecha <= $semana->fin->format('Y-m-d'))
                ->sum('cantidadLitros');
            $datos['acopiosPendientes'] = count($listarAcopiosPendientes->ejecutar());
            $datos['ultimosAcopios'] = $acopios->take(5);

            $datos['tendenciaLitros'] = $this->agruparPorSemana(
                $semanas,
                $acopios->all(),
                fn (AcopioData $a): string => $a->fecha,
                fn (AcopioData $a): float => $a->cantidadLitros,
            );
        }

        if ($puedeCalidad) {
            $hace7Dias = now()->subDays(7)->format('Y-m-d');
            $datos['rechazosRecientes'] = collect($listarCalidad->ejecutar())
                ->filter(fn (CalidadData $c) => $c->fecha >= $hace7Dias && $c->resultado === 'rechazada')
                ->count();
        }

        if ($puedeProduccion) {
            $produccion = collect($listarProduccion->ejecutar());

            $datos['produccionFueraDeRango'] = $produccion
                ->filter(fn (ProduccionData $p) => ! $p->rendimientoEnRangoEsperado)
                ->count();

            $datos['tendenciaProduccion'] = $this->agruparPorSemana(
                $semanas,
                $produccion->all(),
                fn (ProduccionData $p): string => $p->fecha,
                fn (ProduccionData $p): float => $p->quesosProducidos,
            );
        }

        if ($puedePagos) {
            $pagos = collect($listarPagos->ejecutar());
            $pendientes = $pagos->filter(fn (PagoData $p) => $p->estado === 'pendiente');

            $datos['pagosPendientesCantidad'] = $pendientes->count();
            $datos['pagosPendientesMonto'] = $pendientes->sum('totalPagar');
            $datos['ultimosPagos'] = $pagos->take(5);
        }

        if ($puedeInventario) {
            $productos = collect($listarProductos->ejecutar());

            // Sumar unidades distintas (piezas, litros, kilos) no diria nada,
            // asi que se avisa cuantos productos quedaron sin existencia.
            $datos['productosAgotados'] = $productos->filter(fn ($p): bool => $p->stock <= 0)->count();
            $datos['productosTotal'] = $productos->count();
        }

        if ($puedeVentas) {
            $ventas = collect($listarMovimientos->ejecutar(TipoMovimiento::Venta));
            $semanaVentas = SemanaPago::desde(new DateTimeImmutable());

            $datos['ventasSemana'] = $ventas
                ->filter(fn ($v): bool => $v->fecha >= $semanaVentas->inicio->format('Y-m-d')
                    && $v->fecha <= $semanaVentas->fin->format('Y-m-d'))
                ->sum(fn ($v): float => $v->total ?? 0.0);

            $datos['tendenciaVentas'] = $this->agruparPorSemana(
                $semanas,
                $ventas->all(),
                fn ($v): string => $v->fecha,
                fn ($v): float => $v->total ?? 0.0,
            );
        }

        if ($puedePedidos) {
            $pedidos = collect($listarPedidos->ejecutar());

            $datos['pedidosPendientes'] = $pedidos->where('estado', 'pendiente')->count();
            $datos['pedidosPorEstado'] = [
                'pendiente' => $pedidos->where('estado', 'pendiente')->count(),
                'confirmado' => $pedidos->where('estado', 'confirmado')->count(),
                'entregado' => $pedidos->where('estado', 'entregado')->count(),
                'cancelado' => $pedidos->where('estado', 'cancelado')->count(),
            ];
            $datos['recaudadoSemana'] = $pedidos
                ->filter(fn (PedidoData $p): bool => $p->estado === 'entregado'
                    && $p->fechaEntrega >= SemanaPago::desde(new DateTimeImmutable())->inicio->format('Y-m-d'))
                ->sum('montoCobrado');
            $datos['ultimosPedidos'] = $pedidos->take(5);
        }

        return view('livewire.dashboard.index', $datos);
    }

    /**
     * Los ultimos N cortes de semana de acopio (jueves-miercoles), de mas
     * antiguo a mas reciente, con una etiqueta corta para el eje del grafico.
     *
     * @return list<array{inicio: string, fin: string, etiqueta: string}>
     */
    private function ultimasSemanas(int $cantidad): array
    {
        $semanas = [];

        for ($i = $cantidad - 1; $i >= 0; $i--) {
            $semana = SemanaPago::desde(now()->subWeeks($i)->toDateTimeImmutable());
            $semanas[] = [
                'inicio' => $semana->inicio->format('Y-m-d'),
                'fin' => $semana->fin->format('Y-m-d'),
                'etiqueta' => $semana->inicio->format('d/m'),
            ];
        }

        return $semanas;
    }

    /**
     * Suma $extraerValor de cada registro cuya $extraerFecha cae dentro de
     * cada semana, en el mismo orden que $semanas.
     *
     * @param  list<array{inicio: string, fin: string, etiqueta: string}>  $semanas
     * @param  array<int, mixed>  $registros
     * @return list<array{label: string, value: float}>
     */
    private function agruparPorSemana(array $semanas, array $registros, \Closure $extraerFecha, \Closure $extraerValor): array
    {
        return array_map(
            static function (array $semana) use ($registros, $extraerFecha, $extraerValor): array {
                $total = 0.0;

                foreach ($registros as $registro) {
                    $fecha = $extraerFecha($registro);

                    if ($fecha >= $semana['inicio'] && $fecha <= $semana['fin']) {
                        $total += $extraerValor($registro);
                    }
                }

                return ['label' => $semana['etiqueta'], 'value' => round($total, 2)];
            },
            $semanas,
        );
    }
}
