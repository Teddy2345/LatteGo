package com.example.lattego.domain.model

import java.time.LocalDate

data class Acopio(
    val idAcopio: Long,
    val idProductor: Long,
    val fecha: LocalDate,
    val cantidadLitros: Double,
    val observacion: String,
    val estado: String
)
