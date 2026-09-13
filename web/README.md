# Ecolactea Huata

Backend de gestion de acopio y produccion lechera para la planta Ecolactea Huata, construido en Laravel 12 con arquitectura por capas (Domain / Application / Infrastructure / Presentation) agrupada por feature.

## Stack

- Laravel 12.x (PHP 8.3+)
- MySQL 8
- Livewire 3 + Alpine.js + Tailwind CSS (portal web administrativo)
- Laravel Sanctum (tokens personales para la app movil Android)
- spatie/laravel-permission (roles y permisos)
- laravel/fortify (2FA obligatorio para roles `admin` y `supervisor`)
- spatie/laravel-activitylog (auditoria de cambios)
- Pest (tests)
- dedoc/scramble (documentacion OpenAPI autogenerada)

## Arquitectura

```
app/
├── Domain/{Feature}/              Reglas de negocio puras. CERO Illuminate/Eloquent/Livewire.
│   ├── Entities/                  Clases PHP inmutables (readonly), sin ORM.
│   ├── ValueObjects/              Validan su propia invariante en el constructor.
│   ├── Repositories/              Solo interfaces (contratos).
│   └── Exceptions/                Excepciones de dominio (implementan
│                                  App\Domain\Shared\Exceptions\{NotFoundException,DomainRuleException}).
│
├── Application/{Feature}/         Orquesta Domain. Puede depender de OTROS features via
│   ├── UseCases/                  sus interfaces/Use Cases publicos (ej: Calidad -> Proveedor).
│   └── DTOs/                      Objetos de entrada/salida readonly.
│
├── Infrastructure/{Feature}/      Implementacion concreta con Eloquent.
│   ├── Models/                    Sufijo "Model" (ProveedorModel, AcopioModel...).
│   ├── Repositories/              Implementan las interfaces de Domain.
│   └── Providers/                 {Feature}ServiceProvider: bindings interfaz->implementacion
│                                  + registro de Policies (Gate::policy).
│
└── Presentation/
    ├── Web/Livewire/{Feature}/    Componentes Livewire + vistas Blade. Solo llaman Use Cases.
    └── Api/V1/
        ├── Controllers/           Solo llaman Use Cases + $this->authorize().
        ├── Requests/              Form Requests (validacion de forma).
        └── Resources/             API Resources (envuelven los DTOs de Application).
```

**Regla de dependencia (no negociable):** `Domain` no importa nada de Illuminate. `Application`
depende solo de interfaces de Domain (propias u otro feature). `Infrastructure` implementa esas
interfaces con Eloquent. `Presentation` nunca toca Eloquent ni repositorios directamente, solo Use
Cases.

### Modulos

Proveedor · Acopio · Calidad (LactoScan) · Produccion y Trazabilidad (+ Despacho) · Pagos.

Cada uno paso por: tests unitarios de Domain/Application (con repositorios en memoria en
`tests/Support/`) -> Infrastructure (migracion, modelo, repositorio, factory, seeder) ->
Presentation Web -> Presentation Api + tests de feature.

## Instalacion

Requisitos: PHP 8.3+, Composer, MySQL 8, Node 18+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Edita `.env` con los datos de tu MySQL local (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

La app queda en `http://127.0.0.1:8000`. El seeder crea 12 usuarios demo (contraseña `password`
para todos):

| Rol | Email | Alcance |
|---|---|---|
| admin | admin@ecolactea.test | Todo el sistema |
| supervisor | supervisor@ecolactea.test | Proveedores, reportes, almacen y auditoria |
| calidad | calidad@ecolactea.test | Analisis de laboratorio |
| produccion | produccion@ecolactea.test | Produccion, despachos y transformaciones al almacen |
| ventas | ventas@ecolactea.test | Existencias y comercializacion de producto terminado |
| almacen | almacen@ecolactea.test | Catalogo, entradas y salidas de insumos y producto |
| repartidor | repartidor@ecolactea.test | Entrega y cobro de los pedidos que le asignan |

Los acopiadores son cinco personas reales, una por movilidad, y cada una solo ve su propia ruta
y sus propios proveedores:

| Movilidad | Ruta | Email |
|---|---|---|
| Camion 01 | Huata Centro | camion1@ecolactea.test |
| Camion 02 | Achacachi | camion2@ecolactea.test |
| Camion 03 | Copacabana | camion3@ecolactea.test |
| Motocarga | Puerto Perez | motocarga@ecolactea.test |
| Entrega directa en planta | — | recepcion@ecolactea.test |

`admin` y `supervisor` deben configurar 2FA (QR + codigo) en su primer login antes de poder usar
el sistema; el resto de roles no lo requiere.

Separacion de funciones en el almacen: quien produce no vende y quien vende no produce. El
historial comercial (precios, clientes, recaudacion) exige `ventas.registrar` o `reportes.ver`,
de modo que el operador de planta ve las existencias pero no los importes.

## Migraciones y seeders

```bash
php artisan migrate:fresh --seed        # reinicia todo desde cero
php artisan db:seed --class=ProveedorSeeder   # un seeder individual
```

El `DatabaseSeeder` corre en este orden (por dependencias de FK y de negocio):

1. `RolePermissionSeeder` — 6 roles, 12 permisos.
2. `UserSeeder` — 6 usuarios demo con su rol.
3. `RutaSeeder` — 5 rutas de acopio.
4. `ProveedorSeeder` — 25 proveedores.
5. `AcopioSeeder` — 100 acopios.
6. `CalidadSeeder` — 20 analisis de calidad.
7. `ProduccionSeeder` — 30 dias de produccion + su despacho.
8. `PagoSeeder` — genera planillas de pago reales (via `GenerarPlanillaPagoUseCase`) para cada
   semana jueves-miercoles cubierta por los acopios sembrados.
9. `InventarioSeeder` — 6 productos terminados (quesos, yogures, mantequilla, leche en bolsa) con
   su historial de transformaciones y ventas; deja stock positivo en todos.

## API y documentacion OpenAPI

La API vive bajo `/api/v1`, protegida con Sanctum (`auth:sanctum`). Autenticacion pensada para
la app movil (un token por dispositivo):

```
POST /api/v1/login    {email, password, device_name}   -> throttle:5,1
POST /api/v1/logout                                     -> revoca el token del dispositivo actual
```

La documentacion OpenAPI se genera automaticamente a partir del codigo (Scramble):

```bash
php artisan serve
# UI interactiva:
http://127.0.0.1:8000/docs/api
# JSON crudo:
http://127.0.0.1:8000/docs/api.json

# Exportar a un archivo:
php artisan scramble:export
```

## Tests

Los tests corren contra una base de datos MySQL separada (`ecolactea_huata_testing`, definida en
`phpunit.xml`) para no pisar los datos de `ecolactea_huata`. Crearla una vez:

```sql
CREATE DATABASE ecolactea_huata_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php vendor/bin/pest              # suite completa
php vendor/bin/pint              # estilo de codigo (PSR-12 + strict_types)
```

## Comandos para dejar el proyecto funcionando desde cero

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```
