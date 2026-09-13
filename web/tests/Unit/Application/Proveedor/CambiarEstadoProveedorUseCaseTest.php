<?php

declare(strict_types=1);

use App\Application\Proveedor\DTOs\RegistrarProveedorData;
use App\Application\Proveedor\UseCases\CambiarEstadoProveedorUseCase;
use App\Application\Proveedor\UseCases\ListarProveedoresActivosUseCase;
use App\Application\Proveedor\UseCases\RegistrarProveedorUseCase;
use Tests\Support\InMemoryProveedorRepository;

test('un proveedor desactivado no aparece en listarActivos', function () {
    $repositorio = new InMemoryProveedorRepository();
    $registrar = new RegistrarProveedorUseCase($repositorio);
    $cambiarEstado = new CambiarEstadoProveedorUseCase($repositorio);
    $listarActivos = new ListarProveedoresActivosUseCase($repositorio);

    $proveedor = $registrar->ejecutar(new RegistrarProveedorData(
        nombre: 'Rosa Apaza',
        cedula: '112233',
        telefono: null,
        finca: null,
        litrosProm: 30,
        precioLitro: 3.2,
        rutaId: null,
    ));

    expect($listarActivos->ejecutar())->toHaveCount(1);

    $cambiarEstado->ejecutar($proveedor->id, false);

    expect($listarActivos->ejecutar())->toHaveCount(0);
});
