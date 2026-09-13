# Ecolactea Huata · Android nativo

Aplicación nativa en Kotlin y Jetpack Compose. La versión 0.4.0 suma sesión persistente, trabajo sin conexión, selectores de proveedor, zona y fecha, y el módulo de almacén (stock, transformaciones y ventas), apoyados en los endpoints `mobile/*` del backend Laravel. Mantiene la identidad visual definida en 0.2.0: campo al amanecer en el login, campo al atardecer en el inicio y logo sobre fondo blanco circular.

El portal web usa esta misma paleta y plantilla, de modo que teléfono y navegador se ven como un único producto.

Paleta: verde bosque `#173E32`, verde `#356B50`, salvia `#E7EEE3`, crema `#F7F7F0` y dorado `#E4C58A`. Incluye iconos de línea nativos, accesos por módulos, tarjetas con bordes suaves y formulario organizado en secciones. Las fotografías se muestran con recorte y degradados en la interfaz; los archivos originales no se alteraron.

El contenido y los datos corresponden a la API del proyecto Laravel `C:\ecolac\lattego\ecolactea-huata`. No modifica su base de datos ni sus reglas de negocio durante la instalación.

## Abrir en Android Studio

1. Selecciona **Open** y abre la carpeta `EcolacteaAndroid` que contiene este archivo.
2. Espera a que termine **Gradle Sync**. Usa el SDK de Android 37 y el JDK incluido en Android Studio o JDK 17.
3. Selecciona un emulador o un celular con depuración USB y pulsa **Run ▶**.

El proyecto incluye Gradle Wrapper. `local.properties` es específico de esta computadora; Android Studio puede regenerarlo en otra máquina.

## Encender Laravel

Mantén MySQL activo en Laragon. En PowerShell:

```powershell
cd C:\ecolac\lattego\ecolactea-huata
& "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" artisan serve --host=0.0.0.0 --port=8001
```

En el login de la app, pulsa **Configurar servidor**:

- Emulador de Android Studio: `http://10.0.2.2:8001` (valor inicial).
- Celular: `http://IP-DE-TU-PC:8001`. La IP de la captura del usuario era `172.20.80.120`; verifica con `ipconfig` si cambias de red.
- PC y teléfono deben compartir una red que permita comunicación entre dispositivos. Si el firewall solicita acceso para PHP, habilítalo en la red privada.

No uses `localhost` en el teléfono: se refiere al propio teléfono.

### Alternativa por USB o túnel del emulador

Si la red del emulador o el Wi-Fi bloquean la conexión, con el dispositivo conectado a ADB ejecuta:

```powershell
& "$env:LOCALAPPDATA\Android\Sdk\platform-tools\adb.exe" reverse tcp:8001 tcp:8001
```

En ese caso configura `http://127.0.0.1:8001` en la app. Esta dirección funciona únicamente mientras el túnel ADB está activo. Si hay varios dispositivos, agrega `-s ID-DEL-DISPOSITIVO` antes de `reverse`.

Cuenta demo documentada en Laravel: `admin@ecolactea.test`, contraseña `password`. La aplicación utiliza `/api/v1/login` y los tokens Sanctum existentes; no cambia la autenticación del backend. Los permisos de cada operación los comprueba Laravel.

## Funciones incluidas

- Inicio y cierre de sesión contra la API real, con **sesión persistente**: el token se guarda cifrado con el almacén de claves de Android, así que reciclar o cerrar la app no obliga a escribir la contraseña otra vez. La contraseña nunca se almacena y **Cerrar sesión** revoca el token en Laravel.
- Los módulos visibles se arman con el campo `acciones` que devuelve `/api/v1/login`, de modo que cada rol ve exactamente lo que su cuenta permite.
- Recepción en campo con **proveedor y zona elegidos de una lista** (endpoint `mobile/catalogo`), selector de fecha nativo y captura de punto GPS con control de precisión.
- **Modo sin conexión**: si no hay cobertura, el acopio se guarda en el teléfono y se envía solo al recuperar la red. Una barra en el inicio indica cuántos quedan por subir y permite forzar el envío. Cada registro lleva un `request_id`, y el backend lo reconoce, así que reintentar nunca duplica una recepción.
- El catálogo de proveedores y zonas queda cacheado para poder llenar el formulario sin señal.
- **Almacén de producto terminado**: catálogo con existencias (Stock), registro de transformaciones de leche en queso, yogurt o mantequilla, y registro de ventas con descuento automático del stock. El backend rechaza vender más de lo que hay en existencia.
- Consulta, búsqueda local y detalle de acopios, proveedores, calidad, despachos, pagos, consolidado por zonas y auditoría, con deslizar para actualizar.
- Perfil con nombre y roles del usuario autenticado; dirección del servidor configurable.

