# LatteGo

> Gestiona el acopio de leche de forma fácil y organizada.

Sistema de gestión para la planta **Ecolactea Huata**: acopio de leche en campo, control de
calidad, producción, almacén, ventas, tienda web, reparto y pago semanal a proveedores.

## Problema que resuelve

El acopio de leche requiere registrar las entregas de muchos productores (fecha, litros,
calidad, observaciones). Hecho a mano, la información se desordena, cuesta consultar entregas
anteriores y aparecen errores. LatteGo lleva todo el proceso a un sistema web con una app
móvil para el trabajo en campo, que registra aun sin conexión y sincroniza después.

## Estructura del repositorio

| Carpeta | Contenido |
|---|---|
| [`web/`](./web) | Sistema web y API REST — Laravel 12, Livewire 3, Tailwind |
| [`movil/`](./movil) | App Android para acopiadores y supervisión — Kotlin, Jetpack Compose |
| [`docs/`](./docs) | Documentación del curso: actas del equipo, propuesta y comparativa |

Cada proyecto tiene su propio README con la instalación detallada.

## Módulos

- **Acopio en campo** — cada acopiador tiene su movilidad, su ruta y sus proveedores; registro
  rápido, registro sin conexión y sincronización.
- **Calidad** — análisis LactoScan con rechazo automático por agua añadida o acidez, y
  sanciones escalonadas por adulteración.
- **Producción y costeo** — rendimiento de quesos por litro y costo real de cada producción.
- **Almacén** — catálogo, entradas, salidas y transformaciones.
- **Ventas, tienda web y reparto** — pedidos en línea, asignación de repartidor y cobro.
- **Pagos** — planilla semanal (jueves a miércoles) con el precio por litro de la temporada.
- **Notificaciones** — alertas de calidad y solicitudes de cambio de zona.

## Seguridad

- Permisos por rol: admin, supervisor, acopiador, calidad, producción, ventas, almacén y
  repartidor. El menú de cada rol muestra exactamente lo que puede abrir.
- Verificación en dos pasos obligatoria para admin y supervisor, con códigos de respaldo y
  opción de recibir el código en el celular vinculado.

## Tecnologías

| Área | Tecnología |
|---|---|
| Backend | PHP 8.3, Laravel 12, Livewire 3, MySQL |
| Arquitectura | Clean Architecture por módulos (Domain, Application, Infrastructure, Presentation) |
| Autenticación | Laravel Fortify (web, 2FA TOTP) y Sanctum (tokens por dispositivo) |
| Móvil | Kotlin, Jetpack Compose, almacenamiento cifrado con Android Keystore |
| Pruebas | Pest — 294 pruebas |

## Equipo

| Integrante | Código | Rol |
|---|---|---|
| **Richard Edy Quispecondori Huaricallo** | 202413206 | Coordinación |
| **Keysy Gabriela Inofuente Chua** | 202413208 | Desarrollo de UI |
| **Ariana Danitza Alvarez Moya** | 202414027 | Lógica y datos |

**Responsable de validación:** Lic. Silvana

## Historial

El contenido anterior de este repositorio — la propuesta inicial en `main` y el esqueleto
Kotlin Multiplatform en `develop` — se conserva en las ramas `respaldo/main-anterior` y
`respaldo/develop-anterior`.
