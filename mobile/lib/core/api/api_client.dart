import 'dart:convert';

import 'package:http/http.dart' as http;

import '../auth/auth_user.dart';

class ApiException implements Exception {
  const ApiException(this.message);

  final String message;

  @override
  String toString() => message;
}

class LoginResult {
  const LoginResult({required this.token, required this.user});

  final String token;
  final AuthUser user;
}

class ApiClient {
  ApiClient({http.Client? httpClient}) : _http = httpClient ?? http.Client();

  /// Defaults to production; override per environment, for example:
  /// flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api  (local emulator)
  static const baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://lms-moe.weststar-dev.com/api',
  );

  final http.Client _http;

  Future<LoginResult> login({
    required String login,
    required String password,
    required String deviceName,
  }) async {
    final response = await _http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: const {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'login': login,
        'password': password,
        'device_name': deviceName,
      }),
    );

    final body = _decode(response);
    _throwIfUnsuccessful(response, body);

    return LoginResult(
      token: body['token'] as String,
      user: AuthUser.fromJson(body['user'] as Map<String, dynamic>),
    );
  }

  Future<AuthUser> me(String token) async {
    final response = await _http.get(
      Uri.parse('$baseUrl/auth/me'),
      headers: _authHeaders(token),
    );

    final body = _decode(response);
    _throwIfUnsuccessful(response, body);

    return AuthUser.fromJson(body['user'] as Map<String, dynamic>);
  }

  Future<void> logout(String token) async {
    final response = await _http.post(
      Uri.parse('$baseUrl/auth/logout'),
      headers: _authHeaders(token),
    );

    if (response.statusCode != 204 && response.statusCode >= 400) {
      final body = _decode(response);
      _throwIfUnsuccessful(response, body);
    }
  }

  Map<String, String> _authHeaders(String token) => {
    'Accept': 'application/json',
    'Authorization': 'Bearer $token',
  };

  Map<String, dynamic> _decode(http.Response response) {
    if (response.body.isEmpty) {
      return const {};
    }

    final decoded = jsonDecode(response.body);
    return decoded is Map<String, dynamic> ? decoded : const {};
  }

  void _throwIfUnsuccessful(http.Response response, Map<String, dynamic> body) {
    if (response.statusCode < 400) {
      return;
    }

    final errors = body['errors'];
    if (errors is Map && errors.values.isNotEmpty) {
      final first = errors.values.first;
      if (first is List && first.isNotEmpty) {
        throw ApiException(first.first.toString());
      }
    }

    throw ApiException(
      (body['message'] as String?) ?? 'Tidak dapat menyambung ke pelayan.',
    );
  }
}
