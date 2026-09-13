package com.ecolactea.huata

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.BackHandler
import androidx.activity.compose.setContent
import androidx.compose.foundation.Image
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.runtime.*
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.draw.clip
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalView
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.semantics.contentDescription
import androidx.core.view.WindowCompat
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.launch
import org.json.JSONObject
import java.time.LocalDate

private val modules = linkedMapOf("proveedores" to "Proveedores", "acopios" to "Acopios",
    "calidad" to "Calidad", "produccion" to "Producción", "despachos" to "Despachos", "pagos" to "Liquidaciones",
    "consolidado" to "Reporte por zonas", "ventas" to "Ventas", "stock" to "Stock", "auditoria" to "Auditoría")

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        val prefs = getSharedPreferences("connection", MODE_PRIVATE)
        setContent {
            MaterialTheme(colorScheme = lightColorScheme(primary = Green, secondary = DarkGreen,
                primaryContainer = PaleGreen, secondaryContainer = PaleGreen, onSecondaryContainer = DarkGreen,
                onPrimary = Color.White, onSurface = DarkGreen, onBackground = DarkGreen,
                surfaceContainer = Background, surfaceContainerHighest = PaleGreen, outline = Gray, outlineVariant = Line,
                background = Background, surface = Color.White)) {
                EcolacteaApp(prefs.getString("server", "http://10.0.2.2:8001").orEmpty()) {
                    prefs.edit().putString("server", it).apply()
                }
            }
        }
    }
}

