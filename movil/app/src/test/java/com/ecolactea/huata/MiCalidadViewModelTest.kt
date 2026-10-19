package com.ecolactea.huata

import com.ecolactea.huata.dominio.AnalisisCalidad
import com.ecolactea.huata.dominio.CalidadProveedorRepository
import com.ecolactea.huata.dominio.ValorMedido
import com.ecolactea.huata.presentacion.MiCalidadViewModel
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test

/**
 * Estas pruebas corren en la JVM: sin emulador, sin red y sin servidor
 * encendido. Es posible porque el ViewModel depende de la interfaz
 * CalidadProveedorRepository y no de la API, así que aquí se le pasa otra
 * implementación. Eso es, en concreto, lo que da el patrón de capas.
 */
@OptIn(ExperimentalCoroutinesApi::class)
class MiCalidadViewModelTest {

    private val dispatcher = StandardTestDispatcher()

    @Before fun antes() = Dispatchers.setMain(dispatcher)

    @After fun despues() = Dispatchers.resetMain()

    /** Repositorio de mentira: devuelve lo que se le diga, sin salir a la red. */
    private class RepositorioFalso(
        private val respuesta: List<AnalisisCalidad> = emptyList(),
        private val fallo: Exception? = null,
    ) : CalidadProveedorRepository {
        override suspend fun misAnalisis(): List<AnalisisCalidad> {
            fallo?.let { throw it }

            return respuesta
        }
    }

    private fun analisisDePrueba(aceptada: Boolean = true, sancion: String? = null) = AnalisisCalidad(
        id = 1,
        acopioId = 105,
        fecha = "2026-09-21",
        aceptada = aceptada,
        motivoRechazo = if (aceptada) null else "adulteracion",
        sancion = sancion,
        valores = listOf(ValorMedido("Grasa", 3.42, "%")),
    )

    @Test
    fun `al abrir la pantalla carga los analisis del proveedor`() = runTest {
        val modelo = MiCalidadViewModel(RepositorioFalso(listOf(analisisDePrueba())))

        advanceUntilIdle()

        val estado = modelo.estado.value
        assertFalse(estado.cargando)
        assertEquals(1, estado.analisis.size)
        assertEquals("Grasa", estado.analisis.first().valores.first().nombre)
        assertEquals(null, estado.error)
    }

    @Test
    fun `si la fuente falla, la pantalla recibe el mensaje y no se queda cargando`() = runTest {
        val modelo = MiCalidadViewModel(RepositorioFalso(fallo = ApiError(500, "El servidor no responde.")))

        advanceUntilIdle()

        val estado = modelo.estado.value
        assertFalse(estado.cargando)
        assertEquals("El servidor no responde.", estado.error)
        assertTrue(estado.analisis.isEmpty())
    }

    @Test
    fun `una sancion distinta de ninguna se reconoce como castigo`() {
        assertTrue(analisisDePrueba(aceptada = false, sancion = "suspension_semana").tieneSancion)
        assertFalse(analisisDePrueba().tieneSancion)
    }
}
