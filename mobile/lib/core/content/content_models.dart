import 'dart:ui' show Color;

/// Data models for the student learning surface. Each mirrors a JSON payload from the
/// `/api/student/*` endpoints. Kept plain (fromJson only) to match the existing app style.

int _int(Object? v) => v is int ? v : int.tryParse('$v') ?? 0;
int? _intOrNull(Object? v) =>
    v == null ? null : (v is int ? v : int.tryParse('$v'));
String _str(Object? v) => v?.toString() ?? '';
String? _strOrNull(Object? v) => v?.toString();
bool _bool(Object? v) => v == true;

class GradeInfo {
  const GradeInfo({required this.id, required this.level, required this.name});

  final int id;
  final int level;
  final String name;

  factory GradeInfo.fromJson(Map<String, dynamic> j) => GradeInfo(
    id: _int(j['id']),
    level: _int(j['level']),
    name: _str(j['name']),
  );
}

class SubjectCard {
  const SubjectCard({
    required this.id,
    required this.name,
    required this.displayName,
    required this.slug,
    required this.category,
    required this.colorHex,
    required this.lessonsCount,
  });

  final int id;
  final String name;
  final String displayName;
  final String slug;
  final String category;
  final String? colorHex;
  final int lessonsCount;

  /// The subject accent, parsed from "#RRGGBB". Falls back to brand teal.
  Color get color {
    final hex = (colorHex ?? '').replaceAll('#', '');
    if (hex.length == 6) {
      final value = int.tryParse(hex, radix: 16);
      if (value != null) return Color(0xFF000000 | value);
    }
    return const Color(0xFF0F766E);
  }

  factory SubjectCard.fromJson(Map<String, dynamic> j) => SubjectCard(
    id: _int(j['id']),
    name: _str(j['name']),
    displayName: _str(j['display_name']),
    slug: _str(j['slug']),
    category: _str(j['category']),
    colorHex: _strOrNull(j['color']),
    lessonsCount: _int(j['lessons_count']),
  );
}

class LessonCard {
  const LessonCard({
    required this.id,
    required this.title,
    required this.thumbnailUrl,
    required this.durationLabel,
    required this.source,
    required this.subjectName,
    required this.chapterLabel,
    required this.percent,
    required this.completed,
    required this.favourited,
    required this.watched,
  });

  final int id;
  final String title;
  final String? thumbnailUrl;
  final String? durationLabel;
  final String source;
  final String? subjectName;
  final String? chapterLabel;
  final int percent;
  final bool completed;
  final bool favourited;
  final bool watched;

  factory LessonCard.fromJson(Map<String, dynamic> j) => LessonCard(
    id: _int(j['id']),
    title: _str(j['title']),
    thumbnailUrl: _strOrNull(j['thumbnail_url']),
    durationLabel: _strOrNull(j['duration_label']),
    source: _str(j['source']),
    subjectName: _strOrNull(j['subject_name']),
    chapterLabel: _strOrNull(j['chapter_label']),
    percent: _int(j['percent']),
    completed: _bool(j['completed']),
    favourited: _bool(j['favourited']),
    watched: _bool(j['watched']),
  );
}

/// A grade-scoped result from the learner catalogue search.
class SearchResult {
  const SearchResult({
    required this.id,
    required this.kind,
    required this.title,
    required this.subjectName,
    required this.chapterLabel,
    required this.thumbnailUrl,
    required this.quizType,
    required this.questionCount,
    required this.percent,
    required this.completed,
  });

  final int id;
  final String kind; // 'lesson' | 'quiz'
  final String title;
  final String? subjectName;
  final String? chapterLabel;
  final String? thumbnailUrl;
  final String? quizType;
  final int? questionCount;
  final int? percent;
  final bool completed;

  bool get isLesson => kind == 'lesson';
  bool get isFileQuiz => quizType == 'file';

  factory SearchResult.fromJson(Map<String, dynamic> j) => SearchResult(
    id: _int(j['id']),
    kind: _str(j['kind']),
    title: _str(j['title']),
    subjectName: _strOrNull(j['subject_name']),
    chapterLabel: _strOrNull(j['chapter_label']),
    thumbnailUrl: _strOrNull(j['thumbnail_url']),
    quizType: _strOrNull(j['quiz_type']),
    questionCount: _intOrNull(j['question_count']),
    percent: _intOrNull(j['percent']),
    completed: _bool(j['completed']),
  );
}

