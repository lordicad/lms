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
  }) async {
    final response = await http.get(
      Uri.parse(url),
      headers: {
        'Accept': 'application/octet-stream',
        'Authorization': 'Bearer $token',
      },
    );
    if (response.statusCode >= 400) {
      throw const ApiException('Bahan tidak dapat dimuat turun. Sila cuba lagi.');
    }

    final cache = await getTemporaryDirectory();
    final folder = Directory('${cache.path}${Platform.pathSeparator}materials');
    if (!await folder.exists()) await folder.create(recursive: true);

    final safeName = fileName
        .replaceAll(RegExp(r'[\\/:*?"<>|]'), '_')
        .replaceAll(RegExp(r'\s+'), ' ')
        .trim();
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
}
