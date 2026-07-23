import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';

import '../api/api_client.dart';

/// Downloads a protected material into the app cache before delegating it to
/// an installed PDF, PowerPoint, Word, Excel or image reader.
class AuthenticatedFileOpener {
  static Future<void> open({
    required String url,
    required String token,
    required String fileName,
    String? fallbackExtension,
  }) async {
    final response = await http.get(
      Uri.parse(url),
      headers: {
        'Accept': 'application/octet-stream',
        'Authorization': 'Bearer $token',
      },
    );
    if (response.statusCode >= 400) {
      throw const ApiException(
        'Bahan tidak dapat dimuat turun. Sila cuba lagi.',
      );
    }
    final contentType = response.headers['content-type']?.toLowerCase() ?? '';
    final trimmed = String.fromCharCodes(
      response.bodyBytes.take(32),
    ).trimLeft().toLowerCase();
    if (contentType.contains('text/html') ||
        contentType.contains('application/json') ||
        trimmed.startsWith('<!doctype') ||
        trimmed.startsWith('<html') ||
        trimmed.startsWith('{')) {
      throw const ApiException(
        'Pelayan tidak menghantar fail bahan yang sah. Sila cuba log masuk semula.',
      );
    }

    final cache = await getTemporaryDirectory();
    final folder = Directory('${cache.path}${Platform.pathSeparator}materials');
    if (!await folder.exists()) await folder.create(recursive: true);

    final safeName = _withExtension(
      fileName
          .replaceAll(RegExp(r'[\\/:*?"<>|]'), '_')
          .replaceAll(RegExp(r'\s+'), ' ')
          .trim(),
      fallbackExtension,
    );
    final destination = File(
      '${folder.path}${Platform.pathSeparator}${safeName.isEmpty ? 'bahan' : safeName}',
    );
    await destination.writeAsBytes(response.bodyBytes, flush: true);

    final result = await OpenFilex.open(destination.path);
    if (result.type != ResultType.done) {
      throw ApiException(
        result.message.isEmpty
            ? 'Tiada aplikasi yang boleh membuka fail ini pada peranti anda.'
            : result.message,
      );
    }
  }

  static String _withExtension(String name, String? fallbackExtension) {
    final extension = (fallbackExtension ?? '')
        .replaceAll('.', '')
        .trim()
        .toLowerCase();
    if (name.isEmpty || extension.isEmpty) return name;

    final lower = name.toLowerCase();
    if (lower.endsWith('.$extension')) return name;

    final lastSegment = name.split(Platform.pathSeparator).last;
    if (lastSegment.contains('.')) return name;

    return '$name.$extension';
  }
}
