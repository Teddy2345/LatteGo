<?php

declare(strict_types=1);

namespace App\Application\Proveedor\UseCases;

use App\Domain\Proveedor\Exceptions\CuentaProveedorException;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Le abre al proveedor su cuenta de la app.
 *
 * Entra con su cedula, que es el dato que la planta ya tiene de los 23
 * proveedores, y una clave que se le entrega en el momento. El correo es
 * solo para cumplir con la tabla de usuarios: el proveedor nunca lo escribe
 * ni lo necesita.
 */
final class CrearCuentaProveedorUseCase
{
    /** Clave temporal legible para dictarla en el mostrador. */
    private const ALFABETO = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * @return array{proveedor: ProveedorModel, clave: string}
     */
    public function ejecutar(int $proveedorId): array
    {
        return DB::transaction(function () use ($proveedorId) {
            $proveedor = ProveedorModel::query()->lockForUpdate()->find($proveedorId);

            if ($proveedor === null) {
                throw new CuentaProveedorException('Ese proveedor ya no está registrado.');
            }

            if ($proveedor->user_id !== null) {
                throw new CuentaProveedorException($proveedor->nombre.' ya tiene su cuenta de la app.');
            }

            if (trim((string) $proveedor->cedula) === '') {
                throw new CuentaProveedorException('Primero registra la cédula de '.$proveedor->nombre.': con ella entra a la app.');
            }

            if (! $proveedor->activo) {
                throw new CuentaProveedorException('No se le abre cuenta a un proveedor dado de baja.');
            }

            $cedula = trim((string) $proveedor->cedula);

            if (ProveedorModel::query()->where('cedula', $cedula)->whereNotNull('user_id')->exists()) {
                throw new CuentaProveedorException('Ya hay una cuenta con esa cédula. Revisa si el proveedor está duplicado.');
            }

            $clave = self::claveNueva();

            $user = User::query()->create([
                'name' => $proveedor->nombre,
                // Interno: la tabla de usuarios lo exige, el proveedor no lo usa.
                'email' => 'proveedor-'.$cedula.'@ecolactea.local',
                'password' => Hash::make($clave),
            ]);

            $user->assignRole('proveedor');
            $proveedor->update(['user_id' => $user->id]);

            activity('proveedores')->causedBy(auth()->user())->performedOn($proveedor)
                ->withProperties(['user_id' => $user->id])->log('Cuenta de la app creada');

            return ['proveedor' => $proveedor->fresh(), 'clave' => $clave];
        });
    }

    /** Renueva la clave del proveedor que la olvido. */
    public function restablecerClave(int $proveedorId): string
    {
        $proveedor = ProveedorModel::query()->find($proveedorId);

        if ($proveedor === null || $proveedor->user_id === null) {
            throw new CuentaProveedorException('Ese proveedor todavía no tiene cuenta de la app.');
        }

        $clave = self::claveNueva();

        User::query()->whereKey($proveedor->user_id)->update(['password' => Hash::make($clave)]);

        activity('proveedores')->causedBy(auth()->user())->performedOn($proveedor)->log('Clave de la app restablecida');

        return $clave;
    }

    private static function claveNueva(): string
    {
        $clave = '';

        for ($i = 0; $i < 8; $i++) {
            $clave .= self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)];
        }

        return $clave;
    }
}