@Composable
private fun EcolacteaApp(initialServer: String, saveServer: (String) -> Unit) {
    val context = LocalContext.current
    // La sesión se guarda cifrada con el Keystore; la contraseña nunca se almacena.
    val sesion = remember { SecureStore(context) }
    val pendientes = remember { OfflineQueue(context) }
    var server by rememberSaveable { mutableStateOf(initialServer) }
    var api by remember { mutableStateOf<Api?>(null) }
    var user by remember { mutableStateOf(JSONObject()) }
    var screen by remember { mutableStateOf("inicio") }
    var busy by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }
    var notice by remember { mutableStateOf<String?>(null) }
    var records by remember { mutableStateOf(emptyList<JSONObject>()) }
    var detail by remember { mutableStateOf<JSONObject?>(null) }
    var proveedorDetalle by remember { mutableStateOf<JSONObject?>(null) }
    var porEnviar by remember { mutableIntStateOf(pendientes.size()) }
    var sincronizando by remember { mutableStateOf(false) }
    val scope = rememberCoroutineScope()
    val view = LocalView.current
    SideEffect {
        val window = (view.context as? android.app.Activity)?.window
        if (window != null) WindowCompat.getInsetsController(window, view).isAppearanceLightStatusBars = api != null
    }
    fun execute(action: suspend () -> Unit) {
        if (busy) return
        busy = true; error = null; notice = null
        scope.launch {
            try { action() }
            catch (e: CancellationException) { throw e }
            catch (e: Exception) {
                if (e is ApiError && e.status == 401) {
                    sesion.clear(); api = null; user = JSONObject(); screen = "inicio"
                }
                error = if (e is ApiError || e is IllegalArgumentException) e.message
                    else "No se pudo conectar. Comprueba la dirección, la red Wi-Fi y que Laravel esté encendido."
            } finally { busy = false }
        }
    }

    /**
     * Envía los acopios que quedaron encolados sin señal. El `request_id` de
     * cada uno hace que reintentar sea seguro. Un registro que el servidor
     * rechaza por inválido se descarta para que no bloquee la cola; si falla
     * la red, se detiene y se reintenta más tarde.
     */
    fun sincronizarPendientes(avisar: Boolean) {
        val conexion = api ?: return
        if (sincronizando) return
        if (pendientes.size() == 0) {
            if (avisar) notice = "No hay acopios pendientes de envío."
            return
        }
        sincronizando = true
        scope.launch {
            var enviados = 0
            var descartados = 0
            var corte: String? = null
            try {
                for (payload in pendientes.all()) {
                    try {
                        conexion.request("mobile/acopios", "POST", payload)
                        pendientes.remove(payload.optString("request_id")); enviados++
                    } catch (e: CancellationException) {
                        throw e
                    } catch (e: ApiError) {
                        when (e.status) {
                            in 400..499 -> if (e.status == 401) { corte = e.message; break }
                                else { pendientes.remove(payload.optString("request_id")); descartados++ }
                            else -> { corte = e.message; break }
                        }
                    } catch (e: Exception) {
                        corte = "Sin conexión: quedan acopios por enviar."; break
                    }
                }
            } finally {
                porEnviar = pendientes.size()
                sincronizando = false
                if (corte != null && corte.contains("sesión", true)) {
                    sesion.clear(); api = null; user = JSONObject(); screen = "inicio"
                }
                error = corte
                if (corte == null && (enviados > 0 || descartados > 0)) {
                    notice = buildString {
                        if (enviados > 0) append("$enviados acopio(s) enviados a planta. ")
                        if (descartados > 0) append("$descartados quedaron descartados por datos inválidos.")
                    }.trim()
                } else if (avisar && corte == null && enviados == 0) {
                    notice = "No hay acopios pendientes de envío."
                }
            }
        }
    }

    // Restaura la sesión guardada y aprovecha para vaciar la cola de campo.
    LaunchedEffect(Unit) {
        if (api == null) {
            sesion.load()?.let { (token, guardado) ->
                runCatching { Api(Api.normalize(server), token) }.getOrNull()?.let {
                    api = it; user = guardado
                }
            }
        }
    }
    LaunchedEffect(api) {
        if (api != null && pendientes.size() > 0 && hayConexion(context)) sincronizarPendientes(false)
    }
    fun navigate(destination: String) {
        if (busy) return
        if (destination in modules && destination !in allowedModules(user)) return
        if (destination == "acopio_proveedores" && "acopio" !in actions(user)) return
        if (destination == "nueva_produccion" && "planta" !in actions(user)) return
        if (destination == "nueva_venta" && "ventas" !in actions(user)) return
        screen = destination; detail = null; records = emptyList(); error = null; notice = null
        if (destination == "acopio_proveedores") proveedorDetalle = null
        if (modules.containsKey(destination) && destination != "consolidado") execute { records = api!!.list(modulePath(destination)) }
    }
    BackHandler(enabled = api != null && screen != "inicio" && !busy) {
        if (detail != null) detail = null else navigate("inicio")
    }
    Surface(Modifier.fillMaxSize(), color = if (api == null) DarkGreen else Background) {
        Column(Modifier.fillMaxSize().safeDrawingPadding().imePadding()) {
            if (api == null) {
                Login(server, { server = it }, busy, error) { email, password ->
                    execute {
                        val address = Api.normalize(server)
                        val result = Api(address).request("login", "POST", JSONObject()
                            .put("email", email.trim()).put("password", password).put("device_name", "Ecolactea Android"))
                            .getJSONObject("data")
                        user = result.getJSONObject("user")
                        val token = result.getString("token")
                        sesion.save(token, user)
                        api = Api(address, token)
                        saveServer(server.trim()); screen = "inicio"
                    }
                }
            } else {
                Row(Modifier.fillMaxWidth().background(Background).padding(horizontal = 20.dp, vertical = 10.dp),
                    verticalAlignment = Alignment.CenterVertically) {
                    if (screen != "inicio") IconButton(modifier = Modifier.semantics { contentDescription = "Volver" }, enabled = !busy, onClick = {
                        if (detail != null) detail = null else navigate("inicio")
                    }) { Glyph("back") }
                    else { BrandLogo(42.dp); Spacer(Modifier.width(10.dp)) }
                    Column(Modifier.weight(1f)) {
                        Text("Ecolactea", color = DarkGreen, fontWeight = FontWeight.Bold, fontSize = 19.sp)
                        Text(user.optString("perfil", "HUATA"), color = Gray, fontSize = 10.sp)
                    }
                    // La campana solo le sirve a quien tiene 2FA obligatorio.
                    if (requiereDosFactores(user)) {
                        IconButton(modifier = Modifier.semantics { contentDescription = "Codigo de verificacion" },
                            enabled = !busy, onClick = { navigate("dos_factores") }) {
                            Glyph("campana", if (screen == "dos_factores") Green else Gray)
                        }
                    }
                    Surface(color = PaleGreen, shape = RoundedCornerShape(16.dp)) {
                        Text(user.optString("name").take(1).uppercase(), Modifier.padding(horizontal = 14.dp, vertical = 10.dp), color = Green, fontWeight = FontWeight.Bold)
                    }
                }
                if (busy || sincronizando) LinearProgressIndicator(Modifier.fillMaxWidth())
                if (porEnviar > 0) PendientesAviso(porEnviar, sincronizando) { sincronizarPendientes(true) }
                error?.let { Message(it, true) }
                notice?.let { Message(it, false) }
                Box(Modifier.weight(1f)) {
                    when {
                        detail != null -> Detail(modules[screen].orEmpty(), detail!!)
                        screen == "inicio" -> Home(user, busy, ::navigate)
                        screen == "perfil" -> Column(Modifier.padding(20.dp).verticalScroll(rememberScrollState()),
                            verticalArrangement = Arrangement.spacedBy(16.dp)) {
                            Title("Mi perfil", "Sesión conectada al sistema de Huata")
                            RecordCard(user)
                            Text("Servidor", color = Gray)
                            Text(server)
                            Button(enabled = !busy, onClick = {
                                execute {
                                    api!!.request("logout", "POST")
                                    sesion.clear(); api = null; user = JSONObject(); screen = "inicio"
                                }
                            }) { Text("Cerrar sesión") }
                            Text("Para cambiar de servidor, cierra la sesión. La sesión se guarda solo mientras la app está abierta.", color = Gray)
                        }
                        screen == "dos_factores" -> DosFactoresScreen(api!!)
                        screen == "consolidado" -> ConsolidatedScreen(api!!)
                        screen == "acopio_proveedores" -> ProveedorPicker(api!!, busy) { proveedor ->
                            execute {
                                proveedorDetalle = api!!.request("mobile/proveedores/${proveedor.getInt("id")}").getJSONObject("data")
                                screen = "acopio_proveedor"
                            }
                        }
                        screen == "acopio_proveedor" && proveedorDetalle != null -> ProveedorDetalle(proveedorDetalle!!, busy,
                            onNuevoLitraje = { screen = "nuevo" }, onTraslado = { screen = "traslado_zona" })
                        screen == "nuevo" && proveedorDetalle != null -> CampoForm(proveedorDetalle!!, busy) { payload ->
                            // Sin señal el acopio se guarda en el teléfono y se
                            // envía solo cuando vuelve la cobertura.
                            if (!hayConexion(context)) {
                                pendientes.add(payload); porEnviar = pendientes.size()
                                screen = "acopio_proveedor"
                                notice = "Sin señal: el acopio quedó guardado y se enviará al recuperar cobertura."
                            } else execute {
                                try {
                                    api!!.request("mobile/acopios", "POST", payload)
                                    proveedorDetalle = api!!.request("mobile/proveedores/${proveedorDetalle!!.getInt("id")}").getJSONObject("data")
                                    screen = "acopio_proveedor"
                                    notice = "Litraje enviado. El reporte de planta ya está actualizado."
                                } catch (e: ApiError) {
                                    throw e
                                } catch (e: CancellationException) {
                                    throw e
                                } catch (e: Exception) {
                                    pendientes.add(payload); porEnviar = pendientes.size()
                                    screen = "acopio_proveedor"
                                    notice = "No se pudo enviar ahora: el acopio quedó guardado y se reintentará."
                                }
                            }
                        }
                        screen == "traslado_zona" && proveedorDetalle != null -> TrasladoZonaForm(api!!, proveedorDetalle!!, busy) { payload ->
                            execute {
                                api!!.request("mobile/proveedores/${proveedorDetalle!!.getInt("id")}/cambio-zona", "POST", payload)
                                screen = "acopio_proveedor"
                                notice = "Solicitud de traslado enviada. Quedará pendiente de aprobación."
                            }
                        }
                        screen == "nueva_produccion" || screen == "nueva_venta" -> PlantaForm(api!!, screen == "nueva_venta", busy) { payload ->
                            val target=if(screen=="nueva_venta") "ventas" else "produccion"
                            execute { api!!.request("mobile/$target","POST",payload); screen="inicio"; notice="Registro guardado. Stock actualizado." }
                        }
                        else -> Column {
                            if(screen=="produccion" && "planta" in actions(user)) TextButton(enabled=!busy,onClick={navigate("nueva_produccion")}){Text("+ Registrar transformación")}
                            if(screen=="ventas" && "ventas" in actions(user)) TextButton(enabled=!busy,onClick={navigate("nueva_venta")}){Text("+ Registrar venta")}
                            if(screen=="stock") Text("Stock de los lotes registrados desde la activación del módulo móvil.",Modifier.padding(16.dp),fontSize=12.sp,color=Gray)
                            Box(Modifier.weight(1f)) { Records(allowedModules(user)[screen].orEmpty(), screen, records, busy,
                                onRefresh = { execute { records = api!!.list(modulePath(screen)) } },
                                onOpen = { row ->
                                    if(screen in setOf("ventas","stock","produccion","auditoria")) detail=row
                                    else execute { detail = api!!.request("${modulePath(screen)}/${row.getInt("id")}").getJSONObject("data") }
                                }) }
                        }
                    }
                }
                NavigationBar(containerColor = Color.White, tonalElevation = 0.dp, windowInsets = WindowInsets(0,0,0,0)) {
                    (listOf("inicio" to "Inicio") + allowedModules(user).entries.take(2).map { it.key to it.value } + listOf("perfil" to "Perfil")).forEach { (id, label) ->
                        NavigationBarItem(selected = screen == id, enabled = !busy, onClick = { navigate(id) },
                            icon = { Glyph(id, if (screen == id) DarkGreen else Gray) }, label = { Text(label, fontSize = 10.sp) })
                    }
                }
            }
        }
    }
}

