package com.example.lattego.domain.model

import java.time.LocalDate

data class QRProductor(
    val idQR: Long,
    val idProductor: Long,
    val codigo: String,
    val fechaGeneracion: LocalDate,
    val estado: Boolean
)
