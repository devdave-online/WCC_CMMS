package com.example.wcc_companion_app.ui.scan

import android.Manifest
import android.content.pm.PackageManager
import android.content.res.Configuration
import android.util.Size
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.camera.core.CameraSelector
import androidx.camera.core.ImageAnalysis
import androidx.camera.core.ImageProxy
import androidx.camera.core.Preview
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.compose.animation.core.FastOutSlowInEasing
import androidx.compose.animation.core.RepeatMode
import androidx.compose.animation.core.animateFloat
import androidx.compose.animation.core.infiniteRepeatable
import androidx.compose.animation.core.rememberInfiniteTransition
import androidx.compose.animation.core.tween
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.CornerRadius
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size as GeomSize
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalLifecycleOwner
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.content.ContextCompat
import com.google.mlkit.vision.barcode.BarcodeScannerOptions
import com.google.mlkit.vision.barcode.BarcodeScanning
import com.google.mlkit.vision.barcode.common.Barcode
import com.google.mlkit.vision.common.InputImage
import java.util.concurrent.Executors

/**
 * Full-screen barcode scanner for asset / part / tooling tags.
 *
 * Formats: **QR_CODE** and **DATA_MATRIX** (plant labels use both).
 * Premium reticle: corner brackets + breath pulse; chrome never clips the frame.
 */