class DashboardData {
  const DashboardData({
    required this.grade,
    required this.grades,
    required this.points,
    required this.rank,
    required this.hero,
    required this.heroResuming,
    required this.continueWatching,
    required this.trending,
    required this.trendingFallback,
    required this.newest,
    required this.suggested,
    required this.subjects,
  });

  final GradeInfo? grade;
  final List<GradeInfo> grades;
  final int points;
  final int? rank;
  final LessonCard? hero;
  final bool heroResuming;
  final List<LessonCard> continueWatching;
  final List<LessonCard> trending;
  final bool trendingFallback;
  final List<LessonCard> newest;
  final List<LessonCard> suggested;
  final List<SubjectCard> subjects;

  factory DashboardData.fromJson(Map<String, dynamic> j) => DashboardData(
    grade: j['grade'] == null
        ? null
        : GradeInfo.fromJson(j['grade'] as Map<String, dynamic>),
    grades: _list(j['grades'], GradeInfo.fromJson),
    points: _int(j['points']),
    rank: _intOrNull(j['rank']),
    hero: j['hero'] == null
        ? null
        : LessonCard.fromJson(j['hero'] as Map<String, dynamic>),
    heroResuming: _bool(j['hero_resuming']),
    continueWatching: _list(j['continue_watching'], LessonCard.fromJson),
    trending: _list(j['trending'], LessonCard.fromJson),
    trendingFallback: _bool(j['trending_fallback']),
    newest: _list(j['newest'], LessonCard.fromJson),
    suggested: _list(j['suggested'], LessonCard.fromJson),
    subjects: _list(j['subjects'], SubjectCard.fromJson),
  );
}

// --- Offline downloads (mirrors the web Simpanan Offline page) ---

class OfflineData {
  const OfflineData({
    required this.grade,
    required this.lessons,
    required this.materials,
  });

  final GradeInfo? grade;
  final List<OfflineLesson> lessons;
  final List<OfflineMaterial> materials;

  factory OfflineData.fromJson(Map<String, dynamic> j) => OfflineData(
    grade: j['grade'] == null
        ? null
        : GradeInfo.fromJson(j['grade'] as Map<String, dynamic>),
    lessons: _list(j['lessons'], OfflineLesson.fromJson),
    materials: _list(j['materials'], OfflineMaterial.fromJson),
  );
}

class OfflineLesson {
  const OfflineLesson({
    required this.id,
    required this.title,
    required this.source,
    required this.thumbnailUrl,
    required this.subjectName,
    required this.chapterLabel,
    required this.downloadable,
    required this.downloadUrl,
    required this.fileName,
  });

  final int id;
  final String title;
  final String source;
  final String? thumbnailUrl;
  final String? subjectName;
  final String? chapterLabel;
  final bool downloadable;
  final String? downloadUrl;
  final String? fileName;

  bool get isYoutube => source == 'youtube';

  factory OfflineLesson.fromJson(Map<String, dynamic> j) => OfflineLesson(
    id: _int(j['id']),
    title: _str(j['title']),
    source: _str(j['source']),
    thumbnailUrl: _strOrNull(j['thumbnail_url']),
    subjectName: _strOrNull(j['subject_name']),
    chapterLabel: _strOrNull(j['chapter_label']),
    downloadable: _bool(j['downloadable']),
    downloadUrl: _strOrNull(j['download_url']),
    fileName: _strOrNull(j['file_name']),
  );
}

class OfflineMaterial {
  const OfflineMaterial({
    required this.id,
    required this.title,
    required this.extension,
    required this.humanSize,
    required this.subjectName,
    required this.chapterLabel,
    required this.downloadUrl,
    required this.fileName,
  });

  final int id;
  final String title;
  final String extension;
  final String humanSize;
  final String? subjectName;
  final String? chapterLabel;
  final String downloadUrl;
  final String fileName;

