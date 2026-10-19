package com.ecolactea.huata

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONObject
import java.io.File
import java.net.HttpURLConnection
import java.net.URI

/** `codigo` es la clave que manda el servidor para errores que la app trata aparte (p. ej. `requiere_2fa`). */
class ApiError(val status: Int, message: String, val codigo: String? = null) : Exception(message)

class Api(val baseUrl: String, val token: String = "", val dispositivoId: String = "") {
    companion object {
        fun normalize(address: String): String {
            val value = address.trim().trimEnd('/')
            val uri = runCatching { URI(value) }.getOrNull()
            require(uri != null && uri.scheme in listOf("http", "https") && !uri.host.isNullOrBlank()
                && uri.userInfo == null && uri.query == null && uri.fragment == null) {
                "Escribe una dirección válida, por ejemplo http://10.0.2.2:8001"
            }
            require(uri.path.orEmpty() in listOf("", "/api/v1")) { "Usa la dirección del servidor sin /login." }
            return if (value.endsWith("/api/v1")) value else "$value/api/v1"
        }
    }

    suspend fun request(path: String, method: String = "GET", body: JSONObject? = null): JSONObject =
        withContext(Dispatchers.IO) {
            val connection = URI("$baseUrl/$path").toURL().openConnection() as HttpURLConnection
            try {
                connection.requestMethod = method
                connection.instanceFollowRedirects = false
                connection.connectTimeout = 10000
                connection.readTimeout = 20000
                connection.setRequestProperty("Accept", "application/json")
                if (token.isNotEmpty()) connection.setRequestProperty("Authorization", "Bearer $token")
                // Identifica al teléfono para que su vinculación de 2FA no
                // dependa de la sesión, que se renueva en cada inicio.
                if (dispositivoId.isNotEmpty()) connection.setRequestProperty("X-Dispositivo", dispositivoId)
                if (body != null) {
                    connection.doOutput = true
                    connection.setRequestProperty("Content-Type", "application/json; charset=UTF-8")
                    connection.outputStream.use { it.write(body.toString().toByteArray(Charsets.UTF_8)) }
                }
                val status = connection.responseCode
                val raw = (if (status in 200..299) connection.inputStream else connection.errorStream)
                    ?.bufferedReader()?.use { it.readText() }.orEmpty()
                val json = runCatching { JSONObject(raw) }.getOrDefault(JSONObject())
                if (status !in 200..299) {
                    val errors = json.optJSONObject("errors")
                    val details = errors?.keys()?.asSequence()?.map { key ->
                        errors.optJSONArray(key)?.join("\n")?.replace("\"", "").orEmpty()
                    }?.joinToString("\n").orEmpty()
                    // Laravel responde este texto genérico cuando una policy niega; los 403 con motivo propio se muestran tal cual.
                    val motivo = json.optString("message").takeIf { it.isNotBlank() && it != "This action is unauthorized." }
                    throw ApiError(status, when {
                        status == 401 -> "La sesión venció. Vuelve a ingresar."
                        status == 403 -> motivo ?: "Tu rol no tiene permiso para esta operación."
                        status == 429 -> "Demasiados intentos. Espera un minuto antes de volver a ingresar."
                        details.isNotBlank() -> details
                        status in 400..499 && motivo != null -> motivo
                        else -> "No se pudo completar la operación (HTTP $status)."
                    }, json.optString("codigo").ifBlank { null })
                }
                if (raw.isNotBlank() && json.length() == 0) throw ApiError(0, "El servidor no devolvió JSON. Revisa la dirección de la API.")
                json
            } finally { connection.disconnect() }
        }

    /**
     * Envía un formulario con una foto adjunta. Se usa para el análisis de
     * calidad: los valores ya confirmados van como campos y la foto de la
     * pantalla del LactoScan viaja como evidencia.
     */
    suspend fun upload(
        path: String,
        campos: Map<String, String>,
        archivo: File? = null,
        nombreCampo: String = "foto",
    ): JSONObject = withContext(Dispatchers.IO) {
        val frontera = "----EcolacteaHuata" + System.currentTimeMillis()
        val connection = URI("$baseUrl/$path").toURL().openConnection() as HttpURLConnection
        try {
            connection.requestMethod = "POST"
            connection.instanceFollowRedirects = false
            connection.connectTimeout = 10000
            connection.readTimeout = 60000
            connection.setRequestProperty("Accept", "application/json")
            if (token.isNotEmpty()) connection.setRequestProperty("Authorization", "Bearer $token")
            if (dispositivoId.isNotEmpty()) connection.setRequestProperty("X-Dispositivo", dispositivoId)
            connection.setRequestProperty("Content-Type", "multipart/form-data; boundary=$frontera")
            connection.doOutput = true

            connection.outputStream.use { salida ->
                fun escribir(texto: String) = salida.write(texto.toByteArray(Charsets.UTF_8))

                campos.forEach { (clave, valor) ->
                    escribir("--$frontera\r\n")
                    escribir("Content-Disposition: form-data; name=\"$clave\"\r\n\r\n")
                    escribir("$valor\r\n")
                }

                if (archivo != null && archivo.exists()) {
                    escribir("--$frontera\r\n")
                    escribir("Content-Disposition: form-data; name=\"$nombreCampo\"; filename=\"${archivo.name}\"\r\n")
                    escribir("Content-Type: image/jpeg\r\n\r\n")
                    archivo.inputStream().use { it.copyTo(salida) }
                    escribir("\r\n")
                }

                escribir("--$frontera--\r\n")
            }

            val status = connection.responseCode
            val raw = (if (status in 200..299) connection.inputStream else connection.errorStream)
                ?.bufferedReader()?.use { it.readText() }.orEmpty()
            val json = runCatching { JSONObject(raw) }.getOrDefault(JSONObject())
            if (status !in 200..299) {
                val errors = json.optJSONObject("errors")
                val details = errors?.keys()?.asSequence()?.map { key ->
                    errors.optJSONArray(key)?.join("\n")?.replace("\"", "").orEmpty()
                }?.joinToString("\n").orEmpty()
                val motivo = json.optString("message").takeIf { it.isNotBlank() && it != "This action is unauthorized." }
                throw ApiError(status, when {
                    status == 401 -> "La sesión venció. Vuelve a ingresar."
                    status == 403 -> motivo ?: "Tu rol no tiene permiso para esta operación."
                    details.isNotBlank() -> details
                    motivo != null -> motivo
                    else -> "No se pudo enviar (HTTP $status)."
                }, json.optString("codigo").ifBlank { null })
            }
            json
        } finally { connection.disconnect() }
    }

    suspend fun list(path: String): List<JSONObject> {
        val array = request(path).getJSONArray("data")
        return (0 until array.length()).map { array.getJSONObject(it) }
    }
}
