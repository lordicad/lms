import 'dart:convert';

import 'package:http/http.dart' as http;

import '../api/api_client.dart' show ApiException;
import 'content_cache.dart';
import 'content_models.dart';

/// HTTP client for the student learning endpoints (`/api/student/*`). Every call is
/// token-authenticated; the token is supplied by [ContentRepository].
class ContentApi {
  ContentApi({http.Client? httpClient, ContentCache? cache})
    : _http = httpClient ?? http.Client(),
      _cache = cache ?? const ContentCache();

  static const baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://lms-moe.weststar-dev.com/api',
  );

  final http.Client _http;
  final ContentCache _cache;

  Future<DashboardData> dashboard(String token, {int? grade}) async {
    final json = await _get(
      token,
      '/student/dashboard',
      grade: grade,
      cacheResource: 'dashboard_${grade ?? 'own'}',
    );
    return DashboardData.fromJson(json);
  }

  Future<SubjectsData> subjects(String token, {int? grade}) async {
    final json = await _get(
      token,
      '/student/subjects',
      grade: grade,
      cacheResource: 'subjects_${grade ?? 'own'}',
    );
    return SubjectsData.fromJson(json);
  }

  Future<List<SearchResult>> search(
    String token,
    String query, {
    int? grade,
  }) async {
    final encodedQuery = Uri.encodeQueryComponent(query.trim());
    final json = await _get(
      token,
      '/student/search?q=$encodedQuery',
      grade: grade,
    );
    final list = json['results'];
    if (list is! List) return const [];
    return list
        .whereType<Map<String, dynamic>>()
        .map(SearchResult.fromJson)
        .toList(growable: false);
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
      cacheResource: 'subject_${slug}_${grade ?? 'own'}',
    );
    return SubjectChaptersData.fromJson(json);
  }

  Future<ChapterDetail> chapter(String token, int chapterId) async {
    final json = await _get(
      token,
      '/student/chapters/$chapterId',
      cacheResource: 'chapter_$chapterId',
    );
    return ChapterDetail.fromJson(json);
  }

  Future<LessonDetail> lesson(String token, int lessonId) async {
    final json = await _get(
      token,
      '/student/lessons/$lessonId',
      cacheResource: 'lesson_$lessonId',
    );
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

  Future<OfflineData> offline(String token, {int? grade}) async {
    final json = await _get(
      token,
      '/student/offline',
      grade: grade,
      cacheResource: 'offline_${grade ?? 'own'}',
    );
    return OfflineData.fromJson(json);
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

  Future<List<QuizListItem>> quizzes(String token, {int? grade}) async {
    final json = await _get(
      token,
      '/student/quizzes',
      grade: grade,
      cacheResource: 'quizzes_${grade ?? 'own'}',
    );
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
    String? cacheResource,
  }) async {
    final baseUri = Uri.parse('$baseUrl$path');
    final uri = grade == null
        ? baseUri
        : baseUri.replace(
            queryParameters: {...baseUri.queryParameters, 'grade': '$grade'},
          );

    try {
      final response = await _http.get(uri, headers: _headers(token));
      final decoded = _decode(response);
      if (cacheResource != null) {
        try {
          await _cache.write(
            token: token,
            resource: cacheResource,
            payload: decoded,
          );
        } catch (_) {
          // A full or unavailable preferences store must not prevent online learning.
        }
      }
      return decoded;
    } on ApiException {
      // Authentication and server validation failures must never be hidden by stale data.
      rethrow;
    } catch (_) {
      if (cacheResource != null) {
        try {
          final cached = await _cache.read(
            token: token,
            resource: cacheResource,
          );
          if (cached != null) return cached;
        } catch (_) {
          // Preserve the original network error when cache retrieval also fails.
        }
      }
      rethrow;
    }
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
