package com.ecolactea.huata.datos

import com.ecolactea.huata.Api
import org.json.JSONObject

/**
 * La fuente remota: lo único que habla HTTP.
 *
 * Devuelve DTOs, no modelos de dominio. Separar la fuente del repositorio
 * permite que mañana haya otra —una caché en disco para la planta sin señal—
 * sin tocar al repositorio que las combina.
 */
internal class CalidadProveedorApiDataSource(private val api: Api) {

    suspend fun misAnalisis(): List<AnalisisCalidadDto> {
        val datos = api.request("mobile/proveedor/calidad").getJSONArray("data")

        return (0 until datos.length())
            .map { indice -> datos.get(indice) as JSONObject }
            .map { AnalisisCalidadDto(it) }
    }
}
