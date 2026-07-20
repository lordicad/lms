import 'dart:convert';

import 'package:http/http.dart' as http;

import '../api/api_client.dart' show ApiException;
import '../platform/native_file_picker.dart';
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

  Future<TeacherNotificationsData> notifications(String token) async {
    final json = await _get(token, '/teacher/notifications');
    return TeacherNotificationsData.fromJson(json);
  }

  Future<void> markNotificationsRead(String token) async {
    await _post(token, '/teacher/notifications/read');
  }

  Future<TeacherTalentData> talent(String token) async {
    final json = await _get(token, '/teacher/talent');
    return TeacherTalentData.fromJson(json);
  }

  Future<TeacherRankingData> ranking(
    String token, {
    int? gradeId,
    int? subjectId,
    int? quizId,
  }) async {
    final params = <String>[
      if (gradeId != null) 'grade_id=$gradeId',
      if (subjectId != null) 'subject_id=$subjectId',
      if (quizId != null) 'quiz_id=$quizId',
    ];
    final suffix = params.isEmpty ? '' : '?${params.join('&')}';
    final json = await _get(token, '/teacher/ranking$suffix');
    return TeacherRankingData.fromJson(json);
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
      if (description?.isNotEmpty ?? false) 'description': description,
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

  Future<void> updateVideo(
    String token,
    int id, {
    required int chapterId,
    required String title,
    String? description,
    required String youtubeUrl,
    required bool isPublished,
  }) async {
    await _sendJson('PUT', token, '/teacher/videos/$id', {
      'chapter_id': chapterId,
      'title': title,
      if (description != null && description.isNotEmpty)
        'description': description,
      'youtube_url': youtubeUrl,
      'is_published': isPublished,
    });
  }

  Future<void> createMaterial(
    String token, {
    required int chapterId,
    required String title,
    required NativeUploadFile file,
  }) async {
    await _sendMultipart('POST', token, '/teacher/materials', {
      'chapter_id': '$chapterId',
      'title': title,
    }, file: file);
  }

  Future<void> updateMaterial(
    String token,
    int id, {
    required int chapterId,
    required String title,
    NativeUploadFile? file,
  }) async {
    await _sendMultipart('POST', token, '/teacher/materials/$id', {
      'chapter_id': '$chapterId',
      'title': title,
    }, file: file);
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
      'duration_minutes': ?durationMinutes,
      'is_published': isPublished,
      'questions': questions
          .map((question) => question.toJson())
          .toList(growable: false),
    });

    return int.tryParse('${json['id']}') ?? 0;
  }

  Future<TeacherQuizDetail> interactiveQuiz(String token, int id) async {
    final json = await _get(token, '/teacher/quizzes/$id');
    final quiz = json['quiz'];
    return TeacherQuizDetail.fromJson(
      quiz is Map<String, dynamic> ? quiz : const <String, dynamic>{},
    );
  }

  Future<void> updateInteractiveQuiz(
    String token,
    int id, {
    required int chapterId,
    required String title,
    String? description,
    int? durationMinutes,
    required bool isPublished,
    required List<TeacherQuizQuestionDraft> questions,
  }) async {
    await _sendJson('PUT', token, '/teacher/quizzes/$id', {
      'chapter_id': chapterId,
      'title': title,
      if (description?.isNotEmpty ?? false) 'description': description,
      'duration_minutes': ?durationMinutes,
      'is_published': isPublished,
      'questions': questions
          .map((question) => question.toJson())
          .toList(growable: false),
    });
  }

  Future<TeacherQuizStats> quizStats(String token, int id) async {
    final json = await _get(token, '/teacher/quizzes/$id/stats');
    final stats = json['stats'];
    return TeacherQuizStats.fromJson(
      stats is Map<String, dynamic> ? stats : const <String, dynamic>{},
    );
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

  Future<Map<String, dynamic>> _sendMultipart(
    String method,
    String token,
    String path,
    Map<String, String> fields, {
    NativeUploadFile? file,
  }) async {
    final request = http.MultipartRequest(method, Uri.parse('$baseUrl$path'))
      ..headers.addAll({
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      })
      ..fields.addAll(fields);

    if (file != null) {
      request.files.add(
        await http.MultipartFile.fromPath(
          'file',
          file.path,
          filename: file.name,
        ),
      );
    }

    final response = await http.Response.fromStream(await request.send());
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
        if (first is List && first.isNotEmpty) {
          throw ApiException(first.first.toString());
        }
      }
      throw ApiException(
        (map['message'] as String?) ?? 'Tidak dapat menyimpan bahan.',
      );
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
