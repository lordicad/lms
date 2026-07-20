import '../api/api_client.dart' show ApiException;
import '../auth/token_store.dart';
import '../platform/native_file_picker.dart';
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

  Future<TeacherDashboardData> dashboard() async =>
      _api.dashboard(await _token());

  Future<TeacherNotificationsData> notifications() async =>
      _api.notifications(await _token());

  Future<void> markNotificationsRead() async =>
      _api.markNotificationsRead(await _token());

  Future<TeacherTalentData> talent() async => _api.talent(await _token());

  Future<TeacherRankingData> ranking({
    int? gradeId,
    int? subjectId,
    int? quizId,
  }) async => _api.ranking(
    await _token(),
    gradeId: gradeId,
    subjectId: subjectId,
    quizId: quizId,
  );

  Future<List<TeacherVideo>> videos() async => _api.videos(await _token());

  Future<List<TeacherMaterial>> materials() async =>
      _api.materials(await _token());

  Future<List<TeacherQuiz>> quizzes() async => _api.quizzes(await _token());

  Future<bool> togglePublishVideo(int id) async =>
      _api.togglePublishVideo(await _token(), id);

  Future<bool> togglePublishQuiz(int id) async =>
      _api.togglePublishQuiz(await _token(), id);

  Future<void> deleteVideo(int id) async =>
      _api.deleteVideo(await _token(), id);

  Future<void> deleteMaterial(int id) async =>
      _api.deleteMaterial(await _token(), id);

  Future<void> deleteQuiz(int id) async => _api.deleteQuiz(await _token(), id);

  Future<TeacherOptions> options() async => _api.options(await _token());

  Future<ChaptersData> chapters(int subjectId, int gradeId) async =>
      _api.chapters(await _token(), subjectId, gradeId);

  Future<void> createChapter({
    required int subjectId,
    required int gradeId,
    required String title,
    String? description,
  }) async => _api.createChapter(
    await _token(),
    subjectId: subjectId,
    gradeId: gradeId,
    title: title,
    description: description,
  );

  Future<void> updateChapter(
    int id, {
    required String title,
    String? description,
  }) async => _api.updateChapter(
    await _token(),
    id,
    title: title,
    description: description,
  );

  Future<void> deleteChapter(int id) async =>
      _api.deleteChapter(await _token(), id);

  Future<void> createVideo({
    required int chapterId,
    required String title,
    String? description,
    required String youtubeUrl,
    required bool isPublished,
  }) async => _api.createVideo(
    await _token(),
    chapterId: chapterId,
    title: title,
    description: description,
    youtubeUrl: youtubeUrl,
    isPublished: isPublished,
  );

  Future<void> updateVideo(
    int id, {
    required int chapterId,
    required String title,
    String? description,
    required String youtubeUrl,
    required bool isPublished,
  }) async => _api.updateVideo(
    await _token(),
    id,
    chapterId: chapterId,
    title: title,
    description: description,
    youtubeUrl: youtubeUrl,
    isPublished: isPublished,
  );

  Future<void> createMaterial({
    required int chapterId,
    required String title,
    required NativeUploadFile file,
  }) async => _api.createMaterial(
    await _token(),
    chapterId: chapterId,
    title: title,
    file: file,
  );

  Future<void> updateMaterial(
    int id, {
    required int chapterId,
    required String title,
    NativeUploadFile? file,
  }) async => _api.updateMaterial(
    await _token(),
    id,
    chapterId: chapterId,
    title: title,
    file: file,
  );

  Future<int> createInteractiveQuiz({
    required int chapterId,
    required String title,
    String? description,
    int? durationMinutes,
    required bool isPublished,
    required List<TeacherQuizQuestionDraft> questions,
  }) async => _api.createInteractiveQuiz(
    await _token(),
    chapterId: chapterId,
    title: title,
    description: description,
    durationMinutes: durationMinutes,
    isPublished: isPublished,
    questions: questions,
  );

  Future<TeacherQuizDetail> interactiveQuiz(int id) async =>
      _api.interactiveQuiz(await _token(), id);

  Future<void> updateInteractiveQuiz(
    int id, {
    required int chapterId,
    required String title,
    String? description,
    int? durationMinutes,
    required bool isPublished,
    required List<TeacherQuizQuestionDraft> questions,
  }) async => _api.updateInteractiveQuiz(
    await _token(),
    id,
    chapterId: chapterId,
    title: title,
    description: description,
    durationMinutes: durationMinutes,
    isPublished: isPublished,
    questions: questions,
  );

  Future<TeacherQuizStats> quizStats(int id) async =>
      _api.quizStats(await _token(), id);
}
