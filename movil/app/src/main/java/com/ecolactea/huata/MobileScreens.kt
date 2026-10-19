package com.ecolactea.huata

import android.Manifest
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.location.Location
import android.location.LocationListener
import android.location.LocationManager
import android.net.Uri
import android.os.Looper
import android.os.SystemClock
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.content.ContextCompat
import kotlinx.coroutines.*
import org.json.JSONObject
import java.time.Instant
import java.time.LocalDate
import java.time.ZoneOffset
import java.time.format.DateTimeFormatter
import java.util.UUID

internal fun actions(user: JSONObject): Set<String> = user.optJSONArray("acciones")?.let { a ->
    (0 until a.length()).map { a.getString(it) }.toSet()
}.orEmpty()

/** Solo admin y supervisor tienen 2FA obligatorio, y solo a ellos les sirve la campana. */
internal fun requiereDosFactores(user: JSONObject): Boolean = user.optJSONArray("roles")?.let { r ->
    (0 until r.length()).any { r.getString(it) in setOf("admin", "supervisor") }
} == true

internal fun allowedModules(user: JSONObject): Map<String, String> {
    val a = actions(user)
    return linkedMapOf<String,String>().apply {
        if ("acopio" in a || "consolidado" in a) put("acopios", if ("consolidado" in a) "Acopios" else "Mis acopios")
        if ("consolidado" in a) put("consolidado", "Reporte por zonas")
        if ("planta" in a) put("produccion", "Producción")
        if ("ventas" in a) put("ventas", "Ventas")
        if ("stock" in a) put("stock", "Stock")
        if ("proveedores" in a) put("proveedores", "Proveedores")
        if ("calidad" in a) put("calidad", "Calidad")
        if ("despachos" in a) put("despachos", "Despachos")
        if ("pagos" in a) put("pagos", "Liquidaciones")
        if ("auditoria" in a) put("auditoria", "Auditoría")
    }
}

internal fun modulePath(id: String): String = when(id) {
    "acopios", "produccion", "ventas", "auditoria" -> "mobile/$id"
    "stock" -> "mobile/productos"
    else -> id
}

@Composable
internal fun Choice(label: String, records: List<JSONObject>, selected: Int?, onSelect: (Int) -> Unit, enabled: Boolean, placeholder: String = "Seleccionar…") {
    var open by remember { mutableStateOf(false) }
    var search by remember { mutableStateOf("") }
    OutlinedCard(onClick = { open = true }, enabled = enabled, modifier = Modifier.fillMaxWidth()) {
        Column(Modifier.padding(16.dp)) {
            Text(label, color = Gray, fontSize = 12.sp)
            Text(records.firstOrNull { it.optInt("id") == selected }?.optString("nombre") ?: placeholder, color = DarkGreen)
        }
    }
    if (open) AlertDialog(onDismissRequest = { open = false }, title = { Text(label) },
        text = {
            Column {
                OutlinedTextField(search, { search = it }, label = { Text("Buscar") }, modifier = Modifier.fillMaxWidth())
                LazyColumn(Modifier.heightIn(max = 320.dp)) {
                    items(records.filter { it.optString("nombre").contains(search, true) }) { r ->
                        TextButton(onClick = { onSelect(r.getInt("id")); open = false }, modifier = Modifier.fillMaxWidth()) { Text(r.getString("nombre")) }
                    }
                }
            }
        }, confirmButton = { TextButton(onClick = { open = false }) { Text("Cerrar") } })
}

internal fun JSONObject.arrayRows(key: String): List<JSONObject> = getJSONArray(key).let { a -> (0 until a.length()).map { a.getJSONObject(it) } }

/**
 * Selector de fecha del sistema. Escribir "AAAA-MM-DD" a mano en el campo,
 * con guantes y a la intemperie, era una fuente constante de errores.
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
internal fun DateChoice(label: String, value: String, onSelect: (String) -> Unit, enabled: Boolean, hastaHoy: Boolean = false) {
    var open by remember { mutableStateOf(false) }
    val fecha = runCatching { LocalDate.parse(value) }.getOrNull()
    OutlinedCard(onClick = { open = true }, enabled = enabled, modifier = Modifier.fillMaxWidth()) {
        Column(Modifier.padding(16.dp)) {
            Text(label, color = Gray, fontSize = 12.sp)
            Text(
                fecha?.format(DateTimeFormatter.ofPattern("dd/MM/yyyy")) ?: "Seleccionar…",
                color = DarkGreen
            )
        }
    }
    if (open) {
        // Un acopio, una venta o una producción no pueden quedar con fecha futura.
        val limite = LocalDate.now().atStartOfDay(ZoneOffset.UTC).toInstant().toEpochMilli()
        val estado = rememberDatePickerState(
            initialSelectedDateMillis = fecha?.atStartOfDay(ZoneOffset.UTC)?.toInstant()?.toEpochMilli(),
            selectableDates = object : SelectableDates {
                override fun isSelectableDate(utcTimeMillis: Long) = !hastaHoy || utcTimeMillis <= limite
                override fun isSelectableYear(year: Int) = !hastaHoy || year <= LocalDate.now().year
            },
        )
        DatePickerDialog(
            onDismissRequest = { open = false },
            confirmButton = {
                TextButton(onClick = {
                    estado.selectedDateMillis?.let {
                        onSelect(Instant.ofEpochMilli(it).atZone(ZoneOffset.UTC).toLocalDate().toString())
                    }
                    open = false
                }) { Text("Aceptar") }
            },
            dismissButton = { TextButton(onClick = { open = false }) { Text("Cancelar") } }
        ) { DatePicker(state = estado) }
    }
}

/**
 * Descarga el catálogo mínimo (proveedores y zonas) que arma los selectores
 * de campo. En la comunidad puede no haber cobertura, así que si la petición
 * falla se cae a la última copia guardada en el teléfono.
 */
internal suspend fun cargarCatalogo(api: Api, cache: CatalogCache): Pair<JSONObject?, String?> = try {
    val recibido = api.request("mobile/catalogo").getJSONObject("data")
    cache.save(recibido)
    recibido to null
} catch (e: CancellationException) { throw e }
catch (e: Exception) {
    val guardado = cache.load()
    guardado to (if (guardado != null) "Sin conexión: se usa el último catálogo guardado en el teléfono."
        else "No se pudieron cargar las zonas y proveedores. Reintenta con señal.")
}

