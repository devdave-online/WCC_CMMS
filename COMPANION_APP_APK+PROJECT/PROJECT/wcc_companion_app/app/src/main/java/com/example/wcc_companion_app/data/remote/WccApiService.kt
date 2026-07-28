package com.example.wcc_companion_app.data.remote

import com.example.wcc_companion_app.data.remote.models.TicketDto
import com.example.wcc_companion_app.data.remote.models.WccApiResponse
import com.example.wcc_companion_app.data.remote.models.WorkOrderDto
import com.example.wcc_companion_app.data.remote.models.TakeoverRequestDto
import com.example.wcc_companion_app.data.remote.models.CloseoutRequestDto
import com.example.wcc_companion_app.data.remote.models.HoldRequestDto
import com.example.wcc_companion_app.data.remote.models.TeamMemberDto
import com.example.wcc_companion_app.data.remote.models.TicketActionDto
import com.example.wcc_companion_app.data.remote.models.InventoryPartDto
import com.example.wcc_companion_app.data.remote.models.TicketCommentDto
import com.example.wcc_companion_app.data.remote.models.CompanionListResponseDto
import com.example.wcc_companion_app.data.remote.models.CompanionStatusResponseDto
import com.example.wcc_companion_app.data.remote.models.AddCommentRequestDto
import com.example.wcc_companion_app.data.remote.models.EquipmentDto
import okhttp3.MultipartBody
import okhttp3.RequestBody
import okhttp3.ResponseBody
import retrofit2.Response
import retrofit2.http.*

interface WccApiService {
    @GET("tickets")
    suspend fun getTickets(
        @Query("status") status: String? = null,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 50
    ): Response<WccApiResponse<List<TicketDto>>>

    @GET("tickets/{id}")
    suspend fun getTicket(
        @Path("id") id: String
    ): Response<WccApiResponse<TicketDto>>

    @GET("me")
    suspend fun getCurrentUser(): Response<WccApiResponse<Map<String, Any>>>

    @POST("ticket-actions")
    suspend fun logAction(
        @Body action: Map<String, String>
    ): Response<WccApiResponse<Map<String, Int>>>

    @PUT("tickets/{id}")
    suspend fun updateTicket(
        @Path("id") id: String,
        @Body updates: Map<String, String>
    ): Response<WccApiResponse<Unit>>

    @GET("work-orders")
    suspend fun getWorkOrders(
        @Query("status") status: String? = null,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 50
    ): Response<WccApiResponse<List<WorkOrderDto>>>

    @PUT("work-orders/{id}")
    suspend fun updateWorkOrder(
        @Path("id") id: Int,
        @Body updates: Map<String, String>
    ): Response<WccApiResponse<Unit>>

    // --- NEW TICKET WORKFLOW ENDPOINTS ---

    // Note: These hit the root /api/ directory, not /api/v1/resources/
    @Headers("DynamicBaseUrl-Skip: true")
    @POST("/api/submit_takeover.php")
    suspend fun submitTakeover(
        @Body request: TakeoverRequestDto
    ): Response<Map<String, String>>

    @Headers("DynamicBaseUrl-Skip: true")
    @POST("/api/submit_closeout.php")
    suspend fun submitCloseout(
        @Body request: CloseoutRequestDto
    ): Response<Map<String, String>>

    @Headers("DynamicBaseUrl-Skip: true")
    @POST("/api/submit_hold.php")
    suspend fun submitHold(
        @Body request: HoldRequestDto
    ): Response<Map<String, String>>

    @Headers("DynamicBaseUrl-Skip: true")
    @GET("/api/get_team.php")
    suspend fun getTeamMembers(
        @Query("role") role: String = "technical"
    ): Response<com.example.wcc_companion_app.data.remote.models.LegacyTeamResponseDto>

    @Headers("DynamicBaseUrl-Skip: true")
    @FormUrlEncoded
    @POST("/login.php")
    suspend fun loginForm(
        @Field("username") user: String,
        @Field("password") pass: String
    ): Response<ResponseBody>

    // These hit the standard v1 REST API
    @GET("inventory")
    suspend fun getInventory(
        @Query("search") search: String? = null,
        @Query("per_page") perPage: Int = 100,
        @Query("page") page: Int = 1
    ): Response<WccApiResponse<List<InventoryPartDto>>>

