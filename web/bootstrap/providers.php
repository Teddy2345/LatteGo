<?php

declare(strict_types=1);

use App\Infrastructure\Acopio\Providers\AcopioServiceProvider;
use App\Infrastructure\Auditoria\Providers\AuditoriaServiceProvider;
use App\Infrastructure\Calidad\Providers\CalidadServiceProvider;
use App\Infrastructure\Inventario\Providers\InventarioServiceProvider;
use App\Infrastructure\Movilidad\Providers\MovilidadServiceProvider;
use App\Infrastructure\Notificacion\Providers\NotificacionServiceProvider;
use App\Infrastructure\Pagos\Providers\PagosServiceProvider;
use App\Infrastructure\Pedidos\Providers\PedidosServiceProvider;
use App\Infrastructure\Produccion\Providers\ProduccionServiceProvider;
use App\Infrastructure\Proveedor\Providers\ProveedorServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    ProveedorServiceProvider::class,
    AcopioServiceProvider::class,
    MovilidadServiceProvider::class,
    CalidadServiceProvider::class,
    ProduccionServiceProvider::class,
    PagosServiceProvider::class,
    PedidosServiceProvider::class,
    InventarioServiceProvider::class,
    AuditoriaServiceProvider::class,
    NotificacionServiceProvider::class,
];
