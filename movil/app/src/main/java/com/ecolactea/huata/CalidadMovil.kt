package com.ecolactea.huata

import android.net.Uri
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.core.content.FileProvider
import com.google.mlkit.vision.common.InputImage
import com.google.mlkit.vision.text.TextRecognition
import com.google.mlkit.vision.text.latin.TextRecognizerOptions
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.launch
import org.json.JSONObject
import java.io.File
import java.time.LocalDate

/**
 * Los valores que devuelve el LactoScan, en el orden en que se registran.
 *
 * `claves` son los rótulos con los que el equipo puede nombrarlos: se
 * aceptan varios porque el mismo aparato cambia de idioma según cómo esté
 * configurado.
 */
private data class CampoCalidad(
    val nombre: String,
    val etiqueta: String,
    val unidad: String,
    val porDefecto: String,
    val claves: List<String>,
)

private val CAMPOS_CALIDAD = listOf(
    CampoCalidad("grasa", "Grasa", "%", "3.5", listOf("FAT", "GRASA")),
    CampoCalidad("solidos_no_grasos", "Sólidos no grasos", "%", "8.5", listOf("SNF", "MSNF", "SOLIDOS NO GRASOS", "SOLIDS NON FAT")),
    CampoCalidad("densidad", "Densidad", "", "30.0", listOf("DENSITY", "DENSIDAD", "CLR")),
    CampoCalidad("proteina", "Proteína", "%", "3.2", listOf("PROTEIN", "PROTEINA")),
    CampoCalidad("lactosa", "Lactosa", "%", "4.6", listOf("LACTOSE", "LACTOSA")),
    CampoCalidad("sales", "Sales", "%", "0.7", listOf("SALTS", "SALES", "MINERALS", "MINERALES")),
    CampoCalidad("agua_agregada", "Agua agregada", "%", "0", listOf("ADDED WATER", "AGUA AGREGADA", "WATER", "AGUA")),
    CampoCalidad("temperatura", "Temperatura", "°C", "4.0", listOf("TEMP", "TEMPERATURE", "TEMPERATURA")),
    CampoCalidad("ph", "pH", "", "6.7", listOf("PH")),
)

/**
 * Lee la pantalla del LactoScan.
 *
 * Busca cada rótulo y se queda con el primer número que lo acompaña, en su
 * línea o en la siguiente, porque algunos equipos imprimen el valor debajo
 * del nombre. Lo que no reconoce lo deja como estaba: nada se guarda sin que
 * quien analiza lo revise en pantalla.
 */
internal fun leerLactoScan(texto: String): Map<String, String> {
    val lineas = texto.uppercase()
        .replace(',', '.')
        .lines()
        .map { it.trim() }
        .filter { it.isNotEmpty() }

    val numero = Regex("-?\\d+(?:\\.\\d+)?")
    val encontrados = mutableMapOf<String, String>()

    lineas.forEachIndexed { indice, linea ->
        CAMPOS_CALIDAD.forEach { campo ->
            if (campo.nombre in encontrados) return@forEach

            // "PH" aparece dentro de otras palabras, así que se exige que
            // esté suelto; los demás rótulos son suficientemente largos.
            val clave = campo.claves.firstOrNull { clave ->
                if (clave == "PH") Regex("(^|[^A-Z])PH([^A-Z]|$)").containsMatchIn(linea) else linea.contains(clave)
            } ?: return@forEach

            val resto = linea.substringAfter(clave, "")
            val valor = numero.find(resto)?.value
                ?: lineas.getOrNull(indice + 1)?.let { numero.find(it)?.value }

            if (valor != null) encontrados[campo.nombre] = valor
        }
    }

    return encontrados
}

/**
 * Calidad desde el teléfono: se elige la entrega por el nombre de quien la
 * trajo, se le toma una foto a la pantalla del LactoScan y los valores
 * reconocidos se revisan antes de guardarlos.
 */
