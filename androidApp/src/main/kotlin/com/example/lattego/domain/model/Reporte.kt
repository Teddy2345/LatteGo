package com.example.lattego.domain.model

import java.time.LocalDate

data class Reporte(
    val idReporte: Long,
    val tipo: String,
    val fechaInicio: LocalDate,
    val fechaFin: LocalDate,
    val fechaGeneracion: LocalDate
)
