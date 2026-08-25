package com.example.lattego.domain.repository

import com.example.lattego.domain.model.Usuario

interface UsuarioRepository {
    suspend fun login(usuario: String, contrasena: String): Usuario?
    suspend fun obtenerUsuarioActual(): Usuario?
}