/** Lista de proveedores para elegir con quién se está trabajando; primer paso del flujo de acopio. */
@Composable
internal fun ProveedorPicker(api: Api, busy: Boolean, onSelect: (JSONObject) -> Unit) {
    var catalog by remember { mutableStateOf<JSONObject?>(null) }
    var error by remember { mutableStateOf<String?>(null) }
    var retry by remember { mutableIntStateOf(0) }
    var search by rememberSaveable { mutableStateOf("") }
    val context = LocalContext.current
    val cache = remember { CatalogCache(context) }
    LaunchedEffect(retry) { val (c, e) = cargarCatalogo(api, cache); catalog = c; error = e }
    val proveedores = catalog?.arrayRows("proveedores").orEmpty().filter { it.optString("nombre").contains(search, true) }
    Column(Modifier.fillMaxSize().padding(20.dp), verticalArrangement = Arrangement.spacedBy(14.dp)) {
        Text("Registrar acopio", fontSize = 25.sp, color = DarkGreen, fontWeight = FontWeight.Bold)
        Text("Elige al proveedor para ver sus datos y registrar el litraje.", color = Gray)
        OutlinedTextField(search, { search = it }, modifier = Modifier.fillMaxWidth(), singleLine = true,
            shape = RoundedCornerShape(16.dp), leadingIcon = { Glyph("search", Gray, Modifier.size(20.dp)) },
            label = { Text("Buscar proveedor") })
        if (catalog == null && error == null) Text("Cargando…", color = Gray)
        error?.let { Message(it, catalog == null); if (catalog == null) TextButton(onClick = { retry++ }) { Text("Reintentar") } }
        LazyColumn(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            // Cada acopiador recibe solo a los proveedores de la ruta de su camión.
            val sinMovilidad = catalog?.optBoolean("sin_movilidad") == true
            val rutaVacia = catalog != null && catalog!!.arrayRows("proveedores").isEmpty()
            when {
                sinMovilidad -> item { Message("Todavía no tienes un camión asignado. Pide a un administrador que te asigne tu movilidad y tu ruta.", false) }
                rutaVacia -> item { Message("Tu ruta todavía no tiene proveedores activos.", false) }
                catalog != null && proveedores.isEmpty() -> item { Message("Ningún proveedor coincide con «$search».", false) }
            }
            items(proveedores, key = { it.getInt("id") }) { p ->
                Card(onClick = { onSelect(p) }, enabled = !busy, border = BorderStroke(1.dp, Line), shape = RoundedCornerShape(18.dp),
                    colors = CardDefaults.cardColors(containerColor = Color.White)) {
                    Row(Modifier.fillMaxWidth().padding(16.dp), verticalAlignment = Alignment.CenterVertically) {
                        Text(p.getString("nombre"), Modifier.weight(1f), color = DarkGreen, fontWeight = FontWeight.SemiBold)
                        Glyph("arrow", Green, Modifier.size(16.dp))
                    }
                }
            }
        }
    }
}

/** Ficha del proveedor: sus datos completos, su historial de litraje y las acciones de campo. */
@Composable
internal fun ProveedorDetalle(proveedor: JSONObject, busy: Boolean, onNuevoLitraje: () -> Unit) {
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp), verticalArrangement = Arrangement.spacedBy(16.dp)) {
        Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
            Text(proveedor.getString("nombre"), fontSize = 25.sp, color = DarkGreen, fontWeight = FontWeight.Bold)
            Text(if (proveedor.optBoolean("activo", true)) "Proveedor activo" else "Proveedor inactivo", color = Gray, fontSize = 12.sp)
        }
        Card(shape = RoundedCornerShape(20.dp), border = BorderStroke(1.dp, Line), colors = CardDefaults.cardColors(containerColor = Color.White)) {
            Column(Modifier.fillMaxWidth().padding(20.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                Dato("Cédula", value(proveedor.opt("cedula")))
                Dato("Teléfono", value(proveedor.opt("telefono")))
                Dato("Finca", value(proveedor.opt("finca")))
                Dato("Litros promedio", "${proveedor.optDouble("litros_prom")} L")
                Dato("Precio por litro", "S/ ${proveedor.optDouble("precio_litro")}")
                Dato("Zona actual", if (proveedor.isNull("zona")) "Sin zona asignada" else proveedor.getString("zona"), last = true)
            }
        }
        Button(onClick = onNuevoLitraje, enabled = !busy, modifier = Modifier.fillMaxWidth()) { Text("+ Registrar nuevo litraje") }
        Text("Litraje de entregas anteriores", color = DarkGreen, fontWeight = FontWeight.SemiBold, fontSize = 16.sp)
        val historial = proveedor.optJSONArray("historial")
        if (historial == null || historial.length() == 0) {
            Message("Este proveedor todavía no tiene entregas registradas.", false)
        } else (0 until historial.length()).forEach { i ->
            val h = historial.getJSONObject(i)
            Card(border = BorderStroke(1.dp, Line), shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
                Column(Modifier.fillMaxWidth().padding(14.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                        Text(h.optString("fecha"), color = DarkGreen, fontWeight = FontWeight.SemiBold)
                        Text("${h.optDouble("cantidad_litros")} L", color = Green, fontWeight = FontWeight.Bold)
                    }
                    Text(if (h.isNull("zona")) "Sin zona asignada" else h.getString("zona"), color = Gray, fontSize = 12.sp)
                    if (h.optDouble("perdida_litros", 0.0) > 0.0)
                        Text("Pérdida: ${h.optDouble("perdida_litros")} L · ${h.optString("motivo_perdida")}", color = Gray, fontSize = 12.sp)
                }
            }
        }
    }
}

@Composable
private fun Dato(label: String, valor: String, last: Boolean = false) {
    Column { Text(label, color = Green, fontSize = 12.sp); Text(valor, fontSize = 15.sp) }
    if (!last) HorizontalDivider(color = PaleGreen)
}

/**
 * Envía un acopio. Reintentar es seguro: el `request_id` evita duplicarlo.
 *
 * El cambio de zona ya no viaja aquí: lo pide el propio proveedor desde su
 * app, no el acopiador en nombre suyo.
 */
internal suspend fun enviarAcopio(api: Api, payload: JSONObject) {
    api.request("mobile/acopios", "POST", payload)
}

/**
 * Registra un nuevo litraje para un proveedor ya elegido. Solo se piden la
 * fecha y los litros: la ubicación se toma sola al guardar, y si el proveedor
 * se mudó, el cambio de zona se pide aquí mismo y viaja junto con el acopio
 * (queda pendiente hasta que un administrador lo apruebe).
 */
@Composable
internal fun CampoForm(api: Api, proveedor: JSONObject, busy: Boolean, onSave: (JSONObject) -> Unit) {
    var liters by rememberSaveable { mutableStateOf("") }
    var date by rememberSaveable { mutableStateOf(LocalDate.now().toString()) }
    var error by remember { mutableStateOf<String?>(null) }
    var locating by remember { mutableStateOf(false) }
    var catalog by remember { mutableStateOf<JSONObject?>(null) }
    val key = rememberSaveable { UUID.randomUUID().toString() }
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val cache = remember { CatalogCache(context) }
    LaunchedEffect(Unit) { catalog = cache.load(); cargarCatalogo(api, cache).first?.let { catalog = it } }
    val zonaActual = if (proveedor.isNull("ruta_id")) null else proveedor.getInt("ruta_id")
    val pendiente = proveedor.optJSONObject("cambio_zona_pendiente")
    val zonas = catalog?.arrayRows("zonas").orEmpty().filter { it.optInt("id") != zonaActual }

    fun validar(): String? = when {
        runCatching { LocalDate.parse(date) }.isFailure -> "Revisa la fecha."
        liters.replace(',', '.').toDoubleOrNull().let { it == null || !it.isFinite() || it < .01 } -> "Ingresa una cantidad válida de litros."
        else -> null
    }
    fun guardar() {
        if (locating) return
        scope.launch {
            locating = true; error = null
            try {
                val fix = obtenerUbicacion(context)
                val payload = JSONObject().put("request_id", key).put("proveedor_id", proveedor.getInt("id"))
                    .put("fecha", date).put("cantidad_litros", liters.replace(',', '.').toDouble())
                    .put("latitud", fix.latitude).put("longitud", fix.longitude).put("precision_m", fix.accuracy.toDouble())
                    .put("capturado_en", Instant.ofEpochMilli(fix.time).toString())
                if (zonaActual != null) payload.put("ruta_id", zonaActual)
                onSave(payload)
            } catch (e: TimeoutCancellationException) { error = "No se obtuvo una ubicación precisa. Sal al aire libre y vuelve a guardar." }
            catch (e: CancellationException) { throw e }
            catch (e: Exception) { error = e.message ?: "No se pudo obtener la ubicación." }
            finally { locating = false }
        }
    }
    val permiso = rememberLauncherForActivityResult(ActivityResultContracts.RequestMultiplePermissions()) { grants ->
        if (grants[Manifest.permission.ACCESS_FINE_LOCATION] == true) guardar()
        else error = "Se necesita permiso de ubicación precisa para registrar el acopio. Puedes autorizarlo en Ajustes de Android."
    }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp), verticalArrangement = Arrangement.spacedBy(14.dp)) {
        Text("Nuevo litraje", fontSize = 25.sp, color = DarkGreen, fontWeight = FontWeight.Bold)
        Text("${proveedor.getString("nombre")} · ${if (proveedor.isNull("zona")) "sin zona asignada" else proveedor.getString("zona")}", color = Gray)
        DateChoice("Fecha del acopio", date, { date = it }, !busy && !locating, hastaHoy = true)
        Field("Litros medidos", liters, { liters = it }, busy || locating, androidx.compose.ui.text.input.KeyboardType.Decimal)
        if (pendiente != null) {
            Message("Este proveedor pidió cambiar de zona; la planta lo está revisando.", false)
        }
        error?.let { Text(it, color = MaterialTheme.colorScheme.error) }
        Button(enabled = !busy && !locating, modifier = Modifier.fillMaxWidth(), onClick = {
            error = validar()
            if (error == null) {
                if (ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED) guardar()
                else permiso.launch(arrayOf(Manifest.permission.ACCESS_FINE_LOCATION, Manifest.permission.ACCESS_COARSE_LOCATION))
            }
        }) { Text(when { locating -> "Tomando ubicación…"; busy -> "Enviando…"; else -> "Guardar y enviar a planta" }) }
        Text("La ubicación se guarda sola con el acopio; no se rastrea en segundo plano.", color = Gray, fontSize = 12.sp)
    }
}

