package com.example.lattego.domain.model

import java.time.LocalDate

data class ControlCalidad(
    val idControl: Long,
    val idAcopio: Long,
    val resultado: String,
    val observacion: String,
    val fecha: LocalDate
)
