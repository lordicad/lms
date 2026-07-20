import 'dart:io';

import 'package:flutter/services.dart';

import '../api/api_client.dart';

/// Queues a token-authenticated file download in Android's system Downloads app.
/// This matches the web's honest offline behaviour without pretending YouTube files
/// can be stored locally.
class NativeDownload {
  static const _channel = MethodChannel('com.weststar.lms_moe_mobile/files');

  static Future<void> enqueue({
    required String url,
    required String token,
    required String fileName,
  }) async {
    if (!Platform.isAndroid) {
      throw const ApiException(
        'Muat turun offline kini disokong pada Android sahaja.',
      );
    }

    final id = await _channel.invokeMethod<int>('downloadFile', {
      'url': url,
      'token': token,
      'file_name': fileName,
    });
    if (id == null) {
      throw const ApiException('Muat turun tidak dapat dimulakan.');
    }
  }
}
