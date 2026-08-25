package com.example.lattego.domain.model

data class Usuario(
    val idUsuario: Long,
    val idRol: Long,
    val nombre: String,
    val apellido: String,
    val usuario: String,
    val contrasena: String,
    val estado: Boolean
)