/** Solicita el traslado de un proveedor a otra zona; la aprueba quien administra las rutas. */

/**
 * Toma la ubicación en el momento de guardar el acopio. Si el teléfono ya
 * tiene un punto preciso de hace menos de dos minutos lo usa sin esperar; si
 * no, escucha GPS y red hasta 30 segundos. No rastrea en segundo plano.
 */
private suspend fun obtenerUbicacion(context: Context): Location {
    val manager = context.getSystemService(Context.LOCATION_SERVICE) as LocationManager
    if (ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED)
        error("Autoriza la ubicación precisa para registrar el acopio.")
    val providers = listOf(LocationManager.GPS_PROVIDER, LocationManager.NETWORK_PROVIDER).filter { manager.isProviderEnabled(it) }
    if (providers.isEmpty()) error("Activa la ubicación del teléfono para registrar el acopio.")
    fun util(location: Location?) = location != null && location.hasAccuracy() && location.accuracy <= 100 &&
        SystemClock.elapsedRealtimeNanos() - location.elapsedRealtimeNanos < 120_000_000_000L
    providers.mapNotNull { manager.getLastKnownLocation(it) }.filter(::util).minByOrNull { it.accuracy }?.let { return it }

    val result = CompletableDeferred<Location>()
    val listener = object : LocationListener {
        override fun onLocationChanged(location: Location) { if (util(location)) result.complete(location) }
        override fun onProviderEnabled(provider: String) {}
        override fun onProviderDisabled(provider: String) {}
        @Deprecated("Legacy Android callback")
        override fun onStatusChanged(provider: String?, status: Int, extras: android.os.Bundle?) {}
    }
    try {
        providers.forEach { manager.requestLocationUpdates(it, 1000L, 0f, listener, Looper.getMainLooper()) }
        return withTimeout(30000L) { result.await() }
    } finally { manager.removeUpdates(listener) }
}

@Composable
internal fun ConsolidatedScreen(api: Api) {
    var date by rememberSaveable { mutableStateOf(LocalDate.now().toString()) }
    var report by remember { mutableStateOf<JSONObject?>(null) }
    var error by remember { mutableStateOf<String?>(null) }
    var revision by remember { mutableIntStateOf(0) }
    LaunchedEffect(date,revision) {
        report = null
        if(runCatching { LocalDate.parse(date) }.isFailure) { error = "Fecha inválida (AAAA-MM-DD)."; return@LaunchedEffect }
        while(isActive) {
            try { report=api.request("mobile/consolidado?fecha=$date").getJSONObject("data"); error=null }
            catch(e: CancellationException) { throw e }
            catch(e: Exception) { error=if(e is ApiError) e.message else "No se pudo actualizar el reporte. Reintenta." }
            delay(20000)
        }
    }
    LazyColumn(Modifier.fillMaxSize(), contentPadding = PaddingValues(20.dp), verticalArrangement = Arrangement.spacedBy(14.dp)) {
        item { Text("Consolidado por zonas", fontSize=24.sp, fontWeight=FontWeight.Bold, color=DarkGreen) }
        item { DateChoice("Fecha del reporte",date,{date=it},true); TextButton(onClick={revision++}) { Text("Actualizar ahora") } }
        error?.let { item { Text(it,color=MaterialTheme.colorScheme.error) } }
        report?.let { r ->
            item { Text("${r.optDouble("total_litros")} L",fontSize=32.sp,color=Green,fontWeight=FontWeight.Bold)
                Text("${r.optInt("total_registros")} recepciones · actualización automática cada 20 s",color=Gray,fontSize=12.sp)
                Text("Última lectura: ${r.optString("actualizado_en")}",color=Gray,fontSize=11.sp) }
            items(r.arrayRows("zonas")) { zone ->
                Card(Modifier.fillMaxWidth()) { Column(Modifier.padding(18.dp)) {
                    Text(zone.getString("zona"),fontWeight=FontWeight.SemiBold)
                    Text("${zone.optDouble("litros")} L · ${zone.optInt("registros")} registros",color=Green)
                } }
            }
        }
    }
}

@Composable
internal fun PlantaForm(api: Api, sale: Boolean, busy: Boolean, onSave: (JSONObject) -> Unit) {
    var products by remember { mutableStateOf(emptyList<JSONObject>()) }
    var selected by rememberSaveable { mutableStateOf<Int?>(null) }
    var date by rememberSaveable { mutableStateOf(LocalDate.now().toString()) }
    var quantity by rememberSaveable { mutableStateOf("") }
    var input by rememberSaveable { mutableStateOf("") }
    var extra by rememberSaveable { mutableStateOf("") }
    var error by remember { mutableStateOf<String?>(null) }
    var retry by remember { mutableIntStateOf(0) }
    val key=rememberSaveable { UUID.randomUUID().toString() }
    // Solo producto terminado: los insumos se mueven desde el almacén, no se transforman ni se venden.
    LaunchedEffect(retry) { try { products=api.list("mobile/productos").filter { it.optString("tipo", "producto_terminado") == "producto_terminado" }; error=null } catch(e: CancellationException) { throw e }
        catch(e: Exception) { error="No se pudo cargar el catálogo de productos." } }
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp),verticalArrangement=Arrangement.spacedBy(14.dp)) {
        Text(if(sale) "Registrar venta" else "Registrar transformación",fontSize=24.sp,color=DarkGreen,fontWeight=FontWeight.Bold)
        Choice("Producto",products,selected,{selected=it},!busy)
        products.firstOrNull { it.optInt("id")==selected }?.let { Text("Unidad: ${it.optString("unidad")} · Stock: ${it.optDouble("stock")}",color=Gray) }
        DateChoice("Fecha",date,{date=it},!busy,hastaHoy=true)
        Field("Cantidad de producto",quantity,{quantity=it},busy,androidx.compose.ui.text.input.KeyboardType.Decimal)
        Field(if(sale) "Precio por unidad de medida" else "Litros de leche procesados",input,{input=it},busy,androidx.compose.ui.text.input.KeyboardType.Decimal)
        Field(if(sale) "Cliente (opcional)" else "Observaciones",extra,{extra=it},busy)
        error?.let { Text(it,color=MaterialTheme.colorScheme.error); TextButton(onClick={retry++}){Text("Recargar productos")} }
        Button(enabled=!busy && products.isNotEmpty(),onClick={
            val n=quantity.replace(',','.').toDoubleOrNull(); val v=input.replace(',','.').toDoubleOrNull()
            error=when {
                selected==null -> "Selecciona un producto."
                runCatching { LocalDate.parse(date) }.isFailure -> "Revisa la fecha."
                n==null || !n.isFinite() || n<=0 || v==null || !v.isFinite() || v<=0 -> "Ingresa cantidades y valores positivos."
                else -> null
            }
            if(error==null) onSave(JSONObject().put("request_id",key).put("producto_id",selected).put("fecha",date)
                .put("cantidad",n).put(if(sale) "precio_unitario" else "litros_procesados",v)
                .put(if(sale) "cliente" else "observaciones",extra))
        },modifier=Modifier.fillMaxWidth()){Text(if(busy) "Guardando…" else if(sale) "Confirmar venta" else "Registrar e ingresar a stock")}
    }
}


