package com.ecolactea.huata

import androidx.compose.foundation.Canvas
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.graphics.drawscope.scale
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp

internal val DarkGreen = Color(0xFF173E32)
internal val Green = Color(0xFF356B50)
internal val PaleGreen = Color(0xFFE7EEE3)
internal val Background = Color(0xFFF7F7F0)
internal val Gray = Color(0xFF68746A)
internal val Gold = Color(0xFFE4C58A)
internal val Line = Color(0xFFDFE5DA)

@Composable
internal fun BrandLogo(size: Dp = 64.dp) {
    Image(painterResource(R.drawable.brand_logo), "Logo de Ecolactea Huata",
        Modifier.size(size).clip(CircleShape).background(Color.White).border(1.dp, Line, CircleShape).padding(3.dp),
        contentScale = ContentScale.Fit)
}

/** Consistent 24-unit line icons, drawn natively with rounded strokes. */
@Composable
internal fun Glyph(name: String, color: Color = Green, modifier: Modifier = Modifier.size(24.dp)) {
    Canvas(modifier) {
        scale(size.width / 24f, size.height / 24f, pivot = Offset.Zero) {
            val stroke = Stroke(1.7f, cap = StrokeCap.Round)
            fun line(x: Float, y: Float, x2: Float, y2: Float) = drawLine(color, Offset(x,y), Offset(x2,y2), 1.7f, StrokeCap.Round)
            fun path(vararg points: Float) {
                val p = Path().apply { moveTo(points[0], points[1]); for (i in 2 until points.size step 2) lineTo(points[i], points[i+1]) }
                drawPath(p, color, style = stroke)
            }
            when (name) {
                "inicio" -> { path(3f,11f,12f,3f,21f,11f); path(5f,10f,5f,21f,10f,21f,10f,15f,14f,15f,14f,21f,19f,21f,19f,10f) }
                "perfil", "proveedores" -> {
                    drawCircle(color, 3.5f, Offset(12f,7f), style = stroke)
                    drawPath(Path().apply { moveTo(4f,21f); cubicTo(4f,11f,20f,11f,20f,21f) }, color, style = stroke)
                }
                "acopios" -> {
                    drawPath(Path().apply { moveTo(12f,2f); cubicTo(9f,7f,5f,10f,5f,15f); cubicTo(5f,24f,19f,24f,19f,15f); cubicTo(19f,10f,15f,7f,12f,2f); close() }, color, style = stroke)
                    drawPath(Path().apply { moveTo(9f,15f); quadraticTo(9f,18f,12f,18f) }, color, style = stroke)
                }
                "calidad" -> { path(12f,2f,20f,5f,20f,12f,17f,18f,12f,22f,7f,18f,4f,12f,4f,5f,12f,2f); path(8f,12f,11f,15f,16f,9f) }
                "produccion" -> { path(3f,21f,3f,11f,9f,7f,9f,11f,15f,7f,15f,11f,21f,11f,21f,21f,3f,21f); line(18f,11f,18f,3f); line(7f,16f,7f,17f); line(12f,16f,12f,17f); line(17f,16f,17f,17f) }
                "despachos" -> { path(2f,17f,2f,5f,14f,5f,14f,17f); path(14f,9f,19f,9f,22f,13f,22f,17f,20f,17f); line(8f,17f,16f,17f); drawCircle(color,2f,Offset(6f,18f), style=stroke); drawCircle(color,2f,Offset(18f,18f),style=stroke) }
                "pagos" -> { drawRect(color,Offset(3f,4f),Size(18f,16f),style=stroke); line(3f,9f,21f,9f); line(7f,15f,11f,15f) }
                "plus" -> { line(12f,5f,12f,19f); line(5f,12f,19f,12f) }
                "arrow" -> { path(9f,5f,16f,12f,9f,19f) }
                "back" -> { path(15f,5f,8f,12f,15f,19f) }
                "search" -> { drawCircle(color,6.5f,Offset(10f,10f),style=stroke); line(15f,15f,21f,21f) }
                "mail" -> { drawRect(color,Offset(3f,5f),Size(18f,14f),style=stroke); path(3f,6f,12f,13f,21f,6f) }
                "lock" -> { drawRect(color,Offset(5f,10f),Size(14f,11f),style=stroke); drawPath(Path().apply { moveTo(8f,10f); lineTo(8f,7f); cubicTo(8f,1f,16f,1f,16f,7f); lineTo(16f,10f) },color,style=stroke); line(12f,14f,12f,17f) }
                "campana" -> {
                    drawPath(Path().apply { moveTo(5f,17f); cubicTo(7f,15f,7f,13f,7f,10f); cubicTo(7f,4f,17f,4f,17f,10f); cubicTo(17f,13f,17f,15f,19f,17f); close() }, color, style = stroke)
                    drawPath(Path().apply { moveTo(10f,20f); cubicTo(11f,22f,13f,22f,14f,20f) }, color, style = stroke)
                }
                else -> { drawCircle(color,8f,Offset(12f,12f),style=stroke); line(12f,8f,12f,12f); line(12f,16f,12f,16.2f) }
            }
        }
    }
}