@Composable
private fun Login(server: String, onServer: (String) -> Unit, busy: Boolean, error: String?, onLogin: (String, String) -> Unit) {
    var email by rememberSaveable { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var settings by rememberSaveable { mutableStateOf(false) }
    Box(Modifier.fillMaxSize()) {
        Image(painterResource(R.drawable.farm_morning), null, Modifier.matchParentSize(), contentScale = ContentScale.Crop)
        Box(Modifier.matchParentSize().background(Brush.verticalGradient(listOf(
            DarkGreen.copy(alpha = .52f), DarkGreen.copy(alpha = .48f), DarkGreen.copy(alpha = .94f)))))
        Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(horizontal = 24.dp, vertical = 24.dp),
            horizontalAlignment = Alignment.CenterHorizontally, verticalArrangement = Arrangement.Center) {
            Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                BrandLogo(72.dp)
                Column {
                    Text("Ecolactea", fontSize = 28.sp, fontWeight = FontWeight.Bold, color = Color.White)
                    Text("HUATA", color = Gold, fontSize = 12.sp, letterSpacing = 4.sp, fontWeight = FontWeight.Medium)
                }
            }
            Spacer(Modifier.height(26.dp))
            Text("Del campo, con cuidado.", fontSize = 26.sp, lineHeight = 32.sp, fontWeight = FontWeight.SemiBold, color = Color.White)
            Text("Tu jornada empieza aquí", color = Color.White.copy(alpha = .92f), fontSize = 14.sp)
            Spacer(Modifier.height(26.dp))
            Card(modifier = Modifier.widthIn(max = 460.dp).fillMaxWidth(), shape = RoundedCornerShape(28.dp),
                colors = CardDefaults.cardColors(containerColor = Background), elevation = CardDefaults.cardElevation(8.dp)) {
                Column(Modifier.padding(24.dp), verticalArrangement = Arrangement.spacedBy(14.dp)) {
                    Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                        Text("Bienvenido de nuevo", fontWeight = FontWeight.Bold, fontSize = 22.sp, color = DarkGreen)
                        Text("Ingresa a tu cuenta para continuar.", color = Gray, fontSize = 13.sp)
                    }
                    OutlinedTextField(email, { email = it }, label = { Text("Correo electrónico") }, singleLine = true,
                        leadingIcon = { Glyph("mail", Gray, Modifier.size(20.dp)) }, shape = RoundedCornerShape(14.dp),
                        enabled = !busy, modifier = Modifier.fillMaxWidth(), keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email))
                    OutlinedTextField(password, { password = it }, label = { Text("Contraseña") }, singleLine = true,
                        leadingIcon = { Glyph("lock", Gray, Modifier.size(20.dp)) }, shape = RoundedCornerShape(14.dp),
                        enabled = !busy, modifier = Modifier.fillMaxWidth(), visualTransformation = PasswordVisualTransformation(),
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password))
                    error?.let { Message(it, true) }
                    Button(onClick = { onLogin(email, password) }, enabled = !busy && email.isNotBlank() && password.isNotEmpty(),
                        modifier = Modifier.fillMaxWidth().height(54.dp), shape = RoundedCornerShape(14.dp)) {
                        if (busy) { CircularProgressIndicator(Modifier.size(18.dp), strokeWidth = 2.dp, color = Color.White); Spacer(Modifier.width(10.dp)) }
                        Text(if (busy) "Conectando…" else "Ingresar", fontSize = 16.sp, fontWeight = FontWeight.SemiBold)
                        if (!busy) { Spacer(Modifier.width(12.dp)); Glyph("arrow", Color.White, Modifier.size(18.dp)) }
                    }
                    HorizontalDivider(color = Line)
                    TextButton(onClick = { settings = !settings }, enabled = !busy, modifier = Modifier.align(Alignment.CenterHorizontally)) {
                        Text(if (settings) "Ocultar configuración" else "Configurar servidor", fontSize = 12.sp)
                    }
                    if (settings) {
                        OutlinedTextField(server, onServer, label = { Text("Dirección de Laravel") }, singleLine = true,
                            shape = RoundedCornerShape(14.dp), modifier = Modifier.fillMaxWidth(), enabled = !busy,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Uri))
                        Text("Emulador: http://10.0.2.2:8001\nTúnel USB: http://127.0.0.1:8001\nCelular por Wi-Fi: IP de tu PC y puerto 8001", color = Gray, fontSize = 12.sp)
                    }
                }
            }
            Spacer(Modifier.height(20.dp))
            Text("ACOPIO  ·  CALIDAD  ·  PRODUCCIÓN", color = Color.White.copy(alpha = .88f), fontSize = 10.sp, letterSpacing = 1.5.sp)
        }
    }
}