  factory OfflineMaterial.fromJson(Map<String, dynamic> j) => OfflineMaterial(
    id: _int(j['id']),
    title: _str(j['title']),
    extension: _str(j['extension']),
    humanSize: _str(j['human_size']),
    subjectName: _strOrNull(j['subject_name']),
    chapterLabel: _strOrNull(j['chapter_label']),
    downloadUrl: _str(j['download_url']),
    fileName: _str(j['file_name']),
  );
}

class SubjectCategoryGroup {
  const SubjectCategoryGroup({
    required this.key,
    required this.label,
    required this.subjects,
  });

  final String key;
  final String label;
  final List<SubjectCard> subjects;

  factory SubjectCategoryGroup.fromJson(Map<String, dynamic> j) =>
      SubjectCategoryGroup(
        key: _str(j['key']),
        label: _str(j['label']),
        subjects: _list(j['subjects'], SubjectCard.fromJson),
      );
}

class SubjectsData {
  const SubjectsData({required this.grade, required this.categories});

  final GradeInfo? grade;
  final List<SubjectCategoryGroup> categories;

  factory SubjectsData.fromJson(Map<String, dynamic> j) => SubjectsData(
    grade: j['grade'] == null
        ? null
        : GradeInfo.fromJson(j['grade'] as Map<String, dynamic>),
    categories: _list(j['categories'], SubjectCategoryGroup.fromJson),
  );
}

class ChapterListItem {
  const ChapterListItem({
    required this.id,
    required this.number,
    required this.title,
    required this.label,
    required this.lessonsCount,
    required this.materialsCount,
    required this.quizzesCount,
    required this.watchedCount,
  });

  final int id;
  final int number;
  final String title;
  final String label;
  final int lessonsCount;
  final int materialsCount;
  final int quizzesCount;
  final int watchedCount;

  factory ChapterListItem.fromJson(Map<String, dynamic> j) => ChapterListItem(
    id: _int(j['id']),
    number: _int(j['number']),
    title: _str(j['title']),
    label: _str(j['label']),
    lessonsCount: _int(j['lessons_count']),
    materialsCount: _int(j['materials_count']),
    quizzesCount: _int(j['quizzes_count']),
    watchedCount: _int(j['watched_count']),
  );
}

class SubjectChaptersData {
  const SubjectChaptersData({
    required this.subject,
    required this.grade,
    required this.chapters,
  });

  final SubjectCard subject;
  final GradeInfo grade;
  final List<ChapterListItem> chapters;

  factory SubjectChaptersData.fromJson(Map<String, dynamic> j) =>
      SubjectChaptersData(
        subject: SubjectCard.fromJson(j['subject'] as Map<String, dynamic>),
        grade: GradeInfo.fromJson(j['grade'] as Map<String, dynamic>),
        chapters: _list(j['chapters'], ChapterListItem.fromJson),
      );
}

class MaterialItem {
  const MaterialItem({
    required this.id,
    required this.title,
    required this.iconName,
    required this.extension,
    required this.humanSize,
    required this.downloadUrl,
  });

  final int id;
  final String title;
  final String iconName;
  final String extension;
  final String humanSize;
  final String downloadUrl;

  factory MaterialItem.fromJson(Map<String, dynamic> j) => MaterialItem(
    id: _int(j['id']),
    title: _str(j['title']),
    iconName: _str(j['icon_name']),
    extension: _str(j['extension']),
    humanSize: _str(j['human_size']),
    downloadUrl: _str(j['download_url']),
  );
}

class QuizItem {
  const QuizItem({
    required this.id,
    required this.title,
    required this.type,
    required this.myAttemptsCount,
  });

  final int id;
  final String title;
  final String type;
  final int myAttemptsCount;

  factory QuizItem.fromJson(Map<String, dynamic> j) => QuizItem(
    id: _int(j['id']),
    title: _str(j['title']),
    type: _str(j['type']),
    myAttemptsCount: _int(j['my_attempts_count']),
  );
}

class ChapterDetail {
  const ChapterDetail({
    required this.id,
    required this.label,
    required this.description,
    required this.subject,
    required this.grade,
    required this.lessons,
    required this.materials,
    required this.quizzes,
  });

  final int id;
  final String label;
  final String? description;
  final SubjectCard subject;
  final GradeInfo grade;
  final List<LessonCard> lessons;
  final List<MaterialItem> materials;
  final List<QuizItem> quizzes;

