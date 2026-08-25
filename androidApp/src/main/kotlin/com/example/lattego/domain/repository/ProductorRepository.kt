package com.example.lattego.domain.repository

import com.example.lattego.domain.model.Productor

interface ProductorRepository {
    suspend fun obtenerProductores(): List<Productor>
    suspend fun obtenerProductorPorId(id: Long): Productor?
    suspend fun guardarProductor(productor: Productor)
    suspend fun eliminarProductor(id: Long)
}