@Composable
private fun Home(user: JSONObject, busy: Boolean, navigate: (String) -> Unit) {
    val visible = allowedModules(user)
    val descriptions = mapOf("proveedores" to "Nuestra red de productores", "acopios" to "Recepción y trazabilidad",
        "calidad" to "Análisis y controles", "produccion" to "Proceso y trazabilidad",
        "despachos" to "Salidas de la planta", "pagos" to "Planillas y liquidaciones", "consolidado" to "Litraje recibido por zona",
        "ventas" to "Quesos y derivados", "stock" to "Existencias disponibles", "auditoria" to "Actividad de los usuarios")
    LazyColumn(Modifier.fillMaxSize(), contentPadding = PaddingValues(horizontal = 20.dp, vertical = 14.dp),
        verticalArrangement = Arrangement.spacedBy(18.dp)) {
        item {
            Text("TU ESPACIO DE TRABAJO", color = Gray, fontSize = 10.sp, letterSpacing = 1.4.sp, fontWeight = FontWeight.Medium)
            Spacer(Modifier.height(5.dp))
            Text("Hola, ${user.optString("name")}", color = DarkGreen, fontSize = 25.sp, fontWeight = FontWeight.Bold)
        }
        item {
            Box(Modifier.fillMaxWidth().height(176.dp).clip(RoundedCornerShape(24.dp))) {
                Image(painterResource(R.drawable.farm_evening), null, Modifier.matchParentSize(), contentScale = ContentScale.Crop)
                Box(Modifier.matchParentSize().background(Brush.horizontalGradient(listOf(DarkGreen.copy(alpha = .90f), DarkGreen.copy(alpha = .18f)))))
                Column(Modifier.align(Alignment.CenterStart).padding(22.dp).fillMaxWidth(.78f), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text("DE NUESTRA TIERRA", color = Gold, fontSize = 10.sp, letterSpacing = 1.5.sp, fontWeight = FontWeight.Bold)
                    Text("Calidad que nace\nen el campo.", color = Color.White, fontSize = 25.sp, lineHeight = 30.sp, fontWeight = FontWeight.SemiBold)
                    Text("Ecolactea Huata", color = Color.White.copy(alpha = .85f), fontSize = 12.sp)
                }
            }
        }
        if ("acopio" in actions(user)) item {
            Card(onClick = { navigate("acopio_proveedores") }, enabled = !busy, modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(18.dp), colors = CardDefaults.cardColors(containerColor = Green)) {
                Row(Modifier.fillMaxWidth().padding(18.dp), verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(14.dp)) {
                    Box(Modifier.size(42.dp).clip(RoundedCornerShape(12.dp)).background(Color.White.copy(alpha = .13f)), contentAlignment = Alignment.Center) {
                        Glyph("plus", Color.White)
                    }
                    Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(3.dp)) {
                        Text("Registrar acopio", color = Color.White, fontSize = 16.sp, fontWeight = FontWeight.SemiBold)
                        Text("Añade una recepción de leche", color = Color.White.copy(alpha = .82f), fontSize = 11.sp)
                    }
                    Glyph("arrow", Color.White, Modifier.size(18.dp))
                }
            }
        }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Text("Gestión de la planta", fontSize = 18.sp, color = DarkGreen, fontWeight = FontWeight.Bold)
                Text("${visible.size} módulos", color = Gray, fontSize = 11.sp)
            }
        }
        items(visible.entries.toList().chunked(2)) { pair ->
            Row(Modifier.height(IntrinsicSize.Min), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                pair.forEach { (id, label) ->
                    Card(onClick = { navigate(id) }, enabled = !busy, modifier = Modifier.weight(1f).fillMaxHeight(), border = BorderStroke(1.dp, Line),
                        colors = CardDefaults.cardColors(containerColor = Color.White), shape = RoundedCornerShape(20.dp)) {
                        Column(Modifier.padding(16.dp).heightIn(min = 102.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                            Box(Modifier.size(36.dp).clip(RoundedCornerShape(11.dp)).background(PaleGreen), contentAlignment = Alignment.Center) {
                                Glyph(id, Green, Modifier.size(21.dp))
                            }
                            Text(label, color = DarkGreen, fontSize = 15.sp, fontWeight = FontWeight.SemiBold)
                            Text(descriptions[id].orEmpty(), color = Gray, fontSize = 11.sp, lineHeight = 15.sp)
                        }
                    }
                }
                if(pair.size == 1) Spacer(Modifier.weight(1f))
            }
        }
        item { Text("Cuidamos cada paso, del campo a la planta.", color = Gray, fontSize = 11.sp, modifier = Modifier.padding(vertical = 8.dp)) }
    }
}
/**
 * Campos que se muestran en la tarjeta de cada módulo. Antes se volcaban las
 * cuatro primeras claves del JSON, que salían en orden arbitrario y con
 * nombres técnicos; aquí cada listado enseña lo que de verdad se consulta.
 */
