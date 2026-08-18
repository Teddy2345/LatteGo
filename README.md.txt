# LatteGo

> Gestiona el acopio de leche de forma fácil y organizada.

## Problema que resuelve

El proceso de acopio de leche requiere registrar las entregas realizadas por diferentes productores, incluyendo información como la fecha, cantidad de litros y observaciones. Cuando estos registros se realizan manualmente, puede resultar difícil mantener la información organizada, consultar entregas anteriores y evitar errores en el registro.

**LatteGo** busca facilitar la gestión del acopio de leche mediante una aplicación móvil que permita registrar, consultar y administrar las entregas realizadas por los productores.

La aplicación estará orientada a un escenario en el que el encargado del acopio pueda registrar información incluso cuando no exista conexión a Internet, permitiendo posteriormente sincronizar los datos.

## Público objetivo

La aplicación está dirigida principalmente a los responsables y encargados de centros de acopio de leche que necesitan registrar y controlar las entregas realizadas por los productores.

La propuesta será validada con la **Lic. Silvana**, responsable relacionada con el proceso de acopio.

## Funcionalidades previstas

* **F1:** Iniciar sesión mediante usuario y contraseña.
* **F2:** Registrar y consultar información de los productores.
* **F3:** Registrar entregas o acopios de leche.
* **F4:** Listar, editar y eliminar registros de acopio.
* **F5:** Consultar el historial de entregas realizadas.
* **F6:** Registrar información sin conexión a Internet.
* **F7:** Sincronizar los registros locales cuando exista conexión.
* **F8:** Identificar rápidamente al productor mediante el escaneo de un código QR.

## Entidad principal del CRUD

### Acopio

La entidad principal del CRUD será **Acopio**, que representa cada entrega de leche realizada por un productor al centro de acopio.

### Atributos tentativos

| Atributo         | Descripción                                     |
| ---------------- | ----------------------------------------------- |
| `id`             | Identificador único del acopio                  |
| `productorId`    | Identificador del productor                     |
| `fecha`          | Fecha de la entrega                             |
| `cantidadLitros` | Cantidad de leche entregada en litros           |
| `calidad`        | Estado o resultado de la evaluación de la leche |
| `observacion`    | Información adicional del registro              |

### Operaciones CRUD

* **Crear:** registrar un nuevo acopio.
* **Leer:** consultar y listar los acopios registrados.
* **Actualizar:** modificar los datos de un acopio.
* **Eliminar:** eliminar un registro de acopio.

## Pantallas principales previstas

La aplicación tendrá inicialmente entre 4 y 6 pantallas principales:

1. **Inicio de sesión**
2. **Inicio / Dashboard**
3. **Productores**
4. **Registrar acopio**
5. **Historial de acopios**

## Capacidad nativa prevista

Se plantea utilizar el **escaneo de códigos QR** para identificar rápidamente a los productores durante el registro de una entrega de leche.

El flujo previsto será:

**Escanear QR → identificar productor → registrar cantidad de leche → guardar acopio**

Esta funcionalidad permitirá agilizar el registro y reducir la necesidad de ingresar manualmente los datos del productor.

## Funcionamiento offline-first

LatteGo estará diseñada bajo un enfoque **offline-first**, permitiendo registrar información de acopio aun cuando el dispositivo no tenga conexión a Internet.

Los datos podrán almacenarse localmente y posteriormente sincronizarse con el servidor cuando vuelva a existir conexión.

## Autenticación

La aplicación contará con un sistema de inicio de sesión para controlar el acceso de los usuarios autorizados.

Se tiene previsto utilizar autenticación mediante **JWT** en la comunicación con el servicio backend.

## Equipo

| Integrante                               | Código    | Rol – Semana 1   |
| ---------------------------------------- | --------- | ---------------- |
| **Richard Edy Quispecondori Huaricallo** | 202413206 | Coordinación     |
| **Keysy Gabriela Inofuente Chua**        | 202413208 | Desarrollo de UI |
| **Ariana Danitza Alvarez Moya**          | 202414027 | Lógica y datos   |

> El equipo está conformado actualmente por tres integrantes y solicitará la autorización correspondiente al docente para trabajar con esta cantidad de integrantes.

## Tecnologías

### Desarrollo móvil

* **Kotlin Multiplatform**
* **Compose Multiplatform**
* **Android**
* **Desktop**

### Arquitectura

* Clean Architecture
* MVVM

### Datos y comunicación

* API REST
* JWT
* SQLDelight
* Funcionamiento offline-first
* Sincronización de datos

### Control de versiones

* Git
* GitHub
* Rama `main`
* Rama `develop`

## Alcance inicial

LatteGo se enfocará en la gestión del registro de acopios de leche y la información básica de los productores.

El proyecto priorizará un alcance adecuado para el desarrollo durante el semestre, evitando incorporar funcionalidades que no sean necesarias para resolver el problema principal.

## Responsable de validación

**Lic. Silvana**

La responsable permitirá validar que la propuesta y las funcionalidades planteadas respondan a una necesidad real relacionada con el proceso de acopio de leche.

## Estado del proyecto

**Semana 1 — Propuesta y organización del proyecto**

Actualmente se encuentra en proceso de definición de requisitos, conformación del equipo, documentación y configuración del repositorio.
