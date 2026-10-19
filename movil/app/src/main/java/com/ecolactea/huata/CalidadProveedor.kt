package com.ecolactea.huata

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.ecolactea.huata.dominio.AnalisisCalidad
import com.ecolactea.huata.presentacion.MiCalidadViewModel

/**
 * El resultado del análisis de su propia leche.
 *
 * El proveedor tiene derecho a ver con qué resultado se recibió lo que
 * entregó: es lo que explica una sanción y lo que le permite corregir antes
 * de la siguiente entrega.
 *
 * Esta pantalla solo dibuja. No sabe de HTTP ni de JSON: pide el estado al
 * ViewModel y pinta lo que recibe. Por eso conectar otra fuente de datos no
 * obliga a tocar ni una línea de aquí.
 */
@Composable
internal fun MiCalidadProveedorScreen(api: Api) {
    val modelo: MiCalidadViewModel = viewModel(factory = MiCalidadViewModel.fabrica(api))
    val estado by modelo.estado.collectAsState()

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(20.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Text("Calidad de mi leche", fontSize = 24.sp, fontWeight = FontWeight.Bold, color = DarkGreen)
            Text("El resultado de cada entrega que analizó la planta.", color = Gray, fontSize = 13.sp)
        }

        estado.error?.let { mensaje ->
            item {
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text(mensaje, color = MaterialTheme.colorScheme.error, fontSize = 13.sp)
                    OutlinedButton(onClick = { modelo.cargar() }) { Text("Reintentar") }
                }
            }
        }

        if (estado.cargando) item { Text("Cargando…", color = Gray) }

        if (!estado.cargando && estado.error == null && estado.analisis.isEmpty()) {
            item { Text("Todavía no hay análisis de tus entregas.", color = Gray, fontSize = 13.sp) }
        }

        items(estado.analisis) { analisis -> TarjetaDeAnalisis(analisis) }
    }
}

@Composable
private fun TarjetaDeAnalisis(analisis: AnalisisCalidad) {
    Card(
        Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(18.dp),
        border = BorderStroke(1.dp, if (analisis.aceptada) Line else Gold),
        colors = CardDefaults.cardColors(containerColor = Color.White),
    ) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text(analisis.fecha, color = DarkGreen, fontSize = 15.sp, fontWeight = FontWeight.SemiBold)
                Text(
                    if (analisis.aceptada) "Aceptada" else "Rechazada",
                    color = if (analisis.aceptada) Green else MaterialTheme.colorScheme.error,
                    fontSize = 14.sp, fontWeight = FontWeight.Bold,
                )
            }

            analisis.motivoRechazo?.let { motivo ->
                Text("Motivo: " + motivo, color = Gray, fontSize = 12.sp)
            }

            if (analisis.tieneSancion) {
                Text(
                    "Sanción aplicada: " + analisis.sancion!!.replace('_', ' '),
                    color = Gold, fontSize = 12.sp, fontWeight = FontWeight.Medium,
                )
            }

            HorizontalDivider(color = Line)

            analisis.valores.forEach { valor ->
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    Text(valor.nombre, color = Gray, fontSize = 13.sp)
                    Text(
                        cantidadCorta(valor.valor) + " " + valor.unidad,
                        color = DarkGreen, fontSize = 13.sp, fontWeight = FontWeight.Medium,
                    )
                }
            }
        }
    }
}
