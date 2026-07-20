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

  Future<List<TeacherVideo>> videos() async => _api.videos(await _token());

  Future<List<TeacherMaterial>> materials() async => _api.materials(await _token());

  Future<List<TeacherQuiz>> quizzes() async => _api.quizzes(await _token());

  Future<bool> togglePublishVideo(int id) async =>
      _api.togglePublishVideo(await _token(), id);

  Future<bool> togglePublishQuiz(int id) async =>
      _api.togglePublishQuiz(await _token(), id);

  Future<void> deleteVideo(int id) async => _api.deleteVideo(await _token(), id);

  Future<void> deleteMaterial(int id) async => _api.deleteMaterial(await _token(), id);

  Future<void> deleteQuiz(int id) async => _api.deleteQuiz(await _token(), id);

  Future<TeacherOptions> options() async => _api.options(await _token());

  Future<ChaptersData> chapters(int subjectId, int gradeId) async =>
      _api.chapters(await _token(), subjectId, gradeId);

  Future<void> createChapter({
    required int subjectId,
    required int gradeId,
    required String title,
    String? description,
  }) async =>
      _api.createChapter(await _token(),
          subjectId: subjectId, gradeId: gradeId, title: title, description: description);

  Future<void> updateChapter(int id, {required String title, String? description}) async =>
      _api.updateChapter(await _token(), id, title: title, description: description);

  Future<void> deleteChapter(int id) async => _api.deleteChapter(await _token(), id);

  Future<void> createVideo({
    required int chapterId,
    required String title,
    String? description,
    required String youtubeUrl,
    required bool isPublished,
  }) async =>
      _api.createVideo(
        await _token(),
        chapterId: chapterId,
        title: title,
        description: description,
        youtubeUrl: youtubeUrl,
        isPublished: isPublished,
      );
}
