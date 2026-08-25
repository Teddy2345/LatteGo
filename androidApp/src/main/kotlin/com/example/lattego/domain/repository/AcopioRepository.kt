package com.example.lattego.domain.repository

import com.example.lattego.domain.model.Acopio

interface AcopioRepository {
    suspend fun obtenerAcopios(): List<Acopio>
    suspend fun obtenerAcopiosPorProductor(idProductor: Long): List<Acopio>
    suspend fun guardarAcopio(acopio: Acopio)
    suspend fun obtenerAcopioPorId(id: Long): Acopio?
}
