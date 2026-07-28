package com.example.wcc_companion_app.data.remote.models

/**
 * Factory health snapshot — parity with `_maint/active_tickets.php` health panel.
 * Served by `/api/companion/factory_health.php` (read-only).
 */
data class FactoryHealthDto(
    val health_pct: Double = 100.0,
    val band: String = "healthy",
    val total_machines: Int = 0,
    val down_machines: Int = 0,
    val live_tickets: Int = 0,
    val by_status: Map<String, Int>? = null,
    val updated_at: String? = null
) {
    val isHealthy: Boolean get() = band.equals("healthy", ignoreCase = true)
    val isDegraded: Boolean get() = band.equals("degraded", ignoreCase = true)
    val isCritical: Boolean get() = band.equals("critical", ignoreCase = true)
}

data class FactoryHealthResponseDto(
    val status: String,
    val data: FactoryHealthDto? = null,
    val message: String? = null
)
