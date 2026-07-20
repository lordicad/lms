import 'dart:io';

import 'package:flutter/services.dart';

import '../api/api_client.dart';

/// A small Android bridge for selecting a classroom material without adding a
/// large plugin dependency. Android copies the selected document into app cache
/// and returns a path that can be streamed directly to the API.
class NativeUploadFile {
  const NativeUploadFile({
    required this.path,
    required this.name,
    required this.sizeBytes,
  });

  final String path;
  final String name;
  final int sizeBytes;
}

class NativeFilePicker {
  static const _channel = MethodChannel('com.weststar.lms_moe_mobile/files');

  static Future<NativeUploadFile?> pickMaterial() async {
    return _pick(
      'pickMaterial',
      errorMessage: 'Fail yang dipilih tidak dapat dibaca.',
    );
  }

  static Future<NativeUploadFile?> pickAvatar() async {
    return _pick(
      'pickAvatar',
      errorMessage: 'Gambar yang dipilih tidak dapat dibaca.',
    );
  }

  static Future<NativeUploadFile?> _pick(
    String method, {
    required String errorMessage,
  }) async {
    if (!Platform.isAndroid) {
      throw const ApiException(
        'Pemilihan fail kini disokong pada Android sahaja.',
      );
    }

    final raw = await _channel.invokeMapMethod<String, dynamic>(method);
    if (raw == null) return null;

    final path = raw['path']?.toString() ?? '';
    final name = raw['name']?.toString() ?? '';
    if (path.isEmpty || name.isEmpty) {
      throw ApiException(errorMessage);
    }

    return NativeUploadFile(
      path: path,
      name: name,
      sizeBytes: (raw['size'] as num?)?.toInt() ?? 0,
    );
  }
}
