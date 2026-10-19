<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Datos de la planta
    |--------------------------------------------------------------------------
    |
    | La ubicacion es el punto de salida de todas las rutas en el mapa de
    | referencia, y se escribe como se buscaria en Google Maps (lugar o
    | "latitud,longitud"). El resto son los datos que se imprimen en las
    | boletas y facturas.
    |
    */

    'planta' => [
        'ubicacion' => env('PLANTA_UBICACION', 'Santiago de Huata, La Paz, Bolivia'),
        'razon_social' => env('PLANTA_RAZON_SOCIAL', 'Ecoláctea Huata'),
        'ruc' => env('PLANTA_RUC', ''),
        'direccion' => env('PLANTA_DIRECCION', 'Santiago de Huata, La Paz'),
        'telefono' => env('PLANTA_TELEFONO', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cobro con QR
    |--------------------------------------------------------------------------
    |
    | El QR de Yape de la planta, que el repartidor muestra al cliente junto
    | con el monto a cobrar. Se sube desde Ajustes y se guarda en el disco
    | publico; aqui solo vive la ruta al archivo.
    |
    */

    'cobros' => [
        'qr_path' => env('COBROS_QR_PATH', 'cobros/qr-yape.png'),
    ],

];