  factory ChapterDetail.fromJson(Map<String, dynamic> j) {
    final chapter = j['chapter'] as Map<String, dynamic>;
    return ChapterDetail(
      id: _int(chapter['id']),
      label: _str(chapter['label']),
      description: _strOrNull(chapter['description']),
      subject: SubjectCard.fromJson(j['subject'] as Map<String, dynamic>),
      grade: GradeInfo.fromJson(j['grade'] as Map<String, dynamic>),
      lessons: _list(j['lessons'], LessonCard.fromJson),
      materials: _list(j['materials'], MaterialItem.fromJson),
      quizzes: _list(j['quizzes'], QuizItem.fromJson),
    );
  }
}

class LessonProgressInfo {
  const LessonProgressInfo({
    required this.positionSeconds,
    required this.percent,
    required this.completed,
  });

  final int positionSeconds;
  final int percent;
  final bool completed;

  factory LessonProgressInfo.fromJson(Map<String, dynamic> j) =>
      LessonProgressInfo(
        positionSeconds: _int(j['position_seconds']),
        percent: _int(j['percent']),
        completed: _bool(j['completed']),
      );
}

class LessonNeighbour {
  const LessonNeighbour({required this.id, required this.title});
  final int id;
  final String title;

  factory LessonNeighbour.fromJson(Map<String, dynamic> j) =>
      LessonNeighbour(id: _int(j['id']), title: _str(j['title']));
}

class LessonDetail {
  const LessonDetail({
    required this.id,
    required this.title,
    required this.description,
    required this.source,
    required this.youtubeId,
    required this.videoUrl,
    required this.thumbnailUrl,
    required this.durationSeconds,
    required this.teacherName,
    required this.chapterLabel,
    required this.subject,
    required this.progress,
    required this.favourited,
    required this.previous,
    required this.next,
    required this.materials,
  });

  final int id;
  final String title;
  final String? description;
  final String source;
  final String? youtubeId;
  final String? videoUrl;
  final String? thumbnailUrl;
  final int? durationSeconds;
  final String? teacherName;
  final String chapterLabel;
  final SubjectCard subject;
  final LessonProgressInfo? progress;
  final bool favourited;
  final LessonNeighbour? previous;
  final LessonNeighbour? next;
  final List<MaterialItem> materials;

  bool get isYoutube => source == 'youtube';

  factory LessonDetail.fromJson(Map<String, dynamic> j) {
    final lesson = j['lesson'] as Map<String, dynamic>;
    final chapter = j['chapter'] as Map<String, dynamic>;
    return LessonDetail(
      id: _int(lesson['id']),
      title: _str(lesson['title']),
      description: _strOrNull(lesson['description']),
      source: _str(lesson['source']),
      youtubeId: _strOrNull(lesson['youtube_id']),
      videoUrl: _strOrNull(lesson['video_url']),
      thumbnailUrl: _strOrNull(lesson['thumbnail_url']),
      durationSeconds: _intOrNull(lesson['duration_seconds']),
      teacherName: _strOrNull(lesson['teacher_name']),
      chapterLabel: _str(chapter['label']),
      subject: SubjectCard.fromJson(j['subject'] as Map<String, dynamic>),
      progress: j['progress'] == null
          ? null
          : LessonProgressInfo.fromJson(j['progress'] as Map<String, dynamic>),
      favourited: _bool(j['favourited']),
      previous: j['previous'] == null
          ? null
          : LessonNeighbour.fromJson(j['previous'] as Map<String, dynamic>),
      next: j['next'] == null
          ? null
          : LessonNeighbour.fromJson(j['next'] as Map<String, dynamic>),
      materials: _list(j['materials'], MaterialItem.fromJson),
    );
  }
}

List<T> _list<T>(Object? raw, T Function(Map<String, dynamic>) fromJson) {
  if (raw is! List) return const [];
  return raw
      .whereType<Map<String, dynamic>>()
      .map(fromJson)
      .toList(growable: false);
}

List<int> _intList(Object? raw) {
  if (raw is! List) return const [];
  return raw.map(_int).toList(growable: false);
}

// --- Quiz models (mirror the /api/student/quizzes + /attempts endpoints) ---

class QuizOption {
  const QuizOption({
    required this.id,
    required this.letter,
    required this.text,
    this.isCorrect = false,
  });

