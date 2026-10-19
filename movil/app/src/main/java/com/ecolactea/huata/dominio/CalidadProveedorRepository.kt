package com.ecolactea.huata.dominio

/**
 * El contrato: de aquí salen los análisis del proveedor.
 *
 * Esta interfaz es la pieza que hace que el patrón valga la pena. Quien la usa
 * —el ViewModel, el caso de uso— no sabe si los análisis llegan de la API, de
 * una base local o de datos de prueba. Por eso conectar la API no obliga a
 * tocar la pantalla: solo cambia quién implementa esto.
 */
interface CalidadProveedorRepository {
    /** Los análisis de la leche del proveedor que tiene la sesión abierta. */
    suspend fun misAnalisis(): List<AnalisisCalidad>
}
