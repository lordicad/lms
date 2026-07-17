import '../api/api_client.dart' show ApiException;
import '../auth/token_store.dart';
import 'content_api.dart';
import 'content_models.dart';

/// Bridges the content API and the stored auth token, so screens ask for data without
/// handling tokens themselves. Reads the Sanctum token from [TokenStore] on every call.
class ContentRepository {
  ContentRepository({ContentApi? api, TokenStore? tokenStore})
    : _api = api ?? ContentApi(),
      _tokenStore = tokenStore ?? TokenStore();

  final ContentApi _api;
  final TokenStore _tokenStore;

  Future<String> _token() async {
    final token = await _tokenStore.read();
    if (token == null || token.isEmpty) {
      throw const ApiException('Sesi tamat. Sila log masuk semula.');
    }
    return token;
  }

  Future<DashboardData> dashboard({int? grade}) async =>
      _api.dashboard(await _token(), grade: grade);

  Future<SubjectsData> subjects({int? grade}) async =>
      _api.subjects(await _token(), grade: grade);

  Future<SubjectChaptersData> subjectChapters(String slug, {int? grade}) async =>
      _api.subjectChapters(await _token(), slug, grade: grade);

  Future<ChapterDetail> chapter(int chapterId) async =>
      _api.chapter(await _token(), chapterId);

  Future<LessonDetail> lesson(int lessonId) async =>
      _api.lesson(await _token(), lessonId);

  Future<void> markViewed(int lessonId) async =>
      _api.markViewed(await _token(), lessonId);

  Future<void> saveProgress(int lessonId, {required int positionSeconds, int? durationSeconds}) async =>
      _api.saveProgress(await _token(), lessonId,
          positionSeconds: positionSeconds, durationSeconds: durationSeconds);

  Future<List<LessonCard>> favourites() async => _api.favourites(await _token());

  Future<bool> toggleFavourite(int lessonId) async =>
      _api.toggleFavourite(await _token(), lessonId);

  Future<List<QuizListItem>> quizzes() async => _api.quizzes(await _token());

  Future<QuizIntro> quizIntro(int quizId) async => _api.quizIntro(await _token(), quizId);

  Future<QuizStart> startQuiz(int quizId) async => _api.startQuiz(await _token(), quizId);

  Future<QuizResult> submitQuiz(int attemptId, Map<int, List<int>> answers) async =>
      _api.submitQuiz(await _token(), attemptId, answers);

  Future<QuizResult> quizResult(int attemptId) async =>
      _api.quizResult(await _token(), attemptId);
}