private val camposDeLista = mapOf(
    "acopios" to listOf("fecha" to "Fecha", "cantidad_litros" to "Litros", "zona" to "Zona", "estado" to "Estado"),
    "proveedores" to listOf("cedula" to "Cédula", "telefono" to "Teléfono", "litros_prom" to "Litros promedio"),
    "calidad" to listOf("fecha" to "Fecha", "resultado" to "Resultado", "densidad" to "Densidad", "grasa" to "Grasa"),
    "despachos" to listOf("quesos_recibidos" to "Recibidos", "quesos_despachados" to "Despachados", "merma" to "Merma"),
    "pagos" to listOf("semana_inicio" to "Semana", "total_litros" to "Litros", "total_pagar" to "Total", "estado" to "Estado"),
    "auditoria" to listOf("usuario" to "Usuario", "modulo" to "Módulo", "fecha" to "Fecha"),
    "stock" to listOf("unidad" to "Unidad", "stock" to "Disponible", "precio_referencia" to "Precio referencia"),
    "produccion" to listOf(
        "fecha" to "Fecha", "cantidad" to "Obtenido",
        "litros_procesados" to "Leche usada", "litros_por_unidad" to "Litros por unidad",
    ),
    "ventas" to listOf(
        "fecha" to "Fecha", "cliente" to "Cliente",
        "cantidad" to "Cantidad", "total" to "Total",
    ),
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun Records(title: String, module: String, records: List<JSONObject>, busy: Boolean, onRefresh: () -> Unit, onOpen: (JSONObject) -> Unit) {
    var search by rememberSaveable(module) { mutableStateOf("") }
    val filtered = records.filter { it.toString().contains(search, ignoreCase = true) }
    val campos = camposDeLista[module]
    PullToRefreshBox(isRefreshing = busy, onRefresh = onRefresh, modifier = Modifier.fillMaxSize()) {
        LazyColumn(Modifier.fillMaxSize(), contentPadding = PaddingValues(20.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
            item { Title(title, "${records.size} registros cargados") }
            item {
                OutlinedTextField(search, { search = it }, modifier = Modifier.fillMaxWidth(), singleLine = true,
                    shape = RoundedCornerShape(16.dp), leadingIcon = { Glyph("search", Gray, Modifier.size(20.dp)) },
                    label = { Text("Buscar registros") })
                Text("Desliza hacia abajo para actualizar.", color = Gray, fontSize = 11.sp, modifier = Modifier.padding(top = 6.dp))
            }
            if (!busy && filtered.isEmpty()) item {
                Message(
                    if (records.isEmpty()) "Todavía no hay registros en este módulo."
                    else "Ningún registro coincide con «$search».",
                    false
                )
            }
            items(filtered, key = { it.getInt("id") }) { row ->
                Card(onClick = { onOpen(row) }, enabled = !busy, border = BorderStroke(1.dp, Line), shape = RoundedCornerShape(20.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
                    Column(Modifier.fillMaxWidth().padding(16.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                        Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                            Box(Modifier.size(36.dp).clip(RoundedCornerShape(11.dp)).background(PaleGreen), contentAlignment = Alignment.Center) { Glyph(module, modifier = Modifier.size(20.dp)) }
                            Text(row.optString("nombre").ifBlank { "$title #${row.optInt("id")}" }, modifier = Modifier.weight(1f), color = DarkGreen, fontWeight = FontWeight.SemiBold)
                        }
                        Spacer(Modifier.height(4.dp))
                        val visibles = campos?.filter { !row.isNull(it.first) }
                            ?: row.keys().asSequence().filter { it != "id" && it != "nombre" && !row.isNull(it) }.take(4).map { it to label(it) }.toList()
                        visibles.forEach { (clave, etiqueta) ->
                            Text("$etiqueta: ${value(row.opt(clave))}", color = Gray, fontSize = 13.sp)
                        }
                        Text("Ver detalle →", color = Green, fontSize = 12.sp)
                    }
                }
            }
        }
    }
}

private fun label(key: String) = mapOf("id" to "Código", "cedula" to "Cédula", "telefono" to "Teléfono",
    "litros_prom" to "Litros promedio", "precio_litro" to "Precio por litro", "proveedor_id" to "Proveedor",
    "ruta_id" to "Ruta", "cantidad_litros" to "Cantidad de litros", "perdida_litros" to "Pérdida en litros",
    "motivo_perdida" to "Motivo de pérdida", "acopiador_id" to "Acopiador", "name" to "Nombre",
    "email" to "Correo electrónico", "roles" to "Roles")[key] ?: key.replace('_', ' ').replaceFirstChar { it.titlecase() }
internal fun value(item: Any?): String = when (item) {
    null, JSONObject.NULL -> "—"
    true -> "Sí"
    false -> "No"
    is JSONObject -> item.keys().asSequence().joinToString(" · ") { "${label(it)}: ${value(item.opt(it))}" }
    is org.json.JSONArray -> (0 until item.length()).joinToString(", ") { value(item.opt(it)) }
    else -> item.toString()
}

@Composable
private fun Detail(title: String, record: JSONObject) {
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp), verticalArrangement = Arrangement.spacedBy(16.dp)) {
        Title("$title #${record.optInt("id")}", "Detalle del registro")
        RecordCard(record)
    }
}

@Composable
private fun RecordCard(record: JSONObject) {
    Card(shape = RoundedCornerShape(20.dp), border = BorderStroke(1.dp, Line), colors = CardDefaults.cardColors(containerColor = Color.White)) {
        Column(Modifier.fillMaxWidth().padding(20.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            record.keys().forEach { key ->
                Text(label(key), color = Green, fontSize = 12.sp)
                Text(value(record.opt(key)), fontSize = 16.sp)
                HorizontalDivider(color = PaleGreen)
            }
        }
    }
}

@Composable
internal fun Field(title: String, text: String, onChange: (String) -> Unit, busy: Boolean, keyboard: KeyboardType = KeyboardType.Text) {
    OutlinedTextField(text, onChange, label = { Text(title) }, enabled = !busy, singleLine = true,
        shape = RoundedCornerShape(14.dp), colors = OutlinedTextFieldDefaults.colors(unfocusedContainerColor = Color.White),
        modifier = Modifier.fillMaxWidth(), keyboardOptions = KeyboardOptions(keyboardType = keyboard))
}

@Composable
private fun FormSection(number: String, title: String) {
    Row(Modifier.fillMaxWidth().padding(top = 10.dp, bottom = 4.dp), verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(10.dp)) {
        Text(number, Modifier.clip(RoundedCornerShape(8.dp)).background(PaleGreen).padding(8.dp), color = Green, fontSize = 11.sp, fontWeight = FontWeight.Bold)
        Text(title, color = DarkGreen, fontWeight = FontWeight.SemiBold, fontSize = 15.sp)
    }
}

@Composable
private fun Title(title: String, subtitle: String) {
    Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
        Text(title, color = DarkGreen, fontWeight = FontWeight.Bold, fontSize = 25.sp)
        Text(subtitle, color = Gray, fontSize = 14.sp)
    }
}

/** Barra que recuerda cuántos acopios esperan cobertura para subir. */
@Composable
private fun PendientesAviso(cantidad: Int, enviando: Boolean, onEnviar: () -> Unit) {
    Row(
        Modifier.fillMaxWidth().padding(horizontal = 20.dp, vertical = 6.dp)
            .clip(RoundedCornerShape(12.dp)).background(Gold.copy(alpha = .30f)).padding(horizontal = 14.dp, vertical = 10.dp),
        verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(10.dp)
    ) {
        Text(
            if (cantidad == 1) "1 acopio guardado sin enviar" else "$cantidad acopios guardados sin enviar",
            Modifier.weight(1f), color = DarkGreen, fontSize = 13.sp, fontWeight = FontWeight.Medium
        )
        TextButton(onClick = onEnviar, enabled = !enviando) { Text(if (enviando) "Enviando…" else "Enviar ahora", fontSize = 13.sp) }
    }
}

@Composable
internal fun Message(text: String, error: Boolean) {
    Text(text, modifier = Modifier.fillMaxWidth().clip(RoundedCornerShape(12.dp)).background(if (error) Color(0xFFFFEBEE) else PaleGreen).padding(14.dp),
        color = if (error) Color(0xFFB71C1C) else DarkGreen, fontSize = 14.sp)
}
