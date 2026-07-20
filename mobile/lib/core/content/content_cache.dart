import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

/// Small metadata cache for the learner browse surface. It deliberately stores JSON
/// responses only: videos and YouTube streams are not copied to the device.
class ContentCache {
  const ContentCache();

  static const _prefix = 'lms_content_v1';

  Future<Map<String, dynamic>?> read({
    required String token,
    required String resource,
  }) async {
    final preferences = await SharedPreferences.getInstance();
    final raw = preferences.getString(_key(token, resource));
    if (raw == null) return null;

    try {
      final decoded = jsonDecode(raw);
      return decoded is Map<String, dynamic> ? decoded : null;
    } on FormatException {
      return null;
    }
  }

  Future<void> write({
    required String token,
    required String resource,
    required Map<String, dynamic> payload,
  }) async {
    final preferences = await SharedPreferences.getInstance();
    await preferences.setString(_key(token, resource), jsonEncode(payload));
  }

  String _key(String token, String resource) =>
      '${_prefix}_${_stableHash(token)}_$resource';

  /// A compact, stable key namespace. It is not used as cryptography; the app sandbox
  /// still protects the cached content, while this avoids storing a bearer token as a key.
  int _stableHash(String value) {
    var hash = 0x811c9dc5;
    for (final unit in utf8.encode(value)) {
      hash = (hash ^ unit) * 0x01000193 & 0xffffffff;
    }
    return hash;
  }
}