/**
 * Campana de verificación en dos pasos.
 *
 * El código solo llega si el dueño vinculó antes este teléfono: aquí se
 * genera un código de vinculación y él lo escribe en la web, donde ya inició
 * sesión. Así no basta con saber la contraseña de la app para conseguir el
 * segundo factor.
 */
@Composable
internal fun DosFactoresScreen(api: Api) {
    var estado by remember { mutableStateOf<JSONObject?>(null) }
    var codigo by remember { mutableStateOf<String?>(null) }
    var restantes by remember { mutableIntStateOf(0) }
    var codigoVinculacion by remember { mutableStateOf<String?>(null) }
    var venceEn by remember { mutableIntStateOf(0) }
    var error by remember { mutableStateOf<String?>(null) }
    var aviso by remember { mutableStateOf<String?>(null) }
    var trabajando by remember { mutableStateOf(false) }
    var revision by remember { mutableIntStateOf(0) }
    val scope = rememberCoroutineScope()

    val vinculado = estado?.optBoolean("vinculado") == true
    val activo = estado?.optBoolean("dos_factores_activo") == true
    val iniciado = estado?.optBoolean("dos_factores_iniciado", activo) == true

    LaunchedEffect(revision) {
        estado = runCatching { api.request("mobile/2fa/estado").getJSONObject("data") }.getOrNull()
    }

    // Mientras se muestra el código de vinculación, se cuenta su vigencia y se
    // pregunta cada pocos segundos si ya se escribió en la web.
    LaunchedEffect(codigoVinculacion) {
        if (codigoVinculacion == null) return@LaunchedEffect
        var desdeUltimaConsulta = 0
        while (isActive && venceEn > 0) {
            delay(1000)
            venceEn--
            if (++desdeUltimaConsulta >= 3) {
                desdeUltimaConsulta = 0
                val actual = runCatching { api.request("mobile/2fa/estado").getJSONObject("data") }.getOrNull()
                if (actual?.optBoolean("vinculado") == true) {
                    estado = actual
                    codigoVinculacion = null
                    aviso = "Teléfono vinculado. Aquí verás tu código."
                    return@LaunchedEffect
                }
            }
        }
        if (codigoVinculacion != null) {
            codigoVinculacion = null
            aviso = "El código de vinculación venció. Genera otro."
        }
    }

    fun generarCodigoVinculacion() {
        trabajando = true; error = null; aviso = null
        scope.launch {
            try {
                val data = api.request("mobile/2fa/codigo-vinculacion", "POST").getJSONObject("data")
                venceEn = data.optInt("vence_en_segundos", 600)
                codigoVinculacion = data.getString("codigo")
            } catch (e: CancellationException) {
                throw e
            } catch (e: Exception) {
                error = if (e is ApiError) e.message else "No se pudo generar el código."
            } finally {
                trabajando = false
            }
        }
    }

    // Mientras el teléfono esté vinculado se pide un código nuevo cada vez
    // que el anterior vence, para no mostrar nunca uno caduco.
    LaunchedEffect(vinculado, revision) {
        if (!vinculado) return@LaunchedEffect
        while (isActive) {
            try {
                val data = api.request("mobile/2fa/codigo").getJSONObject("data")
                codigo = data.getString("codigo")
                restantes = data.optInt("segundos_restantes", 30)
                error = null
            } catch (e: CancellationException) {
                throw e
            } catch (e: Exception) {
                error = if (e is ApiError) e.message else "No se pudo obtener el código."
                restantes = 10
            }
            while (restantes > 0 && isActive) { delay(1000); restantes-- }
        }
    }

    Column(
        Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp),
    ) {
        Text("Verificación en dos pasos", fontSize = 24.sp, fontWeight = FontWeight.Bold, color = DarkGreen)
        error?.let { Text(it, color = MaterialTheme.colorScheme.error, fontSize = 13.sp) }
        aviso?.let { Text(it, color = Green, fontSize = 13.sp) }

        if (estado == null) {
            Text("Cargando…", color = Gray)
        } else if (!iniciado) {
            Text(
                "Esta cuenta todavía no tiene la verificación en dos pasos. Entra a la web, abre " +
                    "Seguridad (2FA) y pulsa «Habilitar 2FA». Luego vuelve aquí para vincular este teléfono.",
                color = Gray, fontSize = 13.sp,
            )
            OutlinedButton(onClick = { revision++ }, modifier = Modifier.fillMaxWidth()) { Text("Ya lo habilité") }
        } else {
            // Las dos cosas a la vista, siempre: el código con el que se entra
            // y la vinculación de este teléfono. Teniéndolas juntas se ve de un
            // golpe cuál es cuál y por qué una depende de la otra.

            // ── 1. El código para entrar a la web ──
            Card(
                Modifier.fillMaxWidth(), shape = RoundedCornerShape(18.dp),
                border = BorderStroke(1.dp, if (vinculado) Green else Line),
                colors = CardDefaults.cardColors(containerColor = Color.White),
            ) {
                Column(Modifier.padding(18.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    Text("1 · CÓDIGO PARA ENTRAR A LA WEB", color = Gray, fontSize = 10.sp, letterSpacing = 1.2.sp)

                    if (vinculado) {
                        Text(codigo ?: "······", fontSize = 44.sp, fontWeight = FontWeight.Bold, color = Green)
                        Text("Cambia en ${restantes.coerceAtLeast(0)} s", color = Gray, fontSize = 12.sp)
                        Text(
                            if (activo) "Escríbelo en la web para terminar de entrar."
                            else "Escríbelo en la web, en «Código de 6 dígitos», para terminar de activar la verificación.",
                            color = Gray, fontSize = 12.sp,
                        )
                    } else {
                        Text("······", fontSize = 44.sp, fontWeight = FontWeight.Bold, color = Line)
                        Text(
                            "Aquí aparecerá cuando vincules este teléfono, abajo. " +
                                "Mientras tanto, para entrar usa un código de recuperación.",
                            color = Gray, fontSize = 12.sp,
                        )
                    }
                }
            }

            // ── 2. La vinculación de este teléfono ──
            Card(
                Modifier.fillMaxWidth(), shape = RoundedCornerShape(18.dp),
                border = BorderStroke(1.dp, if (vinculado) Line else Gold),
                colors = CardDefaults.cardColors(containerColor = Color.White),
            ) {
                Column(Modifier.padding(18.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text("2 · VINCULAR ESTE TELÉFONO", color = Gray, fontSize = 10.sp, letterSpacing = 1.2.sp)

                    if (vinculado) {
                        Text("Ya está vinculado.", color = Green, fontSize = 15.sp, fontWeight = FontWeight.SemiBold)
                        estado?.optString("vinculado_en")?.takeIf { it.isNotBlank() && it != "null" }?.let { cuando ->
                            Text("Desde el $cuando", color = Gray, fontSize = 12.sp)
                        }
                        OutlinedButton(
                            enabled = !trabajando,
                            modifier = Modifier.fillMaxWidth(),
                            onClick = {
                                trabajando = true
                                error = null
                                aviso = null
                                scope.launch {
                                    try {
                                        api.request("mobile/2fa/vincular", "DELETE")
                                        codigo = null
                                        aviso = "Este teléfono ya no recibirá códigos."
                                        revision++
                                    } catch (e: CancellationException) {
                                        throw e
                                    } catch (e: Exception) {
                                        error = if (e is ApiError) e.message else "No se pudo desvincular."
                                    } finally {
                                        trabajando = false
                                    }
                                }
                            },
                        ) { Text("Desvincular este teléfono") }
                    } else if (codigoVinculacion != null) {
                        Text(
                            "Escribe este código en la web · NO sirve para entrar",
                            color = Gold, fontSize = 12.sp, fontWeight = FontWeight.Medium,
                        )
                        Text(
                            codigoVinculacion!!, fontSize = 40.sp, fontWeight = FontWeight.Bold,
                            color = Green, letterSpacing = 4.sp,
                        )
                        Text(
                            "En la web: Seguridad (2FA) → «Código que muestra tu celular» → Vincular teléfono.",
                            color = Gray, fontSize = 13.sp,
                        )
                        Text(
                            "Vence en ${venceEn / 60}:${(venceEn % 60).toString().padStart(2, '0')}. " +
                                "Al vincular, arriba aparecerá tu código de 6 dígitos.",
                            color = Gray, fontSize = 12.sp,
                        )
                        OutlinedButton(
                            enabled = !trabajando,
                            modifier = Modifier.fillMaxWidth(),
                            onClick = { generarCodigoVinculacion() },
                        ) { Text("Generar otro código") }
                    } else {
                        Text(
                            "Este teléfono todavía no está vinculado, por eso arriba no hay código.",
                            color = Gray, fontSize = 13.sp,
                        )
                        Button(
                            enabled = !trabajando,
                            modifier = Modifier.fillMaxWidth(),
                            onClick = { generarCodigoVinculacion() },
                        ) { Text(if (trabajando) "Generando…" else "Generar código de vinculación") }
                    }
                }
            }
        }
    }
}
/**
 * La ruta del acopiador, igual que la ve la planta en la web: el mapa de
 * referencia del recorrido y los proveedores que atiende, con su finca y su
 * telefono para ubicarlos en el campo.
 */
@Composable
internal fun MiRutaScreen(api: Api) {
    var datos by remember { mutableStateOf<JSONObject?>(null) }
    var error by remember { mutableStateOf<String?>(null) }
    var reintento by remember { mutableIntStateOf(0) }
    val context = LocalContext.current

    LaunchedEffect(reintento) {
        datos = null
        try {
            datos = api.request("mobile/mi-ruta").getJSONObject("data")
            error = null
        } catch (e: CancellationException) {
            throw e
        } catch (e: Exception) {
            error = if (e is ApiError) e.message else "No se pudo cargar tu ruta. Reintenta con señal."
        }
    }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp), verticalArrangement = Arrangement.spacedBy(14.dp)) {
        Text("Mi ruta", fontSize = 25.sp, color = DarkGreen, fontWeight = FontWeight.Bold)
        error?.let { Message(it, true); TextButton(onClick = { reintento++ }) { Text("Reintentar") } }

        val ruta = datos
        if (ruta == null) {
            if (error == null) Text("Cargando…", color = Gray)
        } else {
            Text("${ruta.optString("movilidad")} · ${if (ruta.isNull("zona")) "sin zona asignada" else ruta.getString("zona")}", color = Gray)

            val paradas = ruta.optJSONArray("paradas")?.let { p -> (0 until p.length()).map { p.getString(it) } }.orEmpty()
            if (paradas.isNotEmpty()) {
                Text("Planta → ${paradas.joinToString(" → ")}", color = DarkGreen, fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
            }
            Text("Recorrido referencial: aproximado hasta tener la ruta oficial.", color = Gray, fontSize = 12.sp)

            // El mapa es el mismo recorrido de referencia que muestra la web:
            // lo sirve el servidor en su propia pagina, con el embed de Google.
            Card(border = BorderStroke(1.dp, Line), shape = RoundedCornerShape(18.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
                AndroidView(
                    factory = { ctx ->
                        WebView(ctx).apply {
                            settings.javaScriptEnabled = true
                            settings.domStorageEnabled = true
                            webViewClient = WebViewClient()
                            // La pagina del mapa la sirve el servidor: Google solo
                            // entrega su embed dentro de un iframe de un sitio.
                            loadUrl(ruta.getString("mapa_pagina"))
                        }
                    },
                    modifier = Modifier.fillMaxWidth().height(260.dp),
                )
            }
            OutlinedButton(
                modifier = Modifier.fillMaxWidth(),
                onClick = {
                    runCatching {
                        context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(ruta.getString("mapa_enlace"))))
                    }
                },
            ) { Text("Abrir en Google Maps") }

            val proveedores = ruta.optJSONArray("proveedores")?.let { p -> (0 until p.length()).map { p.getJSONObject(it) } }.orEmpty()
            Text("Proveedores de tu ruta (${proveedores.size})", color = DarkGreen, fontWeight = FontWeight.SemiBold, fontSize = 16.sp)

            if (proveedores.isEmpty()) {
                Message("Tu ruta todavía no tiene proveedores activos.", false)
            } else proveedores.forEach { proveedor ->
                Card(border = BorderStroke(1.dp, Line), shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
                    Row(Modifier.fillMaxWidth().padding(14.dp), verticalAlignment = Alignment.CenterVertically) {
                        Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(2.dp)) {
                            Text(proveedor.getString("nombre"), color = DarkGreen, fontWeight = FontWeight.SemiBold)
                            Text(
                                listOfNotNull(
                                    if (proveedor.isNull("finca")) null else proveedor.getString("finca"),
                                    if (proveedor.isNull("telefono")) null else proveedor.getString("telefono"),
                                ).joinToString(" · ").ifBlank { "Sin finca ni teléfono registrados" },
                                color = Gray, fontSize = 12.sp,
                            )
                        }
                        Text("${proveedor.optDouble("litros_prom", 0.0).toInt()} L prom.", color = Gray, fontSize = 12.sp)
                    }
                }
            }
        }
    }
}

