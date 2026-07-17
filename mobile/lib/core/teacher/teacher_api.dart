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
    final response = await _http.get(
      Uri.parse('$baseUrl/teacher/dashboard'),
      headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
    );

    final decoded = response.body.isEmpty ? const <String, dynamic>{} : jsonDecode(response.body);
    final map = decoded is Map<String, dynamic> ? decoded : const <String, dynamic>{};

    if (response.statusCode >= 400) {
      if (response.statusCode == 401) {
        throw const ApiException('Sesi tamat. Sila log masuk semula.');
      }
      throw ApiException((map['message'] as String?) ?? 'Tidak dapat memuatkan papan pemuka.');
    }

    return TeacherDashboardData.fromJson(map);
  }
}
