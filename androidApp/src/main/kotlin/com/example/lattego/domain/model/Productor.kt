package com.example.lattego.domain.model

data class Productor(
    val idProductor: Long,
    val nombre: String,
    val apellido: String,
    val dni: String,
    val telefono: String,
    val estado: Boolean
)
