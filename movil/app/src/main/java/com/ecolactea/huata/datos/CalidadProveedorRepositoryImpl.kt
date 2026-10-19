package com.ecolactea.huata.datos

import com.ecolactea.huata.dominio.AnalisisCalidad
import com.ecolactea.huata.dominio.CalidadProveedorRepository

/**
 * Cumple el contrato del dominio con los datos de la API.
 *
 * Su trabajo es pedir a la fuente y traducir: de DTO a modelo de dominio. Es
 * la pieza que se cambiaría si los análisis pasaran a leerse de otro lado, y
 * la única: ni la pantalla ni el ViewModel se enterarían.
 */
internal class CalidadProveedorRepositoryImpl(
    private val remoto: CalidadProveedorApiDataSource,
) : CalidadProveedorRepository {

    override suspend fun misAnalisis(): List<AnalisisCalidad> =
        remoto.misAnalisis().map { it.aDominio() }
}
