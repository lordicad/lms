import '../api/api_client.dart' show ApiException;
import '../auth/token_store.dart';
import 'teacher_api.dart';
import 'teacher_models.dart';

/// Bridges the teacher API and the stored auth token, mirroring ContentRepository.
class TeacherRepository {
  TeacherRepository({TeacherApi? api, TokenStore? tokenStore})
    : _api = api ?? TeacherApi(),
      _tokenStore = tokenStore ?? TokenStore();

  final TeacherApi _api;
  final TokenStore _tokenStore;

  Future<String> _token() async {
    final token = await _tokenStore.read();
    if (token == null || token.isEmpty) {
      throw const ApiException('Sesi tamat. Sila log masuk semula.');
    }
    return token;
  }

  Future<TeacherDashboardData> dashboard() async => _api.dashboard(await _token());
}
