package com.weststar.lms_moe_mobile

import android.app.Activity
import android.app.DownloadManager
import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.os.Environment
import android.provider.OpenableColumns
import androidx.core.splashscreen.SplashScreen.Companion.installSplashScreen
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodCall
import io.flutter.plugin.common.MethodChannel
import java.io.File
import java.util.UUID

class MainActivity : FlutterActivity() {
    private var pendingResult: MethodChannel.Result? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        installSplashScreen()
        super.onCreate(savedInstanceState)
    }

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, FILES_CHANNEL)
            .setMethodCallHandler { call, result ->
                if (call.method == "downloadFile") {
                    enqueueDownload(call, result)
                    return@setMethodCallHandler
                }

                if (pendingResult != null) {
                    result.error("picker_in_progress", "Pemilih fail sedang dibuka.", null)
                    return@setMethodCallHandler
                }

                val picker = when (call.method) {
                    "pickMaterial" -> PickerConfig(MATERIAL_REQUEST_CODE, MATERIAL_MIME_TYPES)
                    "pickAvatar" -> PickerConfig(AVATAR_REQUEST_CODE, AVATAR_MIME_TYPES)
                    "pickVideo" -> PickerConfig(VIDEO_REQUEST_CODE, VIDEO_MIME_TYPES)
                    else -> {
                        result.notImplemented()
                        return@setMethodCallHandler
                    }
                }

                pendingResult = result
                val intent = Intent(Intent.ACTION_OPEN_DOCUMENT).apply {
                    addCategory(Intent.CATEGORY_OPENABLE)
                    type = "*/*"
                    putExtra(Intent.EXTRA_MIME_TYPES, picker.mimeTypes)
                }
                startActivityForResult(intent, picker.requestCode)
            }
    }

    @Deprecated("Deprecated in Android SDK, retained for FlutterActivity compatibility.")
    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        if (requestCode != MATERIAL_REQUEST_CODE &&
            requestCode != AVATAR_REQUEST_CODE &&
            requestCode != VIDEO_REQUEST_CODE
        ) {
            return
        }

        val result = pendingResult ?: return
        pendingResult = null
        if (resultCode != Activity.RESULT_OK || data?.data == null) {
            result.success(null)
            return
        }

        val folder = when (requestCode) {
            AVATAR_REQUEST_CODE -> "avatars"
            VIDEO_REQUEST_CODE -> "teacher-videos"
            else -> "teacher-materials"
        }
        copySelectedFile(data.data!!, result, folder)
    }

    private fun copySelectedFile(uri: Uri, result: MethodChannel.Result, folderName: String) {
        try {
            val displayName = contentResolver.query(
                uri,
                arrayOf(OpenableColumns.DISPLAY_NAME),
                null,
                null,
                null,
            )?.use { cursor ->
                if (cursor.moveToFirst()) cursor.getString(0) else null
            } ?: "bahan"
            val extension = displayName.substringAfterLast('.', "")
            val folder = File(cacheDir, folderName).apply { mkdirs() }
            val destination = File(
                folder,
                "${UUID.randomUUID()}${if (extension.isEmpty()) "" else ".${extension}"}",
            )

            contentResolver.openInputStream(uri)?.use { input ->
                destination.outputStream().use { output -> input.copyTo(output) }
            } ?: throw IllegalStateException("Tidak dapat membuka fail")

            result.success(
                mapOf(
                    "path" to destination.absolutePath,
                    "name" to displayName,
                    "size" to destination.length(),
                ),
            )
        } catch (error: Exception) {
            result.error("file_picker_failed", error.message, null)
        }
    }

    private fun enqueueDownload(call: MethodCall, result: MethodChannel.Result) {
        val url = call.argument<String>("url")?.takeIf { it.isNotBlank() }
        val token = call.argument<String>("token")?.takeIf { it.isNotBlank() }
        val rawName = call.argument<String>("file_name")?.takeIf { it.isNotBlank() }
        if (url == null || token == null || rawName == null) {
            result.error("invalid_download", "Maklumat muat turun tidak lengkap.", null)
            return
        }

        try {
            val fileName = rawName.replace(Regex("[\\\\/:*?\"<>|]"), "_")
            val request = DownloadManager.Request(Uri.parse(url))
                .setTitle(fileName)
                .setDescription("Muat turun LMS MOE")
                .setNotificationVisibility(
                    DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED,
                )
                .setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, fileName)
            request.addRequestHeader("Authorization", "Bearer $token")
            request.addRequestHeader("Accept", "application/octet-stream")

            val manager = getSystemService(Context.DOWNLOAD_SERVICE) as DownloadManager
            result.success(manager.enqueue(request))
        } catch (error: Exception) {
            result.error("download_failed", error.message, null)
        }
    }

    companion object {
        private data class PickerConfig(
            val requestCode: Int,
            val mimeTypes: Array<String>,
        )

        private const val FILES_CHANNEL = "com.weststar.lms_moe_mobile/files"
        private const val MATERIAL_REQUEST_CODE = 4310
        private const val AVATAR_REQUEST_CODE = 4311
        private const val VIDEO_REQUEST_CODE = 4312
        private val MATERIAL_MIME_TYPES = arrayOf(
            "application/pdf",
            "application/msword",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "application/vnd.ms-powerpoint",
            "application/vnd.openxmlformats-officedocument.presentationml.presentation",
            "application/vnd.ms-excel",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "image/png",
            "image/jpeg",
        )
        private val AVATAR_MIME_TYPES = arrayOf(
            "image/jpeg",
            "image/png",
            "image/webp",
        )
        private val VIDEO_MIME_TYPES = arrayOf(
            "video/mp4",
            "video/webm",
        )
    }
}