  final int id;
  final String letter;
  final String text;
  final bool isCorrect; // only meaningful on the result payload

  factory QuizOption.fromJson(Map<String, dynamic> j) => QuizOption(
    id: _int(j['id']),
    letter: _str(j['letter']),
    text: _str(j['text']),
    isCorrect: _bool(j['is_correct']),
  );
}

class QuizQuestion {
  const QuizQuestion({
    required this.id,
    required this.text,
    required this.type,
    required this.points,
    required this.options,
  });

  final int id;
  final String text;
  final String type; // 'single' | 'multiple'
  final int points;
  final List<QuizOption> options;

  bool get isMultiple => type == 'multiple';

  factory QuizQuestion.fromJson(Map<String, dynamic> j) => QuizQuestion(
    id: _int(j['id']),
    text: _str(j['text']),
    type: _str(j['type']),
    points: _int(j['points']),
    options: _list(j['options'], QuizOption.fromJson),
  );
}

class QuizStart {
  const QuizStart({
    required this.attemptId,
    required this.secondsLeft,
    required this.quizTitle,
    required this.questions,
  });

  final int attemptId;
  final int? secondsLeft;
  final String quizTitle;
  final List<QuizQuestion> questions;

  factory QuizStart.fromJson(Map<String, dynamic> j) => QuizStart(
    attemptId: _int(j['attempt_id']),
    secondsLeft: _intOrNull(j['seconds_left']),
    quizTitle: _str((j['quiz'] as Map<String, dynamic>?)?['title']),
    questions: _list(j['questions'], QuizQuestion.fromJson),
  );
}

class QuizAttemptSummary {
  const QuizAttemptSummary({
    required this.id,
    required this.score,
    required this.maxScore,
    required this.percent,
    required this.countsForRanking,
  });

  final int id;
  final int score;
  final int maxScore;
  final int percent;
  final bool countsForRanking;

  factory QuizAttemptSummary.fromJson(Map<String, dynamic> j) =>
      QuizAttemptSummary(
        id: _int(j['id']),
        score: _int(j['score']),
        maxScore: _int(j['max_score']),
        percent: _int(j['percent']),
        countsForRanking: _bool(j['counts_for_ranking']),
      );
}

class QuizIntro {
  const QuizIntro({
    required this.id,
    required this.title,
    required this.description,
    required this.type,
    required this.durationMinutes,
    required this.subjectName,
    required this.chapterLabel,
    required this.questionCount,
    required this.maxScore,
    required this.fileUrl,
    required this.hasRankedAttempt,
    required this.myAttempts,
  });

  final int id;
  final String title;
  final String? description;
  final String type; // 'interactive' | 'file'
  final int? durationMinutes;
  final String? subjectName;
  final String chapterLabel;
  final int questionCount;
  final int maxScore;
  final String? fileUrl;
  final bool hasRankedAttempt;
  final List<QuizAttemptSummary> myAttempts;

  bool get isFile => type == 'file';

  factory QuizIntro.fromJson(Map<String, dynamic> j) {
    final subject = j['subject'] as Map<String, dynamic>?;
    final chapter = j['chapter'] as Map<String, dynamic>?;
    final quiz = j['quiz'] as Map<String, dynamic>? ?? const {};
    return QuizIntro(
      id: _int(quiz['id']),
      title: _str(quiz['title']),
      description: _strOrNull(quiz['description']),
      type: _str(quiz['type']),
      durationMinutes: _intOrNull(quiz['duration_minutes']),
      subjectName: subject == null ? null : _strOrNull(subject['display_name']),
      chapterLabel: _str(chapter?['label']),
      questionCount: _int(j['question_count']),
      maxScore: _int(j['max_score']),
      fileUrl: _strOrNull(j['file_url']),
      hasRankedAttempt: _bool(j['has_ranked_attempt']),
      myAttempts: _list(j['my_attempts'], QuizAttemptSummary.fromJson),
    );
  }
}

class QuizResultQuestion {
  const QuizResultQuestion({
    required this.id,
    required this.text,
    required this.type,
    required this.isCorrect,
    required this.yourOptionIds,
    required this.options,
  });

  final int id;
  final String text;
  final String type;
  final bool isCorrect;
  final List<int> yourOptionIds;
  final List<QuizOption> options;