@Composable
fun ScannerScreen(
    onCodeScanned: (String) -> Unit,
    onClose: () -> Unit,
    statusMessage: String? = null,
    isLookingUp: Boolean = false,
    title: String = "Scan QR or DataMatrix"
) {
    com.example.wcc_companion_app.ui.components.KeepScreenOn()
    val context = LocalContext.current
    val lifecycleOwner = LocalLifecycleOwner.current
    val isLandscape =
        LocalConfiguration.current.orientation == Configuration.ORIENTATION_LANDSCAPE
    val primary = MaterialTheme.colorScheme.primary

    var hasPermission by remember {
        mutableStateOf(
            ContextCompat.checkSelfPermission(context, Manifest.permission.CAMERA) ==
                PackageManager.PERMISSION_GRANTED
        )
    }

    val permissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestPermission()
    ) { granted -> hasPermission = granted }

    LaunchedEffect(Unit) {
        if (!hasPermission) permissionLauncher.launch(Manifest.permission.CAMERA)
    }

    var lastCode by remember { mutableStateOf<String?>(null) }

    val pulse = rememberInfiniteTransition(label = "reticle").animateFloat(
        initialValue = 0.55f,
        targetValue = 1f,
        animationSpec = infiniteRepeatable(
            animation = tween(1100, easing = FastOutSlowInEasing),
            repeatMode = RepeatMode.Reverse
        ),
        label = "reticle_pulse"
    )

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(Color.Black)
            .windowInsetsPadding(WindowInsets.systemBars)
    ) {
        if (hasPermission) {
            val analysisExecutor = remember { Executors.newSingleThreadExecutor() }
            DisposableEffect(Unit) {
                onDispose { analysisExecutor.shutdown() }
            }

            AndroidView(
                modifier = Modifier.fillMaxSize(),
                factory = { ctx ->
                    val previewView = PreviewView(ctx).apply {
                        scaleType = PreviewView.ScaleType.FILL_CENTER
                    }
                    val providerFuture = ProcessCameraProvider.getInstance(ctx)

                    providerFuture.addListener({
                        val provider = providerFuture.get()
                        val rotation = previewView.display?.rotation ?: 0

                        val preview = Preview.Builder()
                            .setTargetRotation(rotation)
                            .build()
                            .also { it.setSurfaceProvider(previewView.surfaceProvider) }

                        val options = BarcodeScannerOptions.Builder()
                            .setBarcodeFormats(
                                Barcode.FORMAT_QR_CODE,
                                Barcode.FORMAT_DATA_MATRIX
                            )
                            .build()
                        val scanner = BarcodeScanning.getClient(options)

                        val analysis = ImageAnalysis.Builder()
                            .setTargetRotation(rotation)
                            .setTargetResolution(Size(1280, 720))
                            .setBackpressureStrategy(ImageAnalysis.STRATEGY_KEEP_ONLY_LATEST)
                            .build()
                            .also { ia ->
                                ia.setAnalyzer(analysisExecutor) { proxy: ImageProxy ->
                                    val media = proxy.image
                                    if (media == null) {
                                        proxy.close()
                                        return@setAnalyzer
                                    }
                                    val input = InputImage.fromMediaImage(
                                        media,
                                        proxy.imageInfo.rotationDegrees
                                    )
                                    scanner.process(input)
                                        .addOnSuccessListener { codes ->
                                            val value = codes.firstNotNullOfOrNull { it.rawValue }
                                            if (!value.isNullOrBlank() && value != lastCode) {
                                                lastCode = value
                                                onCodeScanned(value)
                                            }
                                        }
                                        .addOnCompleteListener { proxy.close() }
                                }
                            }

                        try {
                            provider.unbindAll()
                            provider.bindToLifecycle(
                                lifecycleOwner,
                                CameraSelector.DEFAULT_BACK_CAMERA,
                                preview,
                                analysis
                            )
                        } catch (_: Exception) {
                            // Camera unavailable — Close still works.
                        }
                    }, ContextCompat.getMainExecutor(ctx))

                    previewView
                }
            )

            val reticleW = if (isLandscape) 320.dp else 248.dp
            val reticleH = if (isLandscape) 168.dp else 248.dp
            val pulseAlpha = pulse.value

            Box(
                modifier = Modifier
                    .align(if (isLandscape) Alignment.CenterStart else Alignment.Center)
                    .padding(start = if (isLandscape) 48.dp else 0.dp)
                    .offset(y = if (isLandscape) 0.dp else (-40).dp)
                    .size(width = reticleW, height = reticleH)
            ) {
                // Soft outer glow ring
                Box(
                    modifier = Modifier
                        .matchParentSize()
                        .border(
                            BorderStroke(1.dp, primary.copy(alpha = 0.22f * pulseAlpha)),
                            RoundedCornerShape(22.dp)
                        )
                )
                // Corner-bracket reticle (premium industrial scanner look)
                Canvas(modifier = Modifier.fillMaxSize()) {
                    val stroke = 4.dp.toPx()
                    val arm = minOf(size.width, size.height) * 0.18f
                    val inset = 2.dp.toPx()
                    val color = primary.copy(alpha = 0.55f + 0.4f * pulseAlpha)
                    val r = 10.dp.toPx()

                    // Faint rounded frame
                    drawRoundRect(
                        color = primary.copy(alpha = 0.18f * pulseAlpha),
                        topLeft = Offset(inset, inset),
                        size = GeomSize(size.width - inset * 2, size.height - inset * 2),
                        cornerRadius = CornerRadius(r, r),
                        style = Stroke(width = 1.5.dp.toPx())
                    )

                    fun corner(x0: Float, y0: Float, dx: Float, dy: Float) {
                        drawLine(
                            color = color,
                            start = Offset(x0, y0 + dy),
                            end = Offset(x0, y0),
                            strokeWidth = stroke,
                            cap = StrokeCap.Round
                        )
                        drawLine(
                            color = color,
                            start = Offset(x0, y0),
                            end = Offset(x0 + dx, y0),
                            strokeWidth = stroke,
                            cap = StrokeCap.Round
                        )
                    }

                    // TL, TR, BL, BR
                    corner(inset, inset, arm, arm)
                    corner(size.width - inset, inset, -arm, arm)
                    corner(inset, size.height - inset, arm, -arm)
                    corner(size.width - inset, size.height - inset, -arm, -arm)

                    // Horizontal scan line
                    val midY = size.height * (0.28f + 0.44f * pulseAlpha)
                    drawLine(
                        color = primary.copy(alpha = 0.55f * pulseAlpha),
                        start = Offset(arm * 0.6f, midY),
                        end = Offset(size.width - arm * 0.6f, midY),
                        strokeWidth = 1.5.dp.toPx(),
                        cap = StrokeCap.Round
                    )
                }
            }

            Text(
                title,
                modifier = Modifier
                    .align(if (isLandscape) Alignment.TopStart else Alignment.TopCenter)
                    .padding(20.dp),
                color = Color.White.copy(alpha = 0.92f),
                fontWeight = FontWeight.SemiBold,
                fontSize = 14.sp
            )
        } else {
            Column(
                modifier = Modifier.align(Alignment.Center).padding(32.dp),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Text(
                    "Camera permission is needed to scan QR / DataMatrix tags.",
                    color = Color.White,
                    textAlign = TextAlign.Center
                )
                Spacer(modifier = Modifier.height(16.dp))
                Button(onClick = { permissionLauncher.launch(Manifest.permission.CAMERA) }) {
                    Text("Grant camera access")
                }
            }
        }

        Column(
            modifier = Modifier
                .align(if (isLandscape) Alignment.CenterEnd else Alignment.BottomCenter)
                .then(
                    if (isLandscape) Modifier
                        .fillMaxHeight()
                        .widthIn(max = 280.dp)
                        .padding(20.dp)
                    else Modifier
                        .fillMaxWidth()
                        .padding(20.dp)
                ),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = if (isLandscape) Arrangement.Center else Arrangement.Bottom
        ) {
            if (isLookingUp) {
                CircularProgressIndicator(color = MaterialTheme.colorScheme.primary)
                Spacer(modifier = Modifier.height(12.dp))
            }
            if (statusMessage != null) {
                Surface(
                    shape = RoundedCornerShape(16.dp),
                    color = Color.Black.copy(alpha = 0.82f),
                    border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.45f))
                ) {
                    Text(
                        text = statusMessage,
                        modifier = Modifier.padding(horizontal = 16.dp, vertical = 12.dp),
                        color = Color.White,
                        fontSize = 13.sp,
                        fontWeight = FontWeight.SemiBold,
                        textAlign = TextAlign.Center
                    )
                }
                Spacer(modifier = Modifier.height(12.dp))
            }
            OutlinedButton(
                onClick = onClose,
                modifier = Modifier
                    .height(48.dp)
                    .defaultMinSize(minWidth = 120.dp),
                colors = ButtonDefaults.outlinedButtonColors(contentColor = Color.White),
                border = BorderStroke(1.dp, Color.White.copy(alpha = 0.45f)),
                shape = RoundedCornerShape(14.dp)
            ) {
                Text("Close", fontWeight = FontWeight.Bold)
            }
        }

        LaunchedEffect(statusMessage) {
            if (statusMessage != null && !isLookingUp) lastCode = null
        }
    }
}