Qué ve cada rol: el **acopiador** solo sus acopios; el **operador de planta** producción, stock y despachos; la **encargada de ventas** stock y ventas; el **supervisor** proveedores, consolidado por zonas y auditoría; el **administrador** todo.

Quien produce no vende y quien vende no produce: el operador de planta recibe 403 al intentar registrar una venta, y la encargada de ventas al intentar una transformación.

Todavía no implementa edición/eliminación de proveedores, altas de calidad, confirmación de despachos/pagos ni reportes PDF; esas operaciones siguen en el portal web.

## Generar APK

```powershell
.\gradlew.bat assembleDebug
```

Salida: `app\build\outputs\apk\debug\app-debug.apk`. El APK debug admite HTTP para las pruebas en tu red local. La versión release exige HTTPS; configura un servidor HTTPS antes de distribuirla en producción.

Si Java indica `Unable to establish loopback connection`, usa una ruta temporal corta existente para los sockets en esa terminal, por ejemplo:

```powershell
$env:JAVA_TOOL_OPTIONS = '-Djdk.net.unixdomain.tmpdir=C:/Users/TUFGAM~1/Documents/ChatGPT/Moviles'
.\gradlew.bat assembleDebug
```

Esa ruta es de esta computadora; en otro equipo reemplázala por una carpeta corta con permiso de escritura.

## Verificación realizada

- `assembleDebug` y `lintDebug`: completados correctamente.
- API local (curl): login con `acciones`/`perfil`, catálogo, alta de acopio con GPS, reenvío del mismo `request_id` sin duplicar, listado con nombres resueltos, consolidado por zonas y auditoría.
- Emulador Pixel 4, con la cuenta `acopiador@ecolactea.test`:
  - Tras iniciar sesión aparecen el módulo "Mis acopios" y la acción "Registrar acopio" (antes la pantalla salía vacía porque el backend no enviaba `acciones`).
  - El listado muestra "Alejandra Abigail Centeno Montero Hijo · 33.50 L" con fecha, litros, zona y estado.
  - `force-stop` y reapertura: la sesión se mantiene, sin volver a pedir contraseña.
  - Selector de proveedor con la lista real de ganaderos y selector de zona.
  - Con la red apagada, guardar deja el aviso "1 acopio guardado sin enviar"; al restaurar la red y pulsar **Enviar ahora** el registro sube y el aviso desaparece.
  - El acopio quedó en la base como id 203, Alessandra Toro Hijo, zona Huata Centro, 47.25 L, estado `sincronizado`, GPS -15.8401983 / -69.9231 ±5 m.
- Emulador Pixel 4, con la cuenta `produccion@ecolactea.test`: aparecen los módulos Producción, Stock y Despachos; el catálogo muestra "Yogurt de frutilla · Unidad: litro · Stock: 222.49"; registrar una transformación de 30 litros con 36 L de leche dejó el stock en 252.49 a nombre del operador.
- Emulador Pixel 4, con la cuenta `ventas@ecolactea.test`: aparecen solo los módulos Ventas y Stock; registrar 12.50 kg de mantequilla a 48 Bs dejó el stock en 226.76 y la venta quedó a nombre de la encargada.
- Separación de funciones comprobada: el operador de planta recibe 403 tanto al registrar una venta como al consultar el historial comercial.
- No se ha verificado todavía en un teléfono físico ni por Wi-Fi; el acceso directo `10.0.2.2` no respondió en este entorno y la prueba se completó con `adb reverse`.
