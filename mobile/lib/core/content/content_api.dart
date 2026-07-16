import 'dart:convert';

import 'package:http/http.dart' as http;

import '../api/api_client.dart' show ApiException;
import 'content_models.dart';

/// HTTP client for the student learning endpoints (`/api/student/*`). Every call is
/// token-authenticated; the token is supplied by [ContentRepository].
class ContentApi {
  ContentApi({http.Client? httpClient}) : _http = httpClient ?? http.Client();

  static const baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api',
  );

  final http.Client _http;

  Future<DashboardData> dashboard(String token, {int? grade}) async {
    final json = await _get(token, '/student/dashboard', grade: grade);
    return DashboardData.fromJson(json);
  }

  Future<SubjectsData> subjects(String token, {int? grade}) async {
    final json = await _get(token, '/student/subjects', grade: grade);
    return SubjectsData.fromJson(json);
  }

  Future<SubjectChaptersData> subjectChapters(String token, String slug, {int? grade}) async {
    final json = await _get(token, '/student/subjects/$slug/chapters', grade: grade);
    return SubjectChaptersData.fromJson(json);
  }

  Future<ChapterDetail> chapter(String token, int chapterId) async {
    final json = await _get(token, '/student/chapters/$chapterId');
    return ChapterDetail.fromJson(json);
  }

  Future<LessonDetail> lesson(String token, int lessonId) async {
    final json = await _get(token, '/student/lessons/$lessonId');
    return LessonDetail.fromJson(json);
  }

  Future<void> markViewed(String token, int lessonId) async {
    await _post(token, '/student/lessons/$lessonId/viewed', const {});
  }

  Future<void> saveProgress(
    String token,
    int lessonId, {
    required int positionSeconds,
    int? durationSeconds,
  }) async {
    await _post(token, '/student/lessons/$lessonId/progress', {
      'position_seconds': positionSeconds,
      if (durationSeconds != null) 'duration_seconds': durationSeconds,
    });
  }

  Future<Map<String, dynamic>> _get(String token, String path, {int? grade}) async {
    final query = grade == null ? '' : '?grade=$grade';
    final response = await _http.get(
      Uri.parse('$baseUrl$path$query'),
      headers: _headers(token),
    );
    return _decode(response);
  }

  Future<Map<String, dynamic>> _post(String token, String path, Map<String, dynamic> body) async {
    final response = await _http.post(
      Uri.parse('$baseUrl$path'),
      headers: {..._headers(token), 'Content-Type': 'application/json'},
      body: jsonEncode(body),
    );
    return _decode(response);
  }

  Map<String, String> _headers(String token) => {
    'Accept': 'application/json',
    'Authorization': 'Bearer $token',
  };

  Map<String, dynamic> _decode(http.Response response) {
    final body = response.body.isEmpty ? const <String, dynamic>{} : jsonDecode(response.body);
    final map = body is Map<String, dynamic> ? body : const <String, dynamic>{};

    if (response.statusCode >= 400) {
      if (response.statusCode == 401) {
        throw const ApiException('Sesi tamat. Sila log masuk semula.');
      }
      final errors = map['errors'];
      if (errors is Map && errors.values.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) {
          throw ApiException(first.first.toString());
        }
      }
      throw ApiException((map['message'] as String?) ?? 'Tidak dapat memuatkan kandungan.');
    }

    return map;
  }
}
