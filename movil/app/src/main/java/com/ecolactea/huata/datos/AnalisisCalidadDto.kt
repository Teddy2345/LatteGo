package com.ecolactea.huata.datos

import com.ecolactea.huata.dominio.AnalisisCalidad
import com.ecolactea.huata.dominio.ValorMedido
import org.json.JSONObject

/**
 * El análisis tal como lo manda el servidor.
 *
 * Tiene la forma del JSON, con sus rarezas: el resultado llega como texto
 * ("aceptada" / "rechazada"), la sanción puede venir como "ninguna" y los
 * nulos llegan como la cadena "null". Toda esa suciedad muere aquí.
 */
internal class AnalisisCalidadDto(private val json: JSONObject) {

    /**
     * Traduce el JSON al modelo de dominio. Este es el único punto de la app
     * que conoce los nombres de los campos del servidor: si mañana `sancion`
     * pasa a llamarse `castigo`, se cambia en esta línea y en ninguna más.
     */
    fun aDominio(): AnalisisCalidad = AnalisisCalidad(
        id = json.optInt("id"),
        acopioId = json.optInt("acopio_id"),
        fecha = json.optString("fecha"),
        aceptada = json.optString("resultado") == "aceptada",
        motivoRechazo = json.textoONulo("motivo_rechazo"),
        // "ninguna" significa que no hubo castigo: en el dominio eso es null,
        // para no obligar a cada pantalla a conocer esa convención.
        sancion = json.textoONulo("sancion")?.takeIf { it != "ninguna" },
        valores = json.optJSONArray("valores").let { lista ->
            if (lista == null) emptyList()
            else (0 until lista.length()).map { indice ->
                val valor = lista.getJSONObject(indice)
                ValorMedido(
                    nombre = valor.optString("nombre"),
                    valor = valor.optDouble("valor", 0.0),
                    unidad = valor.optString("unidad"),
                )
            }
        },
    )
}

/** Un campo ausente, vacío o con la cadena "null" vale lo mismo: nada. */
private fun JSONObject.textoONulo(clave: String): String? =
    optString(clave).takeIf { it.isNotBlank() && it != "null" }
