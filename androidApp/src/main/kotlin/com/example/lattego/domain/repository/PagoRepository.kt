package com.example.lattego.domain.repository

import com.example.lattego.domain.model.Pago

interface PagoRepository {
    suspend fun obtenerPagosPorProductor(idProductor: Long): List<Pago>
    suspend fun guardarPago(pago: Pago)
    suspend fun obtenerPagoPorId(id: Long): Pago?
}
