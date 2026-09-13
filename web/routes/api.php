<?php

declare(strict_types=1);

use App\Presentation\Api\V1\Controllers\AcopioController;
use App\Presentation\Api\V1\Controllers\AuthController;
use App\Presentation\Api\V1\Controllers\CalidadController;
use App\Presentation\Api\V1\Controllers\DespachoController;
use App\Presentation\Api\V1\Controllers\Mobile\AcopioMovilController;
use App\Presentation\Api\V1\Controllers\Mobile\AuditoriaController;
use App\Presentation\Api\V1\Controllers\Mobile\CatalogoController;
use App\Presentation\Api\V1\Controllers\Mobile\ConsolidadoController;
use App\Presentation\Api\V1\Controllers\Mobile\DosFactoresController;
use App\Presentation\Api\V1\Controllers\Mobile\InventarioMovilController;
use App\Presentation\Api\V1\Controllers\Mobile\ProveedorMovilController;
use App\Presentation\Api\V1\Controllers\PagoController;
use App\Presentation\Api\V1\Controllers\ProduccionController;
use App\Presentation\Api\V1\Controllers\ProveedorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login');
});

Route::prefix('v1')->name('api.v1.')->middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
    Route::post('proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
    Route::get('proveedores/{proveedor}', [ProveedorController::class, 'show'])->name('proveedores.show');
    Route::put('proveedores/{proveedor}', [ProveedorController::class, 'update'])->name('proveedores.update');
    Route::delete('proveedores/{proveedor}', [ProveedorController::class, 'destroy'])->name('proveedores.destroy');
    Route::patch('proveedores/{proveedor}/ruta', [ProveedorController::class, 'reasignarRuta'])->name('proveedores.reasignar-ruta');
    Route::patch('proveedores/{proveedor}/estado', [ProveedorController::class, 'cambiarEstado'])->name('proveedores.cambiar-estado');

    Route::get('acopios', [AcopioController::class, 'index'])->name('acopios.index');
    Route::post('acopios', [AcopioController::class, 'store'])->name('acopios.store');
    Route::get('acopios/{acopio}', [AcopioController::class, 'show'])->name('acopios.show');
    Route::patch('acopios/{acopio}/sincronizar', [AcopioController::class, 'sincronizar'])->name('acopios.sincronizar');

    Route::get('calidad', [CalidadController::class, 'index'])->name('calidad.index');
    Route::post('calidad', [CalidadController::class, 'store'])->name('calidad.store');
    Route::get('calidad/{calidad}', [CalidadController::class, 'show'])->name('calidad.show');

    Route::get('produccion/aptitud/{proveedor}', [ProduccionController::class, 'aptitudPasteurizada'])->name('produccion.aptitud');
    Route::get('produccion', [ProduccionController::class, 'index'])->name('produccion.index');
    Route::post('produccion', [ProduccionController::class, 'store'])->name('produccion.store');
    Route::get('produccion/{produccion}', [ProduccionController::class, 'show'])->name('produccion.show');

    Route::get('despachos', [DespachoController::class, 'index'])->name('despachos.index');
    Route::post('despachos', [DespachoController::class, 'store'])->name('despachos.store');
    Route::get('despachos/{despacho}', [DespachoController::class, 'show'])->name('despachos.show');

    Route::get('pagos', [PagoController::class, 'index'])->name('pagos.index');
    Route::post('pagos/generar-planilla', [PagoController::class, 'generarPlanilla'])->name('pagos.generar-planilla');
    Route::get('pagos/{pago}', [PagoController::class, 'show'])->name('pagos.show');
    Route::patch('pagos/{pago}/pagar', [PagoController::class, 'marcarPagado'])->name('pagos.pagar');

    /*
     * Endpoints que consume la app Android. Viven bajo su propio prefijo
     * porque devuelven vistas ya compuestas para la pantalla del telefono
     * (nombres resueltos, catalogos minimos) en lugar de los recursos
     * normalizados que usa el portal web.
     */
    Route::prefix('mobile')->name('mobile.')->group(function () {
        Route::get('catalogo', CatalogoController::class)->name('catalogo');

        Route::get('acopios', [AcopioMovilController::class, 'index'])->name('acopios.index');
        Route::post('acopios', [AcopioMovilController::class, 'store'])->name('acopios.store');
        Route::get('acopios/{acopio}', [AcopioMovilController::class, 'show'])->name('acopios.show');

        Route::get('proveedores/{proveedor}', [ProveedorMovilController::class, 'show'])->name('proveedores.show');
        Route::post('proveedores/{proveedor}/cambio-zona', [ProveedorMovilController::class, 'solicitarCambioZona'])->name('proveedores.cambio-zona');

        /*
         * Campana de verificacion en dos pasos: solo responde con el codigo
         * al telefono que su dueño vinculo antes desde la web.
         */
        Route::get('2fa/estado', [DosFactoresController::class, 'estado'])->name('2fa.estado');
        Route::post('2fa/vincular', [DosFactoresController::class, 'vincular'])->name('2fa.vincular');
        Route::get('2fa/codigo', [DosFactoresController::class, 'codigo'])->name('2fa.codigo');
        Route::delete('2fa/vincular', [DosFactoresController::class, 'desvincular'])->name('2fa.desvincular');

        Route::get('consolidado', ConsolidadoController::class)->name('consolidado');
        Route::get('auditoria', AuditoriaController::class)->name('auditoria');

        Route::get('productos', [InventarioMovilController::class, 'productos'])->name('productos');
        Route::get('produccion', [InventarioMovilController::class, 'producciones'])->name('produccion.index');
        Route::post('produccion', [InventarioMovilController::class, 'registrarProduccion'])->name('produccion.store');
        Route::get('ventas', [InventarioMovilController::class, 'ventas'])->name('ventas.index');
        Route::post('ventas', [InventarioMovilController::class, 'registrarVenta'])->name('ventas.store');
    });
});
