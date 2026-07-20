import 'dart:convert';

import 'package:http/http.dart' as http;

import '../api/api_client.dart' show ApiException;
import 'teacher_models.dart';

/// HTTP client for the teacher endpoints (`/api/teacher/*`). Token-authenticated.
class TeacherApi {
  TeacherApi({http.Client? httpClient}) : _http = httpClient ?? http.Client();

  static const baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://lms-moe.weststar-dev.com/api',
  );

  final http.Client _http;

  Future<TeacherDashboardData> dashboard(String token) async {
    final json = await _get(token, '/teacher/dashboard');
    return TeacherDashboardData.fromJson(json);
  }

  Future<List<TeacherVideo>> videos(String token) async {
    final json = await _get(token, '/teacher/content/videos');
    return _mapList(json['videos'], TeacherVideo.fromJson);
  }

  Future<List<TeacherMaterial>> materials(String token) async {
    final json = await _get(token, '/teacher/content/materials');
    return _mapList(json['materials'], TeacherMaterial.fromJson);
  }

  Future<List<TeacherQuiz>> quizzes(String token) async {
    final json = await _get(token, '/teacher/content/quizzes');
    return _mapList(json['quizzes'], TeacherQuiz.fromJson);
  }

  Future<bool> togglePublishVideo(String token, int id) async {
    final json = await _post(token, '/teacher/content/videos/$id/publish');
    return json['published'] == true;
  }

  Future<bool> togglePublishQuiz(String token, int id) async {
    final json = await _post(token, '/teacher/content/quizzes/$id/publish');
    return json['published'] == true;
  }

  Future<void> deleteVideo(String token, int id) =>
      _delete(token, '/teacher/content/videos/$id');

  Future<void> deleteMaterial(String token, int id) =>
      _delete(token, '/teacher/content/materials/$id');

  Future<void> deleteQuiz(String token, int id) =>
      _delete(token, '/teacher/content/quizzes/$id');

  Future<TeacherOptions> options(String token) async {
    final json = await _get(token, '/teacher/options');
    return TeacherOptions.fromJson(json);
  }

  Future<ChaptersData> chapters(
    String token,
    int subjectId,
    int gradeId,
  ) async {
    final json = await _get(
      token,
      '/teacher/chapters?subject_id=$subjectId&grade_id=$gradeId',
    );
    return ChaptersData.fromJson(json);
  }

  Future<void> createChapter(
    String token, {
    required int subjectId,
    required int gradeId,
    required String title,
    String? description,
  }) async {
    await _sendJson('POST', token, '/teacher/chapters', {
      'subject_id': subjectId,
      'grade_id': gradeId,
      'title': title,
      if (description != null && description.isNotEmpty)
        'description': description,
    });
  }

  Future<void> updateChapter(
    String token,
    int id, {
    required String title,
    String? description,
  }) async {
    await _sendJson('PUT', token, '/teacher/chapters/$id', {
      'title': title,
      if (description != null && description.isNotEmpty)
        'description': description,
    });
  }

  Future<void> deleteChapter(String token, int id) =>
      _delete(token, '/teacher/chapters/$id');

  Future<void> createVideo(
    String token, {
    required int chapterId,
    required String title,
    String? description,
    required String youtubeUrl,
    required bool isPublished,
  }) async {
    await _sendJson('POST', token, '/teacher/videos', {
      'chapter_id': chapterId,
      'title': title,
      if (description != null && description.isNotEmpty)
        'description': description,
      'youtube_url': youtubeUrl,
      'is_published': isPublished,
    });
  }

  Future<int> createInteractiveQuiz(
    String token, {
    required int chapterId,
    required String title,
    String? description,
    int? durationMinutes,
    required bool isPublished,
    required List<TeacherQuizQuestionDraft> questions,
  }) async {
    final json = await _sendJson('POST', token, '/teacher/quizzes', {
      'chapter_id': chapterId,
      'title': title,
      if (description != null && description.isNotEmpty)
        'description': description,
      if (durationMinutes != null) 'duration_minutes': durationMinutes,
      'is_published': isPublished,
      'questions': questions
          .map((question) => question.toJson())
          .toList(growable: false),
    });

    return int.tryParse('${json['id']}') ?? 0;
  }

  Future<Map<String, dynamic>> _get(String token, String path) async {
    final response = await _http.get(
      Uri.parse('$baseUrl$path'),
      headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
    );

    final decoded = response.body.isEmpty
        ? const <String, dynamic>{}
        : jsonDecode(response.body);
    final map = decoded is Map<String, dynamic>
        ? decoded
        : const <String, dynamic>{};

    if (response.statusCode >= 400) {
      if (response.statusCode == 401) {
        throw const ApiException('Sesi tamat. Sila log masuk semula.');
      }
      throw ApiException(
        (map['message'] as String?) ?? 'Tidak dapat memuatkan data.',
      );
    }

    return map;
  }

  Future<Map<String, dynamic>> _post(String token, String path) async {
    final response = await _http.post(
      Uri.parse('$baseUrl$path'),
      headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
    );

    final decoded = response.body.isEmpty
        ? const <String, dynamic>{}
        : jsonDecode(response.body);
    final map = decoded is Map<String, dynamic>
        ? decoded
        : const <String, dynamic>{};

    if (response.statusCode >= 400) {
      if (response.statusCode == 401) {
        throw const ApiException('Sesi tamat. Sila log masuk semula.');
      }
      throw ApiException((map['message'] as String?) ?? 'Tindakan gagal.');
    }

    return map;
  }

  Future<Map<String, dynamic>> _sendJson(
    String method,
    String token,
    String path,
    Map<String, dynamic> body,
  ) async {
    final uri = Uri.parse('$baseUrl$path');
    final headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'Authorization': 'Bearer $token',
    };
    final encoded = jsonEncode(body);

    final response = method == 'PUT'
        ? await _http.put(uri, headers: headers, body: encoded)
        : await _http.post(uri, headers: headers, body: encoded);

    final decoded = response.body.isEmpty
        ? const <String, dynamic>{}
        : jsonDecode(response.body);
    final map = decoded is Map<String, dynamic>
        ? decoded
        : const <String, dynamic>{};

    if (response.statusCode >= 400) {
      if (response.statusCode == 401) {
        throw const ApiException('Sesi tamat. Sila log masuk semula.');
      }
      final errors = map['errors'];
      if (errors is Map && errors.values.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty)
          throw ApiException(first.first.toString());
      }
      throw ApiException((map['message'] as String?) ?? 'Tindakan gagal.');
    }

    return map;
  }

  Future<void> _delete(String token, String path) async {
    final response = await _http.delete(
      Uri.parse('$baseUrl$path'),
      headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
    );

    if (response.statusCode >= 400) {
      if (response.statusCode == 401) {
        throw const ApiException('Sesi tamat. Sila log masuk semula.');
      }
      final decoded = response.body.isEmpty
          ? const {}
          : jsonDecode(response.body);
      final message = decoded is Map ? decoded['message'] as String? : null;
      throw ApiException(message ?? 'Tidak dapat memadam.');
    }
  }

  List<T> _mapList<T>(Object? raw, T Function(Map<String, dynamic>) fromJson) {
    if (raw is! List) return const [];
    return raw
        .whereType<Map<String, dynamic>>()
        .map(fromJson)
        .toList(growable: false);
  }
}
