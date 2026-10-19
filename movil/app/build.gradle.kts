plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.plugin.compose")
}
android {
    namespace = "com.ecolactea.huata"
    compileSdk = 37
    defaultConfig {
        applicationId = "com.ecolactea.huata"
        minSdk = 26
        targetSdk = 37
        versionCode = 5
        versionName = "0.5.0"
    }
    buildFeatures { compose = true }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
}
dependencies {
    implementation("androidx.activity:activity-compose:1.13.0")
    implementation("androidx.compose.ui:ui:1.11.2")
    implementation("androidx.compose.material3:material3:1.5.0-alpha17")
    // Lectura de la pantalla del LactoScan en el propio telefono: el modelo
    // viaja dentro de la app, asi que funciona sin senal en la planta.
    implementation("com.google.mlkit:text-recognition:16.0.1")
    implementation("androidx.core:core-ktx:1.15.0")
    // ViewModel: sostiene el estado de la pantalla por encima del Composable,
    // asi no se pierde ni se vuelve a pedir todo al girar el telefono.
    implementation("androidx.lifecycle:lifecycle-viewmodel-compose:2.9.0")

    // Pruebas de la capa de presentacion y dominio: corren en la JVM, sin
    // emulador y sin servidor, porque el ViewModel depende de una interfaz.
    testImplementation("junit:junit:4.13.2")
    testImplementation("org.jetbrains.kotlinx:kotlinx-coroutines-test:1.10.2")
}
