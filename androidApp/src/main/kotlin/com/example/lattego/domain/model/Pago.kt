package com.example.lattego.domain.model

import java.time.LocalDate

data class Pago(
    val idPago: Long,
    val idProductor: Long,
    val fecha: LocalDate,
    val periodo: String,
    val cantidadLitros: Double,
    val precioLitro: Double,
    val montoTotal: Double,
    val estado: String
)