/* ─────────────────────────── Lo del proveedor ───────────────────────────
 * El proveedor entra con su cédula y desde aquí ve su tienda —con el precio
 * que le toca—, la leche que lleva entregada en la semana y sus pedidos con
 * el código que enseña al recibirlos.
 */

/** Una línea del carrito: el producto tal como lo devuelve la tienda y cuánto se lleva. */
private data class LineaCarrito(val producto: JSONObject, val cantidad: Double)

internal fun soles(valor: Double): String = "S/ " + String.format("%.2f", valor)

/** Cantidad sin ceros de relleno: 2,00 se lee "2" y 1,50 se lee "1.5". */
internal fun cantidadCorta(valor: Double): String =
    String.format("%.2f", valor).trimEnd('0').trimEnd('.')

@Composable
internal fun TiendaProveedorScreen(api: Api, alComprar: (String) -> Unit) {
    var productos by remember { mutableStateOf<List<JSONObject>>(emptyList()) }
    var carrito by remember { mutableStateOf<Map<Int, LineaCarrito>>(emptyMap()) }
    var cargando by remember { mutableStateOf(true) }
    var error by remember { mutableStateOf<String?>(null) }
    var enCheckout by remember { mutableStateOf(false) }
    var reintento by remember { mutableIntStateOf(0) }

    LaunchedEffect(reintento) {
        cargando = true
        try {
            val datos = api.request("mobile/proveedor/tienda").getJSONArray("data")
            productos = (0 until datos.length()).map { datos.getJSONObject(it) }
            error = null
        } catch (e: CancellationException) {
            throw e
        } catch (e: Exception) {
            error = if (e is ApiError) e.message else "No se pudo cargar la tienda."
        } finally {
            cargando = false
        }
    }

    val total = carrito.values.sumOf { it.producto.optDouble("precio") * it.cantidad }

    if (enCheckout) {
        CheckoutProveedorScreen(
            api = api,
            lineas = carrito.values.toList(),
            total = total,
            volver = { enCheckout = false },
            listo = { codigo -> carrito = emptyMap(); enCheckout = false; alComprar(codigo) },
        )
        return
    }

    Column(Modifier.fillMaxSize()) {
        Box(Modifier.weight(1f)) {
            LazyColumn(
                Modifier.fillMaxSize(),
                contentPadding = PaddingValues(20.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                item {
                    Text("Tienda", fontSize = 24.sp, fontWeight = FontWeight.Bold, color = DarkGreen)
                    Text("Precios de proveedor, solo para ti.", color = Gray, fontSize = 13.sp)
                }
                error?.let { mensaje ->
                    item {
                        Text(mensaje, color = MaterialTheme.colorScheme.error, fontSize = 13.sp)
                        OutlinedButton(onClick = { reintento++ }, modifier = Modifier.fillMaxWidth()) { Text("Reintentar") }
                    }
                }
                if (cargando) item { Text("Cargando…", color = Gray) }
                if (!cargando && error == null && productos.isEmpty()) {
                    item { Text("Ahora mismo no hay productos disponibles.", color = Gray, fontSize = 13.sp) }
                }
                items(productos) { producto ->
                    val id = producto.optInt("id")
                    val enCarrito = carrito[id]?.cantidad ?: 0.0
                    // Lo que se vende por pieza no admite medias unidades.
                    val paso = if (producto.optBoolean("por_unidad_entera")) 1.0 else 0.5

                    Card(
                        Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(18.dp),
                        border = BorderStroke(1.dp, Line),
                        colors = CardDefaults.cardColors(containerColor = Color.White),
                    ) {
                        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                            Text(producto.optString("nombre"), fontSize = 16.sp, fontWeight = FontWeight.SemiBold, color = DarkGreen)
                            Row(verticalAlignment = Alignment.Bottom, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                Text(soles(producto.optDouble("precio")), fontSize = 20.sp, fontWeight = FontWeight.Bold, color = Green)
                                Text("por " + producto.optString("unidad"), color = Gray, fontSize = 12.sp)
                            }
                            Text(
                                "Quedan " + cantidadCorta(producto.optDouble("stock")) + " " + producto.optString("unidad"),
                                color = Gray, fontSize = 11.sp,
                            )

                            Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                                OutlinedButton(
                                    enabled = enCarrito > 0,
                                    onClick = {
                                        val nueva = enCarrito - paso
                                        carrito = if (nueva <= 0) carrito - id
                                        else carrito + (id to LineaCarrito(producto, nueva))
                                    },
                                ) { Text("−") }

                                Text(
                                    if (enCarrito == 0.0) "0" else cantidadCorta(enCarrito),
                                    fontSize = 16.sp, fontWeight = FontWeight.SemiBold, color = DarkGreen,
                                )

                                OutlinedButton(
                                    enabled = enCarrito + paso <= producto.optDouble("stock"),
                                    onClick = { carrito = carrito + (id to LineaCarrito(producto, enCarrito + paso)) },
                                ) { Text("+") }
                            }
                        }
                    }
                }
            }
        }

        if (carrito.isNotEmpty()) {
            Surface(shadowElevation = 12.dp, color = Color.White) {
                Column(Modifier.fillMaxWidth().padding(20.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                        Text(
                            carrito.size.toString() + if (carrito.size == 1) " producto" else " productos",
                            color = Gray, fontSize = 13.sp,
                        )
                        Text(soles(total), fontSize = 20.sp, fontWeight = FontWeight.Bold, color = DarkGreen)
                    }
                    Button(onClick = { enCheckout = true }, modifier = Modifier.fillMaxWidth()) {
                        Text("Continuar con el pedido")
                    }
                }
            }
        }
    }
}

