package com.ecolactea.huata

import android.Manifest
import android.content.Context
import android.content.pm.PackageManager
import android.location.Location
import android.location.LocationListener
import android.location.LocationManager
import android.os.Looper
import android.os.SystemClock
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
internal fun Choice(label: String, records: List<JSONObject>, selected: Int?, onSelect: (Int) -> Unit, enabled: Boolean) {
    var open by remember { mutableStateOf(false) }
    var search by remember { mutableStateOf("") }
    OutlinedCard(onClick = { open = true }, enabled = enabled, modifier = Modifier.fillMaxWidth()) {
        Column(Modifier.padding(16.dp)) {
            Text(label, color = Gray, fontSize = 12.sp)
            Text(records.firstOrNull { it.optInt("id") == selected }?.optString("nombre") ?: "Seleccionar…", color = DarkGreen)
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
internal fun DateChoice(label: String, value: String, onSelect: (String) -> Unit, enabled: Boolean) {
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
        val estado = rememberDatePickerState(
            initialSelectedDateMillis = fecha?.atStartOfDay(ZoneOffset.UTC)?.toInstant()?.toEpochMilli()
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
            if (catalog != null && proveedores.isEmpty()) item { Message("Ningún proveedor coincide con «$search».", false) }
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
internal fun ProveedorDetalle(proveedor: JSONObject, busy: Boolean, onNuevoLitraje: () -> Unit, onTraslado: () -> Unit) {
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
        OutlinedButton(onClick = onTraslado, enabled = !busy, modifier = Modifier.fillMaxWidth()) { Text("Solicitar traslado de zona") }
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
 * Registra un nuevo litraje para un proveedor ya elegido: a diferencia de la
 * versión anterior, ni el proveedor ni la zona se piden aquí — la zona es la
 * que el proveedor ya tiene asignada (para cambiarla existe la solicitud de
 * traslado, no este formulario).
 */
@Composable
internal fun CampoForm(proveedor: JSONObject, busy: Boolean, onSave: (JSONObject) -> Unit) {
    var liters by rememberSaveable { mutableStateOf("") }
    var date by rememberSaveable { mutableStateOf(LocalDate.now().toString()) }
    var notes by rememberSaveable { mutableStateOf("") }
    var loss by rememberSaveable { mutableStateOf("") }
    var reason by rememberSaveable { mutableStateOf("") }
    var fix by remember { mutableStateOf<Location?>(null) }
    var error by remember { mutableStateOf<String?>(null) }
    val key = rememberSaveable { UUID.randomUUID().toString() }
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp), verticalArrangement = Arrangement.spacedBy(14.dp)) {
        Text("Nuevo litraje", fontSize = 25.sp, color = DarkGreen, fontWeight = FontWeight.Bold)
        Text("${proveedor.getString("nombre")} · ${if (proveedor.isNull("zona")) "sin zona asignada" else proveedor.getString("zona")}", color = Gray)
        DateChoice("Fecha del acopio", date, { date = it }, !busy)
        Field("Litros medidos", liters, { liters = it }, busy, androidx.compose.ui.text.input.KeyboardType.Decimal)
        GpsCapture(busy) { fix = it }
        Field("Pérdida en litros (opcional)", loss, { loss = it }, busy, androidx.compose.ui.text.input.KeyboardType.Decimal)
        Field("Motivo de pérdida", reason, { reason = it }, busy)
        Field("Observaciones", notes, { notes = it }, busy)
        error?.let { Text(it, color = MaterialTheme.colorScheme.error) }
        Button(enabled = !busy, modifier = Modifier.fillMaxWidth(), onClick = {
            val n = liters.replace(',','.').toDoubleOrNull()
            val lost = if(loss.isBlank()) 0.0 else loss.replace(',','.').toDoubleOrNull()
            val location = fix
            error = when {
                runCatching { LocalDate.parse(date) }.isFailure -> "Revisa la fecha."
                n == null || !n.isFinite() || n < .01 -> "Ingresa una cantidad válida de litros."
                lost == null || !lost.isFinite() || lost < 0 || lost > n -> "Revisa la pérdida en litros."
                lost > 0 && reason.isBlank() -> "Indica el motivo de pérdida."
                location == null -> "Captura la ubicación del acopio."
                SystemClock.elapsedRealtimeNanos() - location.elapsedRealtimeNanos > 240_000_000_000L -> "La ubicación venció. Captúrala otra vez."
                else -> null
            }
            if (error == null && location != null) {
                val payload = JSONObject().put("request_id",key).put("proveedor_id",proveedor.getInt("id"))
                    .put("fecha",date).put("cantidad_litros",n).put("perdida_litros",lost)
                    .put("motivo_perdida",reason).put("observaciones",notes).put("latitud",location.latitude)
                    .put("longitud",location.longitude).put("precision_m",location.accuracy.toDouble())
                    .put("capturado_en",Instant.ofEpochMilli(location.time).toString())
                if (!proveedor.isNull("ruta_id")) payload.put("ruta_id", proveedor.getInt("ruta_id"))
                onSave(payload)
            }
        }) { Text(if(busy) "Enviando…" else "Guardar y enviar a planta") }
    }
}

/** Solicita el traslado de un proveedor a otra zona; la aprueba quien administra las rutas. */
@Composable
internal fun TrasladoZonaForm(api: Api, proveedor: JSONObject, busy: Boolean, onSave: (JSONObject) -> Unit) {
    var catalog by remember { mutableStateOf<JSONObject?>(null) }
    var error by remember { mutableStateOf<String?>(null) }
    var retry by remember { mutableIntStateOf(0) }
    var zone by rememberSaveable { mutableStateOf<Int?>(null) }
    var date by rememberSaveable { mutableStateOf(LocalDate.now().toString()) }
    var motivo by rememberSaveable { mutableStateOf("") }
    val context = LocalContext.current
    val cache = remember { CatalogCache(context) }
    LaunchedEffect(retry) { val (c, e) = cargarCatalogo(api, cache); catalog = c; error = e }
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp), verticalArrangement = Arrangement.spacedBy(14.dp)) {
        Text("Solicitar traslado de zona", fontSize = 24.sp, color = DarkGreen, fontWeight = FontWeight.Bold)
        Text("${proveedor.getString("nombre")} · Zona actual: ${if (proveedor.isNull("zona")) "sin asignar" else proveedor.getString("zona")}", color = Gray)
        Text("La solicitud queda pendiente hasta que un administrador la apruebe.", color = Gray, fontSize = 12.sp)
        if (catalog == null) TextButton(onClick = { retry++ }) { Text("Cargar zonas") }
        else Choice("Nueva zona", catalog!!.arrayRows("zonas"), zone, { zone = it }, !busy)
        DateChoice("Fecha del traslado", date, { date = it }, !busy)
        Field("Motivo (opcional)", motivo, { motivo = it }, busy)
        error?.let { Text(it, color = MaterialTheme.colorScheme.error) }
        Button(enabled = !busy && catalog != null, modifier = Modifier.fillMaxWidth(), onClick = {
            error = when {
                zone == null -> "Selecciona la nueva zona."
                runCatching { LocalDate.parse(date) }.isFailure -> "Revisa la fecha."
                else -> null
            }
            if (error == null) onSave(JSONObject().put("ruta_solicitada_id", zone).put("fecha_cambio", date).put("motivo", motivo.ifBlank { JSONObject.NULL }))
        }) { Text(if(busy) "Enviando…" else "Enviar solicitud") }
    }
}

@Composable
private fun GpsCapture(busy: Boolean, onFix: (Location) -> Unit) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    var message by remember { mutableStateOf("Se captura solo al pulsar el botón; no se rastrea en segundo plano.") }
    var locating by remember { mutableStateOf(false) }
    fun locate() {
        if(locating) return
        scope.launch {
            locating = true
            val manager = context.getSystemService(Context.LOCATION_SERVICE) as LocationManager
            val result = CompletableDeferred<Location>()
            val listener = object : LocationListener {
                override fun onLocationChanged(location: Location) {
                    if(location.hasAccuracy() && location.accuracy <= 100 && SystemClock.elapsedRealtimeNanos() - location.elapsedRealtimeNanos < 120_000_000_000L)
                        result.complete(location)
                }
                override fun onProviderEnabled(provider: String) {}
                override fun onProviderDisabled(provider: String) {}
                @Deprecated("Legacy Android callback")
                override fun onStatusChanged(provider: String?, status: Int, extras: android.os.Bundle?) {}
            }
            try {
                message = "Buscando ubicación precisa…"
                val providers = listOf(LocationManager.GPS_PROVIDER, LocationManager.NETWORK_PROVIDER).filter { manager.isProviderEnabled(it) }
                if(providers.isEmpty()) error("Activa la ubicación del dispositivo.")
                if(ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED)
                    error("Autoriza la ubicación precisa para registrar el acopio.")
                providers.forEach { manager.requestLocationUpdates(it, 1000L, 0f, listener, Looper.getMainLooper()) }
                val fix = withTimeout(30000L) { result.await() }
                onFix(fix); message = "Ubicación capturada · precisión ${fix.accuracy.toInt()} m\n${fix.latitude}, ${fix.longitude}"
            } catch(e: TimeoutCancellationException) { message = "No se obtuvo una ubicación precisa. Reintenta al aire libre." }
            catch(e: CancellationException) { throw e }
            catch(e: Exception) { message = e.message ?: "No se pudo obtener la ubicación." }
            finally { manager.removeUpdates(listener); locating = false }
        }
    }
    val launcher = rememberLauncherForActivityResult(ActivityResultContracts.RequestMultiplePermissions()) { grants ->
        if(grants[Manifest.permission.ACCESS_FINE_LOCATION] == true) locate() else message = "Se necesita permiso de ubicación precisa. Puedes autorizarlo en Ajustes de Android."
    }
    OutlinedCard(Modifier.fillMaxWidth()) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Text("Trazabilidad GPS", fontWeight = FontWeight.SemiBold, color = DarkGreen)
            Text(message, fontSize = 12.sp, color = Gray)
            OutlinedButton(enabled = !busy && !locating, onClick = {
                if(ContextCompat.checkSelfPermission(context,Manifest.permission.ACCESS_FINE_LOCATION)==PackageManager.PERMISSION_GRANTED) locate()
                else launcher.launch(arrayOf(Manifest.permission.ACCESS_FINE_LOCATION,Manifest.permission.ACCESS_COARSE_LOCATION))
            }) { Text(if(locating) "Capturando…" else "Capturar ubicación") }
        }
    }
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
    LaunchedEffect(retry) { try { products=api.list("mobile/productos"); error=null } catch(e: CancellationException) { throw e }
        catch(e: Exception) { error="No se pudo cargar el catálogo de productos." } }
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp),verticalArrangement=Arrangement.spacedBy(14.dp)) {
        Text(if(sale) "Registrar venta" else "Registrar transformación",fontSize=24.sp,color=DarkGreen,fontWeight=FontWeight.Bold)
        Choice("Producto",products,selected,{selected=it},!busy)
        products.firstOrNull { it.optInt("id")==selected }?.let { Text("Unidad: ${it.optString("unidad")} · Stock: ${it.optDouble("stock")}",color=Gray) }
        DateChoice("Fecha",date,{date=it},!busy)
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
 * El código solo llega si el dueño vinculó antes este teléfono desde la web:
 * de lo contrario bastaría con saber la contraseña para conseguir el segundo
 * factor. Mientras no esté vinculado, aquí solo se puede escribir el código
 * de vinculación que muestra el navegador.
 */
@Composable
internal fun DosFactoresScreen(api: Api) {
    var estado by remember { mutableStateOf<JSONObject?>(null) }
    var codigo by remember { mutableStateOf<String?>(null) }
    var restantes by remember { mutableIntStateOf(0) }
    var codigoVinculacion by rememberSaveable { mutableStateOf("") }
    var error by remember { mutableStateOf<String?>(null) }
    var aviso by remember { mutableStateOf<String?>(null) }
    var trabajando by remember { mutableStateOf(false) }
    var revision by remember { mutableIntStateOf(0) }
    val scope = rememberCoroutineScope()

    val vinculado = estado?.optBoolean("vinculado") == true
    val activo = estado?.optBoolean("dos_factores_activo") == true

    LaunchedEffect(revision) {
        estado = runCatching { api.request("mobile/2fa/estado").getJSONObject("data") }.getOrNull()
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
        } else if (!activo) {
            Text(
                "Esta cuenta no tiene activada la verificación en dos pasos. Actívala desde la web.",
                color = Gray, fontSize = 13.sp,
            )
        } else if (vinculado) {
            Text("Tu código ahora", color = Gray, fontSize = 13.sp)
            Text(codigo ?: "······", fontSize = 44.sp, fontWeight = FontWeight.Bold, color = Green)
            Text("Cambia en ${restantes.coerceAtLeast(0)} s", color = Gray, fontSize = 12.sp)
            Text("Escríbelo en la web para terminar de entrar.", color = Gray, fontSize = 12.sp)
            OutlinedButton(
                enabled = !trabajando,
                modifier = Modifier.fillMaxWidth(),
                onClick = {
                    trabajando = true; error = null; aviso = null
                    scope.launch {
                        try {
                            api.request("mobile/2fa/vincular", "DELETE")
                            codigo = null
                            aviso = "Este teléfono ya no recibirá códigos."
                            revision++
                        } catch (e: Exception) {
                            error = if (e is ApiError) e.message else "No se pudo desvincular."
                        } finally {
                            trabajando = false
                        }
                    }
                },
            ) { Text("Desvincular este teléfono") }
        } else {
            Text(
                "Este teléfono todavía no está vinculado. Entra a la web, abre Seguridad (2FA), " +
                    "pulsa «Vincular mi celular» y escribe aquí el código que aparece.",
                color = Gray, fontSize = 13.sp,
            )
            OutlinedTextField(
                value = codigoVinculacion,
                onValueChange = { codigoVinculacion = it.uppercase() },
                label = { Text("Código de vinculación") },
                singleLine = true,
                modifier = Modifier.fillMaxWidth(),
            )
            Button(
                enabled = !trabajando && codigoVinculacion.isNotBlank(),
                modifier = Modifier.fillMaxWidth(),
                onClick = {
                    trabajando = true; error = null; aviso = null
                    scope.launch {
                        try {
                            api.request(
                                "mobile/2fa/vincular",
                                "POST",
                                JSONObject().put("codigo", codigoVinculacion),
                            )
                            codigoVinculacion = ""
                            aviso = "Teléfono vinculado. Aquí verás tu código."
                            revision++
                        } catch (e: Exception) {
                            error = if (e is ApiError) e.message else "No se pudo vincular."
                        } finally {
                            trabajando = false
                        }
                    }
                },
            ) { Text(if (trabajando) "Vinculando…" else "Vincular este teléfono") }
        }
    }
}
