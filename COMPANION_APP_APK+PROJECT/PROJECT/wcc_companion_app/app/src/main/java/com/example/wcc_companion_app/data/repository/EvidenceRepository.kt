package com.example.wcc_companion_app.data.repository

import android.content.Context
import android.net.Uri
import com.example.wcc_companion_app.data.local.dao.PendingMediaDao
import com.example.wcc_companion_app.data.local.entity.PendingMediaEntity
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.withContext
import java.io.File
import java.util.UUID
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class EvidenceRepository @Inject constructor(
    @ApplicationContext private val context: Context,
    private val mediaDao: PendingMediaDao,
) {
    fun observeForTicket(ticketId: String): Flow<List<PendingMediaEntity>> =
        mediaDao.observeForParent(parentKey("TICKET", ticketId))

    fun observeForWorkOrder(woId: Int): Flow<List<PendingMediaEntity>> =
        mediaDao.observeForParent(parentKey("WO", woId.toString()))

    fun observePendingCount(): Flow<Int> = mediaDao.observePendingCount()

    suspend fun getPendingOrdered(): List<PendingMediaEntity> = mediaDao.getPendingOrdered()

    /**
     * Copy a content URI (camera / gallery) into app-private evidence storage and queue upload.
     */
    suspend fun enqueueFromUri(
        parentType: String,
        parentId: String,
        uri: Uri,
        mimeType: String = "image/jpeg",
        caption: String? = null,
    ): PendingMediaEntity = withContext(Dispatchers.IO) {
        val dir = File(context.filesDir, "evidence").apply { mkdirs() }
        val mediaId = UUID.randomUUID().toString()
        val resolvedMime = context.contentResolver.getType(uri) ?: mimeType
        val ext = when {
            resolvedMime.contains("png") -> "png"
            resolvedMime.contains("webp") -> "webp"
            resolvedMime.contains("gif") -> "gif"
            else -> "jpg"
        }
        val dest = File(dir, "$mediaId.$ext")

        // Gallery / FileProvider URIs need an open grant; openInputStream uses the
        // temporary read permission from GetContent / TakePicture.
        val input = try {
            context.contentResolver.openInputStream(uri)
        } catch (e: SecurityException) {
            error("No permission to read image: ${e.message}")
        } ?: error("Could not read image stream for $uri")

        input.use { stream ->
            dest.outputStream().use { output ->
                val n = stream.copyTo(output)
                if (n <= 0L) error("Image stream was empty")
            }
        }
        if (!dest.exists() || dest.length() <= 0L) {
            error("Failed to write evidence file")
        }

        val row = PendingMediaEntity(
            mediaId = mediaId,
            parentType = parentType.uppercase(),
            parentId = parentId,
            parentKey = parentKey(parentType, parentId),
            localPath = dest.absolutePath,
            mimeType = resolvedMime,
            caption = caption,
            status = "PENDING",
        )
        mediaDao.insert(row)
        android.util.Log.i("WccEvidence", "Queued $mediaId for $parentType:$parentId (${dest.length()} bytes)")
        row
    }

    suspend fun markUploaded(mediaId: String, remoteUrl: String?) {
        mediaDao.updateStatus(mediaId, "UPLOADED", remoteUrl = remoteUrl)
        // Keep row briefly for UI, or delete — delete frees space
        mediaDao.delete(mediaId)
    }

    suspend fun markFailed(mediaId: String, error: String) {
        mediaDao.updateStatus(mediaId, "FAILED", error = error, retryInc = 1)
    }

    suspend fun markInFlight(mediaId: String) {
        mediaDao.updateStatus(mediaId, "IN_FLIGHT")
    }

    suspend fun deleteLocal(mediaId: String) {
        File(context.filesDir, "evidence").listFiles()
            ?.filter { it.name.startsWith(mediaId) }
            ?.forEach { it.delete() }
        mediaDao.delete(mediaId)
    }

    suspend fun clearAll() {
        File(context.filesDir, "evidence").listFiles()?.forEach { it.delete() }
        mediaDao.clearAll()
    }

    companion object {
        fun parentKey(type: String, id: String): String = "${type.uppercase()}:$id"
    }
}