@Composable
private fun CheckoutProveedorScreen(
    api: Api,
    lineas: List<LineaCarrito>,
    total: Double,
    volver: () -> Unit,
    listo: (String) -> Unit,
) {
    var tipoEntrega by rememberSaveable { mutableStateOf("delivery") }
    var direccion by rememberSaveable { mutableStateOf("") }
    var observaciones by rememberSaveable { mutableStateOf("") }
    var enviando by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()

    Column(
        Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp),
    ) {
        Text("Tu pedido", fontSize = 24.sp, fontWeight = FontWeight.Bold, color = DarkGreen)

        Card(
            Modifier.fillMaxWidth(), shape = RoundedCornerShape(18.dp), border = BorderStroke(1.dp, Line),
            colors = CardDefaults.cardColors(containerColor = Color.White),
        ) {
            Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                lineas.forEach { linea ->
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                        Text(
                            cantidadCorta(linea.cantidad) + " × " + linea.producto.optString("nombre"),
                            color = DarkGreen, fontSize = 14.sp, modifier = Modifier.weight(1f),
                        )
                        Text(soles(linea.producto.optDouble("precio") * linea.cantidad), color = DarkGreen, fontSize = 14.sp)
                    }
                }
                HorizontalDivider(color = Line)
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    Text("Total", fontWeight = FontWeight.SemiBold, color = DarkGreen)
                    Text(soles(total), fontWeight = FontWeight.Bold, color = DarkGreen, fontSize = 18.sp)
                }
            }
        }

        Text("¿Cómo lo recibes?", fontWeight = FontWeight.SemiBold, color = DarkGreen)
        listOf("delivery" to "Que me lo lleven", "recojo" to "Lo recojo en planta").forEach { opcion ->
            Row(
                Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(8.dp),
            ) {
                RadioButton(selected = tipoEntrega == opcion.first, onClick = { tipoEntrega = opcion.first })
                Text(opcion.second, color = DarkGreen, fontSize = 14.sp)
            }
        }

        if (tipoEntrega == "delivery") {
            OutlinedTextField(
                value = direccion,
                onValueChange = { direccion = it },
                label = { Text("¿Dónde te lo dejamos?") },
                supportingText = { Text("Comunidad, referencia…") },
                modifier = Modifier.fillMaxWidth(),
            )
        }

        OutlinedTextField(
            value = observaciones,
            onValueChange = { observaciones = it },
            label = { Text("Algo que debamos saber (opcional)") },
            modifier = Modifier.fillMaxWidth(),
        )

        Text(
            "Pagas al recibirlo, en efectivo o por Yape. El código te llega a tu campana.",
            color = Gray, fontSize = 12.sp,
        )

        error?.let { Text(it, color = MaterialTheme.colorScheme.error, fontSize = 13.sp) }

        Button(
            enabled = !enviando && lineas.isNotEmpty(),
            modifier = Modifier.fillMaxWidth(),
            onClick = {
                enviando = true
                error = null
                scope.launch {
                    try {
                        val items = org.json.JSONArray()
                        lineas.forEach { linea ->
                            items.put(
                                JSONObject()
                                    .put("producto_id", linea.producto.optInt("id"))
                                    .put("cantidad", linea.cantidad),
                            )
                        }
                        val cuerpo = JSONObject()
                            .put("tipo_entrega", tipoEntrega)
                            .put("direccion", direccion.trim())
                            .put("observaciones", observaciones.trim())
                            .put("items", items)
                        val data = api.request("mobile/proveedor/pedidos", "POST", cuerpo).getJSONObject("data")
                        listo(data.optString("codigo"))
                    } catch (e: CancellationException) {
                        throw e
                    } catch (e: Exception) {
                        error = if (e is ApiError) e.message else "No se pudo registrar el pedido."
                    } finally {
                        enviando = false
                    }
                }
            },
        ) { Text(if (enviando) "Enviando…" else "Confirmar pedido") }

        OutlinedButton(onClick = volver, modifier = Modifier.fillMaxWidth(), enabled = !enviando) {
            Text("Seguir comprando")
        }
    }
}

