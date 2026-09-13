package com.ecolactea.huata

import android.content.Context
import android.net.ConnectivityManager
import android.net.NetworkCapabilities
import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import android.util.Base64
import org.json.JSONArray
import org.json.JSONObject
import java.io.File
import java.security.KeyStore
import javax.crypto.Cipher
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey
import javax.crypto.spec.GCMParameterSpec

/**
 * Sesión guardada en el dispositivo y cifrada con el almacén de claves de
 * Android: el acopiador no vuelve a escribir su contraseña cada vez que el
 * sistema recicla la app en medio de la jornada.
 *
 * La clave vive en el Keystore y nunca sale de él, y `allowBackup` está
 * desactivado en el manifiesto, así que el token no viaja en las copias de
 * seguridad del teléfono.
 */
class SecureStore(context: Context) {
    private val prefs = context.getSharedPreferences("sesion", Context.MODE_PRIVATE)

    fun save(token: String, user: JSONObject) {
        runCatching {
            prefs.edit()
                .putString(TOKEN, cifrar(token))
                .putString(USER, cifrar(user.toString()))
                .apply()
        }
    }

    /** Devuelve token y usuario, o null si no hay sesión utilizable. */
    fun load(): Pair<String, JSONObject>? = runCatching {
        val token = descifrar(prefs.getString(TOKEN, null) ?: return null)
        val user = JSONObject(descifrar(prefs.getString(USER, null) ?: return null))
        if (token.isBlank()) null else token to user
    }.getOrElse {
        // La clave se invalidó (cambio de bloqueo de pantalla, restauración…):
        // se descarta la sesión y se pide iniciar de nuevo.
        clear()
        null
    }

    fun clear() = prefs.edit().remove(TOKEN).remove(USER).apply()

    private fun cifrar(valor: String): String {
        val cipher = Cipher.getInstance(TRANSFORMACION).apply { init(Cipher.ENCRYPT_MODE, clave()) }
        val datos = cipher.doFinal(valor.toByteArray(Charsets.UTF_8))
        return Base64.encodeToString(cipher.iv, Base64.NO_WRAP) + ":" + Base64.encodeToString(datos, Base64.NO_WRAP)
    }

    private fun descifrar(guardado: String): String {
        val (iv, datos) = guardado.split(":").let {
            Base64.decode(it[0], Base64.NO_WRAP) to Base64.decode(it[1], Base64.NO_WRAP)
        }
        val cipher = Cipher.getInstance(TRANSFORMACION).apply {
            init(Cipher.DECRYPT_MODE, clave(), GCMParameterSpec(128, iv))
        }
        return String(cipher.doFinal(datos), Charsets.UTF_8)
    }

    private fun clave(): SecretKey {
        val keystore = KeyStore.getInstance("AndroidKeyStore").apply { load(null) }
        (keystore.getEntry(ALIAS, null) as? KeyStore.SecretKeyEntry)?.let { return it.secretKey }

        return KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, "AndroidKeyStore").apply {
            init(
                KeyGenParameterSpec.Builder(ALIAS, KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT)
                    .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
                    .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
                    .build()
            )
        }.generateKey()
    }

    private companion object {
        const val ALIAS = "ecolactea.sesion"
        const val TRANSFORMACION = "AES/GCM/NoPadding"
        const val TOKEN = "token"
        const val USER = "usuario"
    }
}

/**
 * Acopios registrados sin señal. Se guardan tal como se enviarán al servidor
 * —cada uno con su `request_id`— para que reintentar el envío nunca duplique
 * una recepción: el backend reconoce esa clave y devuelve el acopio ya
 * registrado en lugar de crear otro.
 */
class OfflineQueue(context: Context) {
    private val archivo = File(context.filesDir, "acopios_pendientes.json")

    fun all(): List<JSONObject> = runCatching {
        if (!archivo.exists()) return emptyList()
        JSONArray(archivo.readText()).let { a -> (0 until a.length()).map { a.getJSONObject(it) } }
    }.getOrDefault(emptyList())

    fun add(payload: JSONObject) = guardar(all() + payload)

    fun remove(requestId: String) = guardar(all().filterNot { it.optString("request_id") == requestId })

    fun size(): Int = all().size

    private fun guardar(pendientes: List<JSONObject>) {
        runCatching { archivo.writeText(JSONArray(pendientes).toString()) }
    }
}

/**
 * Copia del catálogo de proveedores y zonas para que el formulario de campo
 * se pueda llenar aunque no haya cobertura en la comunidad.
 */
class CatalogCache(context: Context) {
    private val archivo = File(context.filesDir, "catalogo.json")

    fun save(catalogo: JSONObject) {
        runCatching { archivo.writeText(catalogo.toString()) }
    }

    fun load(): JSONObject? = runCatching {
        if (archivo.exists()) JSONObject(archivo.readText()) else null
    }.getOrNull()
}

fun hayConexion(context: Context): Boolean {
    val manager = context.getSystemService(Context.CONNECTIVITY_SERVICE) as? ConnectivityManager ?: return true
    val red = manager.activeNetwork ?: return false
    val capacidades = manager.getNetworkCapabilities(red) ?: return false
    return capacidades.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET)
}