  factory QuizResultQuestion.fromJson(Map<String, dynamic> j) =>
      QuizResultQuestion(
        id: _int(j['id']),
        text: _str(j['text']),
        type: _str(j['type']),
        isCorrect: _bool(j['is_correct']),
        yourOptionIds: _intList(j['your_option_ids']),
        options: _list(j['options'], QuizOption.fromJson),
      );
}

class QuizResult {
  const QuizResult({
    required this.attemptId,
    required this.score,
    required this.maxScore,
    required this.percent,
    required this.correctCount,
    required this.questionCount,
    required this.countsForRanking,
    required this.isCelebration,
    required this.quizTitle,
    required this.questions,
  });

  final int attemptId;
  final int score;
  final int maxScore;
  final int percent;
  final int correctCount;
  final int questionCount;
  final bool countsForRanking;
  final bool isCelebration;
  final String quizTitle;
  final List<QuizResultQuestion> questions;

  factory QuizResult.fromJson(Map<String, dynamic> j) {
    final attempt = j['attempt'] as Map<String, dynamic>? ?? const {};
    final quiz = j['quiz'] as Map<String, dynamic>? ?? const {};
    return QuizResult(
      attemptId: _int(attempt['id']),
      score: _int(attempt['score']),
      maxScore: _int(attempt['max_score']),
      percent: _int(attempt['percent']),
      correctCount: _int(attempt['correct_count']),
      questionCount: _int(attempt['question_count']),
      countsForRanking: _bool(attempt['counts_for_ranking']),
      isCelebration: _bool(attempt['is_celebration']),
      quizTitle: _str(quiz['title']),
      questions: _list(j['questions'], QuizResultQuestion.fromJson),
    );
  }
}

class QuizListItem {
  const QuizListItem({
    required this.id,
    required this.title,
    required this.type,
    required this.subjectName,
    required this.chapterLabel,
    required this.questionCount,
    required this.attempted,
    required this.percent,
  });

  final int id;
  final String title;
  final String type; // 'interactive' | 'file'
  final String? subjectName;
  final String? chapterLabel;
  final int questionCount;
  final bool attempted;
  final int? percent; // ranked score, when attempted

  bool get isFile => type == 'file';

  factory QuizListItem.fromJson(Map<String, dynamic> j) => QuizListItem(
    id: _int(j['id']),
    title: _str(j['title']),
    type: _str(j['type']),
    subjectName: _strOrNull(j['subject_name']),
    chapterLabel: _strOrNull(j['chapter_label']),
    questionCount: _int(j['question_count']),
    attempted: _bool(j['attempted']),
    percent: _intOrNull(j['percent']),
  );
}

double _double(Object? v) =>
    v is num ? v.toDouble() : double.tryParse('$v') ?? 0;

// --- Ranking (leaderboard, scoped to the student's Tahun) ---

class RankRow {
  const RankRow({
    required this.rank,
    required this.name,
    required this.points,
    required this.accuracy,
    required this.quizzes,
    required this.isMe,
  });

  final int rank;
  final String name;
  final int points;
  final double accuracy;
  final int quizzes;
  final bool isMe;

  factory RankRow.fromJson(Map<String, dynamic> j) => RankRow(
    rank: _int(j['rank']),
    name: _str(j['name']),
    points: _int(j['points']),
    accuracy: _double(j['accuracy']),
    quizzes: _int(j['quizzes']),
    isMe: _bool(j['is_me']),
  );
}

class RankingData {
  const RankingData({
    required this.gradeName,
    required this.top,
    required this.myRow,
    required this.showMyRow,
  });

  final String? gradeName;
  final List<RankRow> top;
  final RankRow? myRow;
  final bool showMyRow;

  factory RankingData.fromJson(Map<String, dynamic> j) {
    final grade = j['grade'] as Map<String, dynamic>?;
    return RankingData(
      gradeName: grade == null ? null : _strOrNull(grade['name']),
      top: _list(j['top'], RankRow.fromJson),
      myRow: j['my_row'] == null
          ? null
          : RankRow.fromJson(j['my_row'] as Map<String, dynamic>),
      showMyRow: _bool(j['show_my_row']),
    );
  }
}
