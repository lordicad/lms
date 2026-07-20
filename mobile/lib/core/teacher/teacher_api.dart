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

  Future<Map<String, dynamic>> _get(String token, String path) async {
    final response = await _http.get(
      Uri.parse('$baseUrl$path'),
      headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
    );

    final decoded = response.body.isEmpty ? const <String, dynamic>{} : jsonDecode(response.body);
    final map = decoded is Map<String, dynamic> ? decoded : const <String, dynamic>{};

    if (response.statusCode >= 400) {
      if (response.statusCode == 401) {
        throw const ApiException('Sesi tamat. Sila log masuk semula.');
      }
      throw ApiException((map['message'] as String?) ?? 'Tidak dapat memuatkan data.');
    }

    return map;
  }

  Future<Map<String, dynamic>> _post(String token, String path) async {
    final response = await _http.post(
      Uri.parse('$baseUrl$path'),
      headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
    );

    final decoded = response.body.isEmpty ? const <String, dynamic>{} : jsonDecode(response.body);
    final map = decoded is Map<String, dynamic> ? decoded : const <String, dynamic>{};

    if (response.statusCode >= 400) {
      if (response.statusCode == 401) {
        throw const ApiException('Sesi tamat. Sila log masuk semula.');
      }
      throw ApiException((map['message'] as String?) ?? 'Tindakan gagal.');
    }

    return map;
  }

  List<T> _mapList<T>(Object? raw, T Function(Map<String, dynamic>) fromJson) {
    if (raw is! List) return const [];
    return raw.whereType<Map<String, dynamic>>().map(fromJson).toList(growable: false);
  }
}