@Composable
internal fun CalidadMovilScreen(api: Api, alGuardar: (String) -> Unit) {
    var pendientes by remember { mutableStateOf<List<JSONObject>>(emptyList()) }
    var elegida by remember { mutableStateOf<JSONObject?>(null) }
    var error by remember { mutableStateOf<String?>(null) }
    var cargando by remember { mutableStateOf(true) }
    var revision by remember { mutableStateOf(0) }

    LaunchedEffect(revision) {
        cargando = true
        try {
            val datos = api.request("mobile/calidad/pendientes").getJSONArray("data")
            pendientes = (0 until datos.length()).map { datos.getJSONObject(it) }
            error = null
        } catch (e: CancellationException) {
            throw e
        } catch (e: Exception) {
            error = if (e is ApiError) e.message else "No se pudieron cargar las entregas."
        } finally {
            cargando = false
        }
    }

    val entrega = elegida

    if (entrega != null) {
        AnalisisDeEntrega(
            api = api,
            entrega = entrega,
            alVolver = { elegida = null },
            alGuardar = { resultado ->
                elegida = null
                revision++
                alGuardar(resultado)
            },
        )
        return
    }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(20.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Text("Análisis de calidad", fontSize = 24.sp, fontWeight = FontWeight.Bold, color = DarkGreen)
            Text("Elige la entrega que vas a analizar.", color = Gray, fontSize = 13.sp)
        }
        error?.let { mensaje -> item { Text(mensaje, color = MaterialTheme.colorScheme.error, fontSize = 13.sp) } }
        if (cargando) item { Text("Cargando…", color = Gray) }
        if (!cargando && error == null && pendientes.isEmpty()) {
            item { Text("No hay entregas esperando análisis.", color = Gray, fontSize = 13.sp) }
        }
        items(pendientes) { fila ->
            Card(
                onClick = { elegida = fila },
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(18.dp),
                border = BorderStroke(1.dp, Line),
                colors = CardDefaults.cardColors(containerColor = Color.White),
            ) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    Text(fila.optString("proveedor"), fontSize = 16.sp, fontWeight = FontWeight.SemiBold, color = DarkGreen)
                    Text(
                        fila.optString("fecha") + " · " + cantidadCorta(fila.optDouble("litros")) + " L · entrega #" + fila.optInt("id"),
                        color = Gray, fontSize = 12.sp,
                    )
                }
            }
        }
    }
}

