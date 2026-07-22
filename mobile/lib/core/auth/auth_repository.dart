import 'dart:io';

import '../api/api_client.dart';
import '../config/app_config.dart';
import '../platform/native_file_picker.dart';
import 'auth_user.dart';
import 'token_store.dart';

class AuthRepository {
  AuthRepository({ApiClient? api, TokenStore? tokenStore})
    : _api = api ?? ApiClient(),
      _tokenStore = tokenStore ?? TokenStore();

  final ApiClient _api;
  final TokenStore _tokenStore;

  Future<AuthUser?> restoreSession() async {
    if (usePreviewAuthentication) {
      return null;
    }

    final token = await _tokenStore.read();
    if (token == null) {
      return null;
    }

    try {
      return await _api.me(token);
    } catch (_) {
      await _tokenStore.clear();
      return null;
    }
  }

  Future<AuthUser> login({
    required String login,
    required String password,
  }) async {
    if (usePreviewAuthentication) {
      return _previewUser(login, password);
    }

    final result = await _api.login(
      login: login,
      password: password,
      deviceName: _deviceName,
    );

    await _tokenStore.write(result.token);
    return result.user;
  }

  Future<void> logout() async {
    if (usePreviewAuthentication) {
      return;
    }

    final token = await _tokenStore.read();

    try {
      if (token != null) {
        await _api.logout(token);
      }
    } finally {
      await _tokenStore.clear();
    }
  }

  Future<ProfileOptions> profileOptions() async {
    final token = await _tokenStore.read();
    if (token == null) {
      throw const ApiException('Sesi anda telah tamat. Sila log masuk semula.');
    }
    return _api.profileOptions(token);
  }

  Future<AuthUser> updateProfile({
    required AuthUser currentUser,
    required ProfileUpdate update,
  }) async {
    if (usePreviewAuthentication) {
      return currentUser.copyWith(
        name: update.name,
        username: update.username,
        email: update.email,
      );
    }

    final token = await _tokenStore.read();
    if (token == null) {
      throw const ApiException('Sesi anda telah tamat. Sila log masuk semula.');
    }

    return _api.updateProfile(
      token: token,
      role: currentUser.role,
      update: update,
    );
  }

  Future<AuthUser> updateAvatar({
    required AuthUser currentUser,
    required NativeUploadFile file,
  }) async {
    if (usePreviewAuthentication) {
      return currentUser;
    }

    final token = await _tokenStore.read();
    if (token == null) {
      throw const ApiException('Sesi anda telah tamat. Sila log masuk semula.');
    }

    return _api.updateAvatar(
      token: token,
      path: file.path,
      filename: file.name,
    );
  }

  Future<AuthUser> updateFirstPassword({
    required String password,
    required String confirmation,
  }) async {
    if (usePreviewAuthentication) {
      throw const ApiException('Ciri ini tidak tersedia dalam pratonton.');
    }
    final token = await _tokenStore.read();
    if (token == null) {
      throw const ApiException('Sesi anda telah tamat. Sila log masuk semula.');
    }
    return _api.updateFirstPassword(
      token: token,
      password: password,
      confirmation: confirmation,
    );
  }

  String get _deviceName {
    if (Platform.isAndroid) {
      return 'Android LMS MOE';
    }
    if (Platform.isIOS) {
      return 'iPhone LMS MOE';
    }

    return 'LMS MOE Mobile';
  }

  /// Temporary role-routing preview. It never contacts, reads, or accepts
  /// credentials from the live LMS. Production authentication stays disabled
  /// until a separate mobile API is approved.
  AuthUser _previewUser(String login, String password) {
    if (password.isEmpty) {
      throw const ApiException('Sila isi kata laluan.');
    }

    final value = login.trim().toLowerCase();

    if (['murid', 'student'].contains(value)) {
      return const AuthUser(
        id: 1,
        name: 'Murid Contoh',
        username: 'murid',
        role: UserRole.student,
        grade: Grade(id: 1, level: 4, name: 'Tahun 4'),
      );
    }

    if (['guru', 'cikgu', 'teacher'].contains(value)) {
      return const AuthUser(
        id: 2,
        name: 'Cikgu Contoh',
        username: 'guru',
        role: UserRole.teacher,
      );
    }

    throw const ApiException(
      'Untuk pratonton, gunakan “murid” atau “guru” sebagai nama pengguna.',
    );
  }
}