@Composable
internal fun MiCuentaProveedorScreen(api: Api) {
    var datos by remember { mutableStateOf<JSONObject?>(null) }
    var error by remember { mutableStateOf<String?>(null) }
    var reintento by remember { mutableIntStateOf(0) }

    LaunchedEffect(reintento) {
        try {
            datos = api.request("mobile/proveedor/mi-cuenta").getJSONObject("data")
            error = null
        } catch (e: CancellationException) {
            throw e
        } catch (e: Exception) {
            error = if (e is ApiError) e.message else "No se pudo cargar tu cuenta."
        }
    }

    Column(
        Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp),
    ) {
        Text("Mi leche", fontSize = 24.sp, fontWeight = FontWeight.Bold, color = DarkGreen)

        error?.let {
            Text(it, color = MaterialTheme.colorScheme.error, fontSize = 13.sp)
            OutlinedButton(onClick = { reintento++ }, modifier = Modifier.fillMaxWidth()) { Text("Reintentar") }
        }

        val cuenta = datos
        if (cuenta == null && error == null) Text("Cargando…", color = Gray)

        cuenta?.let {
            val proveedor = it.getJSONObject("proveedor")
            val semana = it.getJSONObject("semana")
            val ruta = proveedor.optString("ruta")

            Text(proveedor.optString("nombre"), fontSize = 16.sp, fontWeight = FontWeight.SemiBold, color = DarkGreen)
            if (ruta.isNotBlank() && ruta != "null") {
                Text("Ruta " + ruta, color = Gray, fontSize = 13.sp)
            }

            Card(
                Modifier.fillMaxWidth(), shape = RoundedCornerShape(18.dp), border = BorderStroke(1.dp, Line),
                colors = CardDefaults.cardColors(containerColor = Color.White),
            ) {
                Column(Modifier.padding(18.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    Text(
                        "Semana del " + semana.optString("inicio") + " al " + semana.optString("fin"),
                        color = Gray, fontSize = 12.sp,
                    )
                    Text(
                        cantidadCorta(semana.optDouble("litros")) + " L",
                        fontSize = 34.sp, fontWeight = FontWeight.Bold, color = Green,
                    )
                    Text(
                        semana.optInt("entregas").toString() + " entregas · " +
                            soles(proveedor.optDouble("precio_litro")) + " por litro",
                        color = Gray, fontSize = 12.sp,
                    )
                    HorizontalDivider(color = Line, modifier = Modifier.padding(vertical = 6.dp))
                    Text(soles(semana.optDouble("monto_estimado")), fontSize = 22.sp, fontWeight = FontWeight.Bold, color = DarkGreen)
                    Text(
                        if (semana.optBoolean("cerrada")) "Semana cerrada."
                        else "Estimado: la semana sigue abierta y puede cambiar al liquidar.",
                        color = Gray, fontSize = 11.sp,
                    )
                }
            }

            val entregas = it.optJSONArray("entregas")
            Text("Tus entregas", fontWeight = FontWeight.SemiBold, color = DarkGreen)
            if (entregas == null || entregas.length() == 0) {
                Text("Todavía no entregaste leche esta semana.", color = Gray, fontSize = 13.sp)
            } else {
                (0 until entregas.length()).map { i -> entregas.getJSONObject(i) }.forEach { entrega ->
                    Row(
                        Modifier.fillMaxWidth().padding(vertical = 6.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                    ) {
                        Column {
                            Text(entrega.optString("fecha"), color = DarkGreen, fontSize = 14.sp)
                            Text(entrega.optString("estado"), color = Gray, fontSize = 11.sp)
                        }
                        Text(
                            cantidadCorta(entrega.optDouble("litros")) + " L",
                            color = DarkGreen, fontWeight = FontWeight.SemiBold,
                        )
                    }
                    HorizontalDivider(color = Line)
                }
            }
        }
    }
}

@Composable
internal fun MisPedidosProveedorScreen(api: Api, refrescar: Int) {
    var pedidos by remember { mutableStateOf<List<JSONObject>>(emptyList()) }
    var error by remember { mutableStateOf<String?>(null) }
    var cargando by remember { mutableStateOf(true) }

    LaunchedEffect(refrescar) {
        cargando = true
        try {
            val datos = api.request("mobile/proveedor/pedidos").getJSONArray("data")
            pedidos = (0 until datos.length()).map { datos.getJSONObject(it) }
            error = null
        } catch (e: CancellationException) {
            throw e
        } catch (e: Exception) {
            error = if (e is ApiError) e.message else "No se pudieron cargar tus pedidos."
        } finally {
            cargando = false
        }
    }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(20.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Text("Mis pedidos", fontSize = 24.sp, fontWeight = FontWeight.Bold, color = DarkGreen)
            Text("Enseña el código al recibirlos.", color = Gray, fontSize = 13.sp)
        }
        error?.let { mensaje -> item { Text(mensaje, color = MaterialTheme.colorScheme.error, fontSize = 13.sp) } }
        if (cargando) item { Text("Cargando…", color = Gray) }
        if (!cargando && error == null && pedidos.isEmpty()) {
            item { Text("Todavía no hiciste ningún pedido.", color = Gray, fontSize = 13.sp) }
        }
        items(pedidos) { pedido ->
            Card(
                Modifier.fillMaxWidth(), shape = RoundedCornerShape(18.dp), border = BorderStroke(1.dp, Line),
                colors = CardDefaults.cardColors(containerColor = Color.White),
            ) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                        Text(
                            pedido.optString("codigo"), fontSize = 22.sp, fontWeight = FontWeight.Bold,
                            color = Green, letterSpacing = 3.sp,
                        )
                        Text(soles(pedido.optDouble("total")), fontSize = 16.sp, fontWeight = FontWeight.SemiBold, color = DarkGreen)
                    }
                    Text(
                        pedido.optString("fecha") + " · " + when (pedido.optString("estado")) {
                            "pendiente" -> "Esperando confirmación"
                            "confirmado" -> "Confirmado, en camino"
                            "entregado" -> "Entregado"
                            "cancelado" -> "Cancelado"
                            else -> pedido.optString("estado")
                        },
                        color = Gray, fontSize = 12.sp,
                    )
                    Text(
                        if (pedido.optString("tipo_entrega") == "recojo") "Lo recoges en planta" else "Te lo llevan",
                        color = Gray, fontSize = 11.sp,
                    )
                    val items = pedido.optJSONArray("items")
                    if (items != null) {
                        (0 until items.length()).map { i -> items.getJSONObject(i) }.forEach { item ->
                            Text(
                                cantidadCorta(item.optDouble("cantidad")) + " × " + item.optString("producto"),
                                color = Gray, fontSize = 12.sp,
                            )
                        }
                    }
                }
            }
        }
    }
}

