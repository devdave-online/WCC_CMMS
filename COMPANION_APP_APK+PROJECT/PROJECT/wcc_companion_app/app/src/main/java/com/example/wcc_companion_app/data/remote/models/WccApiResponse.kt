package com.example.wcc_companion_app.data.remote.models

data class WccApiResponse<T>(
    val success: Boolean,
    val data: T?,
    val message: String?,
    val timestamp: String?,
    val meta: MetaData? = null
)

data class MetaData(
    val page: Int,
    val per_page: Int,
    val returned: Int,
    val total: Int? = null
)
