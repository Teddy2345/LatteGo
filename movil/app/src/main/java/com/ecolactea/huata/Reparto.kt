package com.ecolactea.huata

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import android.webkit.WebView
import android.webkit.WebViewClient
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.launch
import org.json.JSONObject

/**
 * La ruta del día: a quién le lleva, a dónde y cuánto cobra.
 *
 * El monto no se escribe: sale del pedido. Lo único que el repartidor
 * escribe es el número de operación cuando le pagan por Yape, y para eso
 * tiene el QR de la planta a la vista en la misma pantalla.
 */
@Composable
internal fun RepartoScreen(api: Api, alEntregar: (String) -> Unit) {
    var pedidos by remember { mutableStateOf<List<JSONObject>>(emptyList()) }
    var qrUrl by remember { mutableStateOf<String?>(null) }
    var abierto by remember { mutableStateOf<JSONObject?>(null) }
    var error by remember { mutableStateOf<String?>(null) }
    var cargando by remember { mutableStateOf(true) }
    var revision by remember { mutableStateOf(0) }

    LaunchedEffect(revision) {
        cargando = true
        try {
            val respuesta = api.request("mobile/reparto/pedidos")
            val datos = respuesta.getJSONArray("data")
            pedidos = (0 until datos.length()).map { datos.getJSONObject(it) }
            qrUrl = respuesta.optJSONObject("cobro")?.optString("qr_url")?.takeIf { it.isNotBlank() && it != "null" }
            error = null
        } catch (e: CancellationException) {
            throw e
        } catch (e: Exception) {
            error = if (e is ApiError) e.message else "No se pudo cargar la ruta."
        } finally {
            cargando = false
        }
    }

    val pedido = abierto

    if (pedido != null) {
        EntregaPedido(
            api = api,
            pedido = pedido,
            qrUrl = qrUrl,
            alVolver = { abierto = null },
            alEntregar = { comprobante ->
                abierto = null
                revision++
                alEntregar(comprobante)
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
            Text("Reparto de hoy", fontSize = 24.sp, fontWeight = FontWeight.Bold, color = DarkGreen)
            Text("Pedidos confirmados que te tocan llevar.", color = Gray, fontSize = 13.sp)
        }
        error?.let { mensaje -> item { Text(mensaje, color = MaterialTheme.colorScheme.error, fontSize = 13.sp) } }
        if (cargando) item { Text("Cargando…", color = Gray) }
        if (!cargando && error == null && pedidos.isEmpty()) {
            item { Text("No tienes pedidos pendientes de entrega.", color = Gray, fontSize = 13.sp) }
        }
        items(pedidos) { fila ->
            Card(
                onClick = { abierto = fila },
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(18.dp),
                border = BorderStroke(1.dp, Line),
                colors = CardDefaults.cardColors(containerColor = Color.White),
            ) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                        Text(fila.optString("cliente"), fontSize = 16.sp, fontWeight = FontWeight.SemiBold, color = DarkGreen)
                        Text(soles(fila.optDouble("total")), fontSize = 16.sp, fontWeight = FontWeight.Bold, color = Green)
                    }
                    Text(fila.optString("direccion"), color = Gray, fontSize = 13.sp)
                    Text(
                        fila.optString("telefono") + " · " +
                            if (fila.optString("tipo_entrega") == "recojo") "Recoge en planta" else "Delivery",
                        color = Gray, fontSize = 12.sp,
                    )
                    Text("Pedido " + fila.optString("codigo"), color = Gold, fontSize = 12.sp, fontWeight = FontWeight.Medium)
                }
            }
        }
    }
}