/** La campana: donde le llega el código de cada pedido. */
@Composable
internal fun NotificacionesScreen(api: Api, refrescar: Int) {
    var notificaciones by remember { mutableStateOf<List<JSONObject>>(emptyList()) }
    var error by remember { mutableStateOf<String?>(null) }
    var cargando by remember { mutableStateOf(true) }

    LaunchedEffect(refrescar) {
        cargando = true
        try {
            val datos = api.request("mobile/notificaciones").getJSONObject("data").getJSONArray("notificaciones")
            notificaciones = (0 until datos.length()).map { datos.getJSONObject(it) }
            error = null
        } catch (e: CancellationException) {
            throw e
        } catch (e: Exception) {
            error = if (e is ApiError) e.message else "No se pudieron cargar tus avisos."
        } finally {
            cargando = false
        }
    }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(20.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item { Text("Avisos", fontSize = 24.sp, fontWeight = FontWeight.Bold, color = DarkGreen) }
        error?.let { mensaje -> item { Text(mensaje, color = MaterialTheme.colorScheme.error, fontSize = 13.sp) } }
        if (cargando) item { Text("Cargando…", color = Gray) }
        if (!cargando && error == null && notificaciones.isEmpty()) {
            item { Text("No tienes avisos por ahora.", color = Gray, fontSize = 13.sp) }
        }
        items(notificaciones) { aviso ->
            val codigo = aviso.optJSONObject("datos")?.optString("codigo").orEmpty()
            Card(
                Modifier.fillMaxWidth(), shape = RoundedCornerShape(18.dp), border = BorderStroke(1.dp, Line),
                colors = CardDefaults.cardColors(containerColor = Color.White),
            ) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    if (codigo.isNotBlank()) {
                        Text("TU CÓDIGO", color = Gray, fontSize = 10.sp, letterSpacing = 1.4.sp)
                        Text(codigo, fontSize = 34.sp, fontWeight = FontWeight.Bold, color = Green, letterSpacing = 5.sp)
                    } else {
                        Text(aviso.optString("titulo"), fontSize = 15.sp, fontWeight = FontWeight.SemiBold, color = DarkGreen)
                    }
                    Text(aviso.optString("mensaje"), color = Gray, fontSize = 13.sp)
                    Text(aviso.optString("creada_en"), color = Gray, fontSize = 11.sp)
                }
            }
        }
    }
}

/**
 * El proveedor pide mudarse de zona desde su propia app.
 *
 * Antes lo pedía el acopiador por él; ahora lo pide quien se muda, y la
 * planta lo aprueba. Mientras haya una solicitud esperando no se admite
 * otra, para que no se acumulen pedidos del mismo ganadero.
 */
@Composable
internal fun MiZonaProveedorScreen(api: Api, alEnviar: () -> Unit) {
    var datos by remember { mutableStateOf<JSONObject?>(null) }
    var zonaElegida by rememberSaveable { mutableStateOf<Int?>(null) }
    var fecha by rememberSaveable { mutableStateOf(LocalDate.now().toString()) }
    var motivo by rememberSaveable { mutableStateOf("") }
    var error by remember { mutableStateOf<String?>(null) }
    var enviando by remember { mutableStateOf(false) }
    var reintento by remember { mutableIntStateOf(0) }
    val scope = rememberCoroutineScope()

    LaunchedEffect(reintento) {
        try {
            datos = api.request("mobile/proveedor/zonas").getJSONObject("data")
            error = null
        } catch (e: CancellationException) {
            throw e
        } catch (e: Exception) {
            error = if (e is ApiError) e.message else "No se pudieron cargar las zonas."
        }
    }

    Column(
        Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp),
    ) {
        Text("Mi zona", fontSize = 24.sp, fontWeight = FontWeight.Bold, color = DarkGreen)

        error?.let {
            Text(it, color = MaterialTheme.colorScheme.error, fontSize = 13.sp)
            OutlinedButton(onClick = { reintento++ }, modifier = Modifier.fillMaxWidth()) { Text("Reintentar") }
        }

        val info = datos
        if (info == null && error == null) Text("Cargando…", color = Gray)

        info?.let {
            val actual = it.optString("zona_actual")
            Text(
                "Zona actual: " + if (actual.isBlank() || actual == "null") "sin asignar" else actual,
                color = Gray, fontSize = 13.sp,
            )

            if (it.optBoolean("pendiente")) {
                Card(
                    Modifier.fillMaxWidth(), shape = RoundedCornerShape(18.dp), border = BorderStroke(1.dp, Line),
                    colors = CardDefaults.cardColors(containerColor = Color.White),
                ) {
                    Column(Modifier.padding(18.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                        Text("Ya pediste un cambio", fontWeight = FontWeight.SemiBold, color = DarkGreen)
                        Text(
                            "La planta todavía lo está revisando. Cuando lo aprueben, tu zona cambiará sola.",
                            color = Gray, fontSize = 13.sp,
                        )
                    }
                }
                return@let
            }

            val zonas = it.optJSONArray("zonas")
            if (zonas == null || zonas.length() == 0) {
                Text("No hay otras zonas a las que puedas pasar.", color = Gray, fontSize = 13.sp)
                return@let
            }

            Text("¿A qué zona te mudas?", fontWeight = FontWeight.SemiBold, color = DarkGreen)
            (0 until zonas.length()).map { i -> zonas.getJSONObject(i) }.forEach { zona ->
                Row(
                    Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    RadioButton(
                        selected = zonaElegida == zona.optInt("id"),
                        onClick = { zonaElegida = zona.optInt("id") },
                    )
                    Text(zona.optString("nombre"), color = DarkGreen, fontSize = 14.sp)
                }
            }

            OutlinedTextField(
                value = fecha,
                onValueChange = { nueva -> fecha = nueva },
                label = { Text("¿Desde cuándo?") },
                supportingText = { Text("Año-mes-día, por ejemplo 2026-10-01") },
                modifier = Modifier.fillMaxWidth(),
            )

            OutlinedTextField(
                value = motivo,
                onValueChange = { nuevo -> motivo = nuevo },
                label = { Text("Motivo (opcional)") },
                modifier = Modifier.fillMaxWidth(),
            )

            Text(
                "Tu solicitud va a la planta. El cambio se hace solo cuando la aprueben.",
                color = Gray, fontSize = 12.sp,
            )

            Button(
                enabled = !enviando && zonaElegida != null,
                modifier = Modifier.fillMaxWidth(),
                onClick = {
                    enviando = true
                    error = null
                    scope.launch {
                        try {
                            api.request(
                                "mobile/proveedor/cambio-zona", "POST",
                                JSONObject()
                                    .put("ruta_solicitada_id", zonaElegida)
                                    .put("fecha_cambio", fecha.trim())
                                    .put("motivo", motivo.trim()),
                            )
                            alEnviar()
                        } catch (e: CancellationException) {
                            throw e
                        } catch (e: Exception) {
                            error = if (e is ApiError) e.message else "No se pudo enviar la solicitud."
                        } finally {
                            enviando = false
                        }
                    }
                },
            ) { Text(if (enviando) "Enviando…" else "Pedir el cambio") }
        }
    }
}