    /** Free-text equipment search (name / asset tag / model). */
    @GET("equipment")
    suspend fun searchEquipment(
        @Query("search") search: String? = null,
        @Query("per_page") perPage: Int = 25
    ): Response<WccApiResponse<List<com.example.wcc_companion_app.data.remote.models.EquipmentDto>>>

    /** Exact asset-tag lookup used by the QR/barcode scanner. */
    @GET("equipment")
    suspend fun findEquipmentByAssetTag(
        @Query("asset_uuid") assetUuid: String
    ): Response<WccApiResponse<List<com.example.wcc_companion_app.data.remote.models.EquipmentDto>>>

    @GET("ticket-actions")
    suspend fun getTicketActions(
        @Query("ticket_id") ticketId: String
    ): Response<WccApiResponse<List<TicketActionDto>>>

    @GET("equipment/{id}")
    suspend fun getEquipment(
        @Path("id") id: Int
    ): Response<WccApiResponse<EquipmentDto>>

    /**
     * Factory health — read-only parity with `_maint/active_tickets.php` health panel.
     * Does not modify the web page.
     */
    @Headers("DynamicBaseUrl-Skip: true")
    @GET("/api/companion/factory_health.php")
    suspend fun getFactoryHealth(): Response<com.example.wcc_companion_app.data.remote.models.FactoryHealthResponseDto>

    /** Companion JSON comments — does not replace web HTML get_comments.php */
    @Headers("DynamicBaseUrl-Skip: true")
    @GET("/api/companion/ticket_comments.php")
    suspend fun getTicketComments(
        @Query("ticket_id") ticketId: String
    ): Response<CompanionListResponseDto<TicketCommentDto>>

    @Headers("DynamicBaseUrl-Skip: true")
    @POST("/api/companion/ticket_comments.php")
    suspend fun addTicketComment(
        @Body request: AddCommentRequestDto
    ): Response<CompanionStatusResponseDto>

    @Headers("DynamicBaseUrl-Skip: true")
    @GET("/api/companion/work_order.php")
    suspend fun getWorkOrderDetail(
        @Query("wo_id") woId: Int
    ): Response<com.example.wcc_companion_app.data.remote.models.CompanionWoResponseDto>

    @Headers("DynamicBaseUrl-Skip: true")
    @POST("/api/companion/work_order.php")
    suspend fun workOrderAction(
        @Body request: com.example.wcc_companion_app.data.remote.models.CompanionWoRequestDto
    ): Response<com.example.wcc_companion_app.data.remote.models.CompanionWoResponseDto>

    /** QR / DataMatrix → equipment | part | tooling */
    @Headers("DynamicBaseUrl-Skip: true")
    @GET("/api/companion/scan_lookup.php")
    suspend fun scanLookup(
        @Query("code") code: String
    ): Response<com.example.wcc_companion_app.data.remote.models.ScanLookupResponseDto>

    @Headers("DynamicBaseUrl-Skip: true")
    @GET("/api/companion/toolings.php")
    suspend fun searchToolings(
        @Query("search") search: String? = null,
        @Query("barcode") barcode: String? = null
    ): Response<com.example.wcc_companion_app.data.remote.models.CompanionListResponseDto<com.example.wcc_companion_app.data.remote.models.ToolingDto>>

    /** Gamified proficiencies + manual skills for Profile trophy case. */
    @Headers("DynamicBaseUrl-Skip: true")
    @GET("/api/companion/profile_achievements.php")
    suspend fun getProfileAchievements(): Response<com.example.wcc_companion_app.data.remote.models.AchievementsResponseDto>

    /** Offline-queued photo evidence upload. */
    @Headers("DynamicBaseUrl-Skip: true")
    @Multipart
    @POST("/api/companion/evidence_upload.php")
    suspend fun uploadEvidence(
        @Part("parent_type") parentType: RequestBody,
        @Part("parent_id") parentId: RequestBody,
        @Part("caption") caption: RequestBody,
        @Part file: MultipartBody.Part,
    ): Response<com.example.wcc_companion_app.data.remote.models.EvidenceUploadResponseDto>
}

