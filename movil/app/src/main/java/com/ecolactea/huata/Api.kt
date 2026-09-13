package com.ecolactea.huata

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URI

class ApiError(val status: Int, message: String) : Exception(message)

class Api(val baseUrl: String, val token: String = "") {
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
                    throw ApiError(status, when {
                        status == 401 -> "La sesión venció. Vuelve a ingresar."
                        status == 403 -> "Tu rol no tiene permiso para esta operación."
                        status == 429 -> "Demasiados intentos. Espera un minuto antes de volver a ingresar."
                        details.isNotBlank() -> details
                        else -> "No se pudo completar la operación (HTTP $status)."
                    })
                }
                if (raw.isNotBlank() && json.length() == 0) throw ApiError(0, "El servidor no devolvió JSON. Revisa la dirección de la API.")
                json
            } finally { connection.disconnect() }
        }

    suspend fun list(path: String): List<JSONObject> {
        val array = request(path).getJSONArray("data")
        return (0 until array.length()).map { array.getJSONObject(it) }
    }
}
