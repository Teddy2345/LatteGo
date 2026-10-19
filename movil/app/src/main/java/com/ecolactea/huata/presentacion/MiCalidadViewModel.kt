package com.ecolactea.huata.presentacion

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.ecolactea.huata.Api
import com.ecolactea.huata.ApiError
import com.ecolactea.huata.datos.CalidadProveedorApiDataSource
import com.ecolactea.huata.datos.CalidadProveedorRepositoryImpl
import com.ecolactea.huata.dominio.AnalisisCalidad
import com.ecolactea.huata.dominio.CalidadProveedorRepository
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

/** Todo lo que la pantalla necesita saber para dibujarse, en un solo objeto. */
data class MiCalidadUiState(
    val cargando: Boolean = true,
    val analisis: List<AnalisisCalidad> = emptyList(),
    val error: String? = null,
)

/**
 * Sostiene el estado de la pantalla de calidad del proveedor.
 *
 * Recibe el repositorio por el constructor, y lo recibe como interfaz: este
 * ViewModel no sabe que detrás hay HTTP. Esa ignorancia es justamente lo que
 * permite probarlo sin servidor y cambiar la fuente de datos sin tocarlo.
 *
 * Al vivir en un ViewModel y no dentro del Composable, el estado sobrevive al
 * giro del teléfono: antes, girar la pantalla volvía a pedir todo a la API.
 */
class MiCalidadViewModel(
    private val repositorio: CalidadProveedorRepository,
) : ViewModel() {

    private val _estado = MutableStateFlow(MiCalidadUiState())
    val estado: StateFlow<MiCalidadUiState> = _estado.asStateFlow()

    init {
        cargar()
    }

    fun cargar() {
        _estado.value = _estado.value.copy(cargando = true, error = null)

        viewModelScope.launch {
            try {
                _estado.value = MiCalidadUiState(cargando = false, analisis = repositorio.misAnalisis())
            } catch (e: CancellationException) {
                throw e
            } catch (e: Exception) {
                _estado.value = MiCalidadUiState(
                    cargando = false,
                    error = if (e is ApiError) e.message else "No se pudieron cargar tus análisis.",
                )
            }
        }
    }

    /**
     * Arma el ViewModel con la fuente real. Es el único lugar de la pantalla
     * donde se decide de dónde vienen los datos; para una prueba basta con
     * construir el ViewModel con otro repositorio.
     */
    companion object {
        fun fabrica(api: Api): ViewModelProvider.Factory = object : ViewModelProvider.Factory {
            @Suppress("UNCHECKED_CAST")
            override fun <T : ViewModel> create(modelClass: Class<T>): T =
                MiCalidadViewModel(
                    CalidadProveedorRepositoryImpl(CalidadProveedorApiDataSource(api)),
                ) as T
        }
    }
}