@Composable
private fun AnalisisDeEntrega(
    api: Api,
    entrega: JSONObject,
    alVolver: () -> Unit,
    alGuardar: (String) -> Unit,
) {
    val contexto = LocalContext.current
    val scope = rememberCoroutineScope()

    val valores = remember {
        mutableStateMapOf<String, String>().apply {
            CAMPOS_CALIDAD.forEach { put(it.nombre, it.porDefecto) }
        }
    }
    var alcoholAceptada by remember { mutableStateOf(true) }
    var foto by remember { mutableStateOf<File?>(null) }
    var aviso by remember { mutableStateOf<String?>(null) }
    var error by remember { mutableStateOf<String?>(null) }
    var enviando by remember { mutableStateOf(false) }
    var leyendo by remember { mutableStateOf(false) }

    // La foto se guarda en la caché de la app y se le entrega a la cámara
    // por el FileProvider: ninguna otra app ve el resto de los archivos.
    val archivo = remember {
        File(contexto.cacheDir, "capturas").apply { mkdirs() }
            .let { File(it, "lactoscan.jpg") }
    }
    val destino: Uri = remember {
        FileProvider.getUriForFile(contexto, contexto.packageName + ".archivos", archivo)
    }

    val camara = rememberLauncherForActivityResult(ActivityResultContracts.TakePicture()) { tomada ->
        if (!tomada) return@rememberLauncherForActivityResult

        foto = archivo
        leyendo = true
        aviso = null
        error = null

        TextRecognition.getClient(TextRecognizerOptions.DEFAULT_OPTIONS)
            .process(InputImage.fromFilePath(contexto, destino))
            .addOnSuccessListener { texto ->
                val leidos = leerLactoScan(texto.text)
                leidos.forEach { (campo, valor) -> valores[campo] = valor }
                leyendo = false
                aviso = if (leidos.isEmpty()) {
                    "No se reconoció ningún valor. Escríbelos a mano; la foto igual queda guardada."
                } else {
                    "Se reconocieron " + leidos.size + " de " + CAMPOS_CALIDAD.size + " valores. Revísalos antes de guardar."
                }
            }
            .addOnFailureListener {
                leyendo = false
                aviso = "No se pudo leer la foto. Escribe los valores a mano."
            }
    }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(20.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            TextButton(onClick = alVolver) { Text("← Elegir otra entrega") }
            Text(entrega.optString("proveedor"), fontSize = 22.sp, fontWeight = FontWeight.Bold, color = DarkGreen)
            Text(
                entrega.optString("fecha") + " · " + cantidadCorta(entrega.optDouble("litros")) + " L · entrega #" + entrega.optInt("id"),
                color = Gray, fontSize = 13.sp,
            )
        }

        item {
            Card(
                Modifier.fillMaxWidth(), shape = RoundedCornerShape(18.dp), border = BorderStroke(1.dp, Gold),
                colors = CardDefaults.cardColors(containerColor = Color.White),
            ) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text("FOTO DEL LACTOSCAN", color = Gray, fontSize = 10.sp, letterSpacing = 1.2.sp)
                    Text(
                        "Enfoca la pantalla del equipo. La app lee los valores y los deja escritos abajo para que los revises.",
                        color = Gray, fontSize = 12.sp,
                    )
                    Button(
                        enabled = !leyendo && !enviando,
                        modifier = Modifier.fillMaxWidth(),
                        onClick = { camara.launch(destino) },
                    ) { Text(if (foto == null) "Tomar la foto" else "Repetir la foto") }

                    if (leyendo) Text("Leyendo la pantalla…", color = Gray, fontSize = 12.sp)
                    aviso?.let { Text(it, color = Green, fontSize = 12.sp) }
                }
            }
        }

        items(CAMPOS_CALIDAD) { campo ->
            OutlinedTextField(
                value = valores[campo.nombre].orEmpty(),
                onValueChange = { valores[campo.nombre] = it },
                label = { Text(campo.etiqueta + if (campo.unidad.isEmpty()) "" else " (" + campo.unidad + ")") },
                singleLine = true,
                shape = RoundedCornerShape(14.dp),
                modifier = Modifier.fillMaxWidth(),
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
            )
        }

        item {
            Row(Modifier.fillMaxWidth(), verticalAlignment = androidx.compose.ui.Alignment.CenterVertically) {
                Switch(checked = alcoholAceptada, onCheckedChange = { alcoholAceptada = it })
                Spacer(Modifier.width(12.dp))
                Column {
                    Text("Prueba de alcohol", color = DarkGreen, fontSize = 14.sp, fontWeight = FontWeight.Medium)
                    Text(
                        if (alcoholAceptada) "Pasó la prueba" else "No pasó: la leche se rechaza",
                        color = Gray, fontSize = 12.sp,
                    )
                }
            }
        }

        error?.let { mensaje -> item { Text(mensaje, color = MaterialTheme.colorScheme.error, fontSize = 13.sp) } }

        item {
            Button(
                enabled = !enviando && !leyendo,
                modifier = Modifier.fillMaxWidth(),
                onClick = {
                    enviando = true
                    error = null
                    scope.launch {
                        try {
                            val campos = mutableMapOf(
                                "acopio_id" to entrega.optInt("id").toString(),
                                "proveedor_id" to entrega.optInt("proveedor_id").toString(),
                                "fecha" to LocalDate.now().toString(),
                                "prueba_alcohol_aceptada" to if (alcoholAceptada) "1" else "0",
                            )
                            CAMPOS_CALIDAD.forEach { campo ->
                                campos[campo.nombre] = valores[campo.nombre].orEmpty().trim().replace(',', '.')
                            }

                            val respuesta = api.upload("mobile/calidad", campos, foto)
                            alGuardar(respuesta.optJSONObject("data")?.optString("resultado").orEmpty())
                        } catch (e: CancellationException) {
                            throw e
                        } catch (e: Exception) {
                            error = if (e is ApiError) e.message else "No se pudo guardar el análisis."
                        } finally {
                            enviando = false
                        }
                    }
                },
            ) { Text(if (enviando) "Guardando…" else "Guardar análisis") }
        }
    }
}
