<?php

declare(strict_types=1);

use App\Presentation\Web\Livewire\Acopio\Formulario as AcopioFormulario;
use App\Presentation\Web\Livewire\Acopio\Index as AcopioIndex;
use App\Presentation\Web\Livewire\Auth\TwoFactorSetup;
use App\Presentation\Web\Livewire\Calidad\Formulario as CalidadFormulario;
use App\Presentation\Web\Livewire\Calidad\Index as CalidadIndex;
use App\Presentation\Web\Livewire\Dashboard\Index as DashboardIndex;
use App\Presentation\Web\Livewire\Inventario\CatalogoFormulario as InventarioCatalogoFormulario;
use App\Presentation\Web\Livewire\Inventario\EntradaFormulario as InventarioEntradaFormulario;
use App\Presentation\Web\Livewire\Inventario\Formulario as InventarioFormulario;
use App\Presentation\Web\Livewire\Inventario\Index as InventarioIndex;
use App\Presentation\Web\Livewire\Inventario\SalidaFormulario as InventarioSalidaFormulario;
use App\Presentation\Web\Livewire\Movilidad\Detalle;
use App\Presentation\Web\Livewire\Movilidad\Movilidades;
use App\Presentation\Web\Livewire\Movilidad\RegistroRapido;
use App\Presentation\Web\Livewire\Pagos\Comprobante as PagoComprobante;
use App\Presentation\Web\Livewire\Pagos\Index as PagosIndex;
use App\Presentation\Web\Livewire\Pedidos\Comprobante as PedidoComprobante;
use App\Presentation\Web\Livewire\Pedidos\Detalle as PedidoDetalle;
use App\Presentation\Web\Livewire\Pedidos\Index as PedidosIndex;
use App\Presentation\Web\Livewire\Produccion\Costeo as ProduccionCosteo;
use App\Presentation\Web\Livewire\Produccion\DespachoFormulario;
use App\Presentation\Web\Livewire\Produccion\Formulario as ProduccionFormulario;
use App\Presentation\Web\Livewire\Produccion\Index as ProduccionIndex;
use App\Presentation\Web\Livewire\Proveedor\Formulario as ProveedorFormulario;
use App\Presentation\Web\Livewire\Proveedor\Index as ProveedorIndex;
use App\Presentation\Web\Livewire\Proveedor\SolicitudesCambioZona;
use App\Presentation\Web\Livewire\Tienda\Carrito as TiendaCarrito;
use App\Presentation\Web\Livewire\Tienda\Catalogo as TiendaCatalogo;
use App\Presentation\Web\Livewire\Tienda\Checkout as TiendaCheckout;
use App\Presentation\Web\Livewire\Tienda\Confirmacion as TiendaConfirmacion;
use App\Presentation\Web\Livewire\Ventas\Formulario as VentaFormulario;
use App\Presentation\Web\Livewire\Ventas\Index as VentasIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
 * Tienda publica: sin autenticacion, la usa cualquier visitante sin cuenta.
 * El pedido que genera queda pendiente de confirmacion manual por Ventas.
 */
Route::prefix('tienda')->name('tienda.')->group(function () {
    Route::get('/', TiendaCatalogo::class)->name('index');
    Route::get('/carrito', TiendaCarrito::class)->name('carrito');
    Route::get('/checkout', TiendaCheckout::class)->name('checkout');
    Route::get('/pedido/{pedido}/gracias', TiendaConfirmacion::class)->name('confirmacion');
});

Route::middleware('auth')->group(function () {
    Route::get('/configuracion/2fa', TwoFactorSetup::class)->name('dos-factores.setup');
});

Route::middleware(['auth', 'ensure2fa'])->group(function () {
    Route::get('/dashboard', DashboardIndex::class)->name('dashboard');

    Route::prefix('proveedores')->name('proveedores.')->group(function () {
        Route::get('/', ProveedorIndex::class)->name('index');
        Route::get('/crear', ProveedorFormulario::class)->name('crear');
        Route::get('/{proveedorId}/editar', ProveedorFormulario::class)->name('editar');
        Route::get('/cambios-de-zona', SolicitudesCambioZona::class)->name('cambios-zona');
    });

    Route::prefix('acopios')->name('acopios.')->group(function () {
        Route::get('/', AcopioIndex::class)->name('index');
        Route::get('/crear', AcopioFormulario::class)->name('crear');
    });

    Route::prefix('movilidades')->name('movilidades.')->group(function () {
        Route::get('/', Movilidades::class)->name('index');
        Route::get('/{movilidadId}', Detalle::class)->name('detalle');
        Route::get('/{movilidadId}/registrar/{proveedorId}', RegistroRapido::class)->name('registro-rapido');
    });

    Route::prefix('calidad')->name('calidad.')->group(function () {
        Route::get('/', CalidadIndex::class)->name('index');
        Route::get('/crear', CalidadFormulario::class)->name('crear');
    });

    Route::prefix('produccion')->name('produccion.')->group(function () {
        Route::get('/', ProduccionIndex::class)->name('index');
        Route::get('/crear', ProduccionFormulario::class)->name('crear');
        Route::get('/despacho/crear', DespachoFormulario::class)->name('despacho.crear');
        Route::get('/{produccion}/costeo', ProduccionCosteo::class)->name('costeo');
    });

    Route::prefix('almacen')->name('inventario.')->group(function () {
        Route::get('/', InventarioIndex::class)->name('index');
        Route::get('/transformacion', InventarioFormulario::class)->name('crear');
        Route::get('/catalogo/crear', InventarioCatalogoFormulario::class)->name('catalogo.crear');
        Route::get('/catalogo/{productoId}/editar', InventarioCatalogoFormulario::class)->name('catalogo.editar');
        Route::get('/entradas/crear', InventarioEntradaFormulario::class)->name('entradas.crear');
        Route::get('/salidas/crear', InventarioSalidaFormulario::class)->name('salidas.crear');
    });

    Route::prefix('ventas')->name('ventas.')->group(function () {
        Route::get('/', VentasIndex::class)->name('index');
        Route::get('/crear', VentaFormulario::class)->name('crear');
    });

    Route::get('/notificaciones', \App\Presentation\Web\Livewire\Notificacion\Centro::class)->name('notificaciones.index');

    Route::prefix('pagos')->name('pagos.')->group(function () {
        Route::get('/', PagosIndex::class)->name('index');
        Route::get('/{pagoId}/comprobante', PagoComprobante::class)->name('comprobante');
    });

    Route::prefix('pedidos')->name('pedidos.')->group(function () {
        Route::get('/', PedidosIndex::class)->name('index');
        Route::get('/{pedido}', PedidoDetalle::class)->name('detalle');
        Route::get('/{pedido}/comprobante', PedidoComprobante::class)->name('comprobante');
    });
});
