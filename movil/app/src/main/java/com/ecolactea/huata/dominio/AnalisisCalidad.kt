package com.ecolactea.huata.dominio

/**
 * El análisis de una entrega de leche, con la forma que le conviene a la app.
 *
 * No sabe de JSON ni de HTTP: no importa `org.json` ni nada de Android. Si el
 * servidor mañana renombra un campo, esta clase no se entera; el cambio se
 * queda en el DTO de la capa de datos.
 */
data class AnalisisCalidad(
    val id: Int,
    val acopioId: Int,
    val fecha: String,
    val aceptada: Boolean,
    val motivoRechazo: String?,
    val sancion: String?,
    val valores: List<ValorMedido>,
) {
    /** Hubo castigo si la planta anotó uno distinto de "ninguna". */
    val tieneSancion: Boolean get() = sancion != null
}

/** Una medición del LactoScan: grasa 3,42 %, pH 6,68, etc. */
data class ValorMedido(
    val nombre: String,
    val valor: Double,
    val unidad: String,
)
