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
    defaultValue: 'https://lms-moe.weststar-dev.com/api',
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

  Future<SubjectChaptersData> subjectChapters(
    String token,
    String slug, {
    int? grade,
  }) async {
    final json = await _get(
      token,
      '/student/subjects/$slug/chapters',
      grade: grade,
    );
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

  Future<List<LessonCard>> favourites(String token) async {
    final json = await _get(token, '/student/favourites');
    final list = json['lessons'];
    if (list is! List) return const [];
    return list
        .whereType<Map<String, dynamic>>()
        .map(LessonCard.fromJson)
        .toList(growable: false);
  }

  /// Toggles the favourite state of a lesson; returns the new state.
  Future<bool> toggleFavourite(String token, int lessonId) async {
    final json = await _post(
      token,
      '/student/lessons/$lessonId/favourite',
      const {},
    );
    return json['favourited'] == true;
  }

  Future<List<QuizListItem>> quizzes(String token) async {
    final json = await _get(token, '/student/quizzes');
    final list = json['quizzes'];
    if (list is! List) return const [];
    return list
        .whereType<Map<String, dynamic>>()
        .map(QuizListItem.fromJson)
        .toList(growable: false);
  }

  Future<QuizIntro> quizIntro(String token, int quizId) async {
    final json = await _get(token, '/student/quizzes/$quizId');
    return QuizIntro.fromJson(json);
  }

  Future<QuizStart> startQuiz(String token, int quizId) async {
    final json = await _post(token, '/student/quizzes/$quizId/start', const {});
    return QuizStart.fromJson(json);
  }

  /// [answers] maps a question id to the selected option ids.
  Future<QuizResult> submitQuiz(
    String token,
    int attemptId,
    Map<int, List<int>> answers,
  ) async {
    final body = {
      'answers': answers.map(
        (questionId, optionIds) => MapEntry('$questionId', optionIds),
      ),
    };
    final json = await _post(
      token,
      '/student/attempts/$attemptId/submit',
      body,
    );
    return QuizResult.fromJson(json);
  }

  Future<QuizResult> quizResult(String token, int attemptId) async {
    final json = await _get(token, '/student/attempts/$attemptId/result');
    return QuizResult.fromJson(json);
  }

  Future<RankingData> ranking(String token) async {
    final json = await _get(token, '/student/ranking');
    return RankingData.fromJson(json);
  }

  Future<Map<String, dynamic>> _get(
    String token,
    String path, {
    int? grade,
  }) async {
    final query = grade == null ? '' : '?grade=$grade';
    final response = await _http.get(
      Uri.parse('$baseUrl$path$query'),
      headers: _headers(token),
    );
    return _decode(response);
  }

  Future<Map<String, dynamic>> _post(
    String token,
    String path,
    Map<String, dynamic> body,
  ) async {
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
    final body = response.body.isEmpty
        ? const <String, dynamic>{}
        : jsonDecode(response.body);
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
      throw ApiException(
        (map['message'] as String?) ?? 'Tidak dapat memuatkan kandungan.',
      );
    }

    return map;
  }
}