/** El pedido abierto: qué lleva, cuánto cobra y con qué se le paga. */
@Composable
private fun EntregaPedido(
    api: Api,
    pedido: JSONObject,
    qrUrl: String?,
    alVolver: () -> Unit,
    alEntregar: (String) -> Unit,
) {
    val scope = rememberCoroutineScope()
    var metodo by remember { mutableStateOf("efectivo") }
    var referencia by remember { mutableStateOf("") }
    var error by remember { mutableStateOf<String?>(null) }
    var enviando by remember { mutableStateOf(false) }

    val total = pedido.optDouble("total")

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(20.dp),
        verticalArrangement = Arrangement.spacedBy(14.dp),
    ) {
        item {
            TextButton(onClick = alVolver) { Text("← Volver a la ruta") }
            Text(pedido.optString("cliente"), fontSize = 22.sp, fontWeight = FontWeight.Bold, color = DarkGreen)
            Text(pedido.optString("direccion"), color = Gray, fontSize = 13.sp)
            Text(pedido.optString("telefono"), color = Gray, fontSize = 13.sp)
        }

        item {
            Card(
                Modifier.fillMaxWidth(), shape = RoundedCornerShape(18.dp), border = BorderStroke(1.dp, Line),
                colors = CardDefaults.cardColors(containerColor = Color.White),
            ) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    Text("QUÉ LLEVA", color = Gray, fontSize = 10.sp, letterSpacing = 1.2.sp)
                    val items = pedido.optJSONArray("items")
                    if (items != null) {
                        (0 until items.length()).map { items.getJSONObject(it) }.forEach { item ->
                            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                Text(
                                    cantidadCorta(item.optDouble("cantidad")) + " × " + item.optString("producto"),
                                    color = DarkGreen, fontSize = 14.sp,
                                )
                                Text(soles(item.optDouble("cantidad") * item.optDouble("precio_unitario")), color = Gray, fontSize = 13.sp)
                            }
                        }
                    }
                    pedido.optString("observaciones").takeIf { it.isNotBlank() && it != "null" }?.let {
                        Text(it, color = Gray, fontSize = 12.sp)
                    }
                }
            }
        }

        // El monto a cobrar, en grande: es el dato que se dice en voz alta.
        item {
            Card(
                Modifier.fillMaxWidth(), shape = RoundedCornerShape(18.dp),
                colors = CardDefaults.cardColors(containerColor = Green),
            ) {
                Column(
                    Modifier.fillMaxWidth().padding(20.dp),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.spacedBy(4.dp),
                ) {
                    Text("A COBRAR", color = Color.White.copy(alpha = .8f), fontSize = 10.sp, letterSpacing = 1.4.sp)
                    Text(soles(total), color = Color.White, fontSize = 40.sp, fontWeight = FontWeight.Bold)
                }
            }
        }

        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                listOf("efectivo" to "Efectivo", "yape" to "Yape").forEach { (valor, etiqueta) ->
                    Card(
                        onClick = { metodo = valor },
                        modifier = Modifier.weight(1f),
                        shape = RoundedCornerShape(16.dp),
                        border = BorderStroke(1.dp, if (metodo == valor) Green else Line),
                        colors = CardDefaults.cardColors(containerColor = if (metodo == valor) PaleGreen else Color.White),
                    ) {
                        Text(
                            etiqueta, Modifier.padding(16.dp), color = DarkGreen,
                            fontSize = 15.sp, fontWeight = FontWeight.SemiBold,
                        )
                    }
                }
            }
        }

        if (metodo == "yape") {
            item {
                Card(
                    Modifier.fillMaxWidth(), shape = RoundedCornerShape(18.dp), border = BorderStroke(1.dp, Line),
                    colors = CardDefaults.cardColors(containerColor = Color.White),
                ) {
                    Column(
                        Modifier.fillMaxWidth().padding(16.dp),
                        horizontalAlignment = Alignment.CenterHorizontally,
                        verticalArrangement = Arrangement.spacedBy(10.dp),
                    ) {
                        Text("QR DE LA PLANTA", color = Gray, fontSize = 10.sp, letterSpacing = 1.2.sp)

                        if (qrUrl != null) {
                            // El QR se muestra tal cual lo subió la planta; el
                            // monto va arriba, en grande, para dictarlo al pagar.
                            AndroidView(
                                factory = { contexto ->
                                    WebView(contexto).apply {
                                        webViewClient = WebViewClient()
                                        settings.loadWithOverviewMode = true
                                        settings.useWideViewPort = true
                                        loadUrl(qrUrl)
                                    }
                                },
                                modifier = Modifier.fillMaxWidth().height(260.dp),
                            )
                            Text(
                                "El cliente escanea y escribe " + soles(total) + ".",
                                color = Gray, fontSize = 12.sp,
                            )
                        } else {
                            Text(
                                "La planta todavía no cargó su QR. Pídele al cliente que yapee al número de la planta y anota el número de operación.",
                                color = Gray, fontSize = 13.sp,
                            )
                        }

                        OutlinedTextField(
                            value = referencia,
                            onValueChange = { referencia = it },
                            label = { Text("Número de operación") },
                            singleLine = true,
                            shape = RoundedCornerShape(14.dp),
                            modifier = Modifier.fillMaxWidth(),
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                        )
                        Text("Sin este número el cobro no se puede cotejar después con el banco.", color = Gray, fontSize = 11.sp)
                    }
                }
            }
        }

        error?.let { mensaje -> item { Text(mensaje, color = MaterialTheme.colorScheme.error, fontSize = 13.sp) } }

        item {
            Button(
                enabled = !enviando,
                modifier = Modifier.fillMaxWidth(),
                onClick = {
                    enviando = true
                    error = null
                    scope.launch {
                        try {
                            val cuerpo = JSONObject()
                                .put("metodo_pago", metodo)
                                .put("monto_cobrado", total)
                            if (metodo != "efectivo") cuerpo.put("referencia_pago", referencia.trim())

                            val respuesta = api.request(
                                "mobile/reparto/pedidos/" + pedido.optInt("id") + "/entregar",
                                "POST", cuerpo,
                            )
                            alEntregar(respuesta.optJSONObject("data")?.optString("comprobante").orEmpty())
                        } catch (e: CancellationException) {
                            throw e
                        } catch (e: Exception) {
                            error = if (e is ApiError) e.message else "No se pudo registrar la entrega."
                        } finally {
                            enviando = false
                        }
                    }
                },
            ) { Text(if (enviando) "Registrando…" else "Entregado y cobrado") }
        }
    }
}
