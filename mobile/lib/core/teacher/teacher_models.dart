// Data models for the teacher mobile surface (/api/teacher/*).

int _int(Object? v) => v is int ? v : int.tryParse('$v') ?? 0;
double _double(Object? v) =>
    v is num ? v.toDouble() : double.tryParse('$v') ?? 0;
String? _strOrNull(Object? v) => v?.toString();
String _str(Object? v) => v?.toString() ?? '';
bool _bool(Object? v) => v == true;

List<T> _mapList<T>(Object? raw, T Function(Map<String, dynamic>) fromJson) {
  if (raw is! List) return const [];
  return raw
      .whereType<Map<String, dynamic>>()
      .map(fromJson)
      .toList(growable: false);
}

class TeacherStats {
  const TeacherStats({
    required this.videos,
    required this.materials,
    required this.quizzes,
    required this.attempts,
    required this.views,
  });

  final int videos;
  final int materials;
  final int quizzes;
  final int attempts;
  final int views;

  factory TeacherStats.fromJson(Map<String, dynamic> j) => TeacherStats(
    videos: _int(j['videos']),
    materials: _int(j['materials']),
    quizzes: _int(j['quizzes']),
    attempts: _int(j['attempts']),
    views: _int(j['views']),
  );
}

class RecentAttempt {
  const RecentAttempt({
    required this.studentName,
    required this.gradeName,
    required this.quizTitle,
    required this.subjectName,
    required this.percent,
  });

  final String? studentName;
  final String? gradeName;
  final String? quizTitle;
  final String? subjectName;
  final int percent;

  factory RecentAttempt.fromJson(Map<String, dynamic> j) => RecentAttempt(
    studentName: _strOrNull(j['student_name']),
    gradeName: _strOrNull(j['grade_name']),
    quizTitle: _strOrNull(j['quiz_title']),
    subjectName: _strOrNull(j['subject_name']),
    percent: _int(j['percent']),
  );
}

class TeacherDashboardData {
  const TeacherDashboardData({
    required this.stats,
    required this.recentAttempts,
  });

  final TeacherStats stats;
  final List<RecentAttempt> recentAttempts;

  factory TeacherDashboardData.fromJson(Map<String, dynamic> j) =>
      TeacherDashboardData(
        stats: TeacherStats.fromJson(
          (j['stats'] as Map<String, dynamic>?) ?? const {},
        ),
        recentAttempts: _mapList(j['recent_attempts'], RecentAttempt.fromJson),
      );
}

class TeacherNotificationsData {
  const TeacherNotificationsData({
    required this.unreadCount,
    required this.notifications,
  });

  final int unreadCount;
  final List<TeacherNotificationItem> notifications;

  factory TeacherNotificationsData.fromJson(Map<String, dynamic> j) =>
      TeacherNotificationsData(
        unreadCount: _int(j['unread_count']),
        notifications: _mapList(
          j['notifications'],
          TeacherNotificationItem.fromJson,
        ),
      );
}

/// Full student leaderboard, filtered by the same Tahun/Subjek/Kuiz controls
/// available on the web Cikgu page.
class TeacherRankingData {
  const TeacherRankingData({required this.filters, required this.rows});

  final TeacherRankingFilters filters;
  final List<TeacherRankingRow> rows;

  factory TeacherRankingData.fromJson(Map<String, dynamic> j) =>
      TeacherRankingData(
        filters: TeacherRankingFilters.fromJson(
          (j['filters'] as Map<String, dynamic>?) ?? const {},
        ),
        rows: _mapList(j['rows'], TeacherRankingRow.fromJson),
      );
}

class TeacherRankingFilters {
  const TeacherRankingFilters({
    required this.grades,
    required this.subjects,
    required this.quizzes,
  });

  final List<OptionItem> grades;
  final List<OptionItem> subjects;
  final List<TeacherRankingQuiz> quizzes;

  factory TeacherRankingFilters.fromJson(Map<String, dynamic> j) =>
      TeacherRankingFilters(
        grades: _mapList(j['grades'], OptionItem.fromJson),
        subjects: _mapList(j['subjects'], OptionItem.fromJson),
        quizzes: _mapList(j['quizzes'], TeacherRankingQuiz.fromJson),
      );
}

class TeacherRankingQuiz {
  const TeacherRankingQuiz({required this.id, required this.title});

  final int id;
  final String title;

  factory TeacherRankingQuiz.fromJson(Map<String, dynamic> j) =>
      TeacherRankingQuiz(id: _int(j['id']), title: _str(j['title']));
}

class TeacherRankingRow {
  const TeacherRankingRow({
    required this.rank,
    required this.studentName,
    required this.initials,
    required this.gradeName,
    required this.points,
    required this.correct,
    required this.questions,
    required this.accuracy,
    required this.quizzes,
  });

  final int rank;
  final String studentName;
  final String initials;
  final String? gradeName;
  final int points;
  final int correct;
  final int questions;
  final double accuracy;
  final int quizzes;

  factory TeacherRankingRow.fromJson(Map<String, dynamic> j) =>
      TeacherRankingRow(
        rank: _int(j['rank']),
        studentName: _str(j['student_name']),
        initials: _str(j['initials']),
        gradeName: _strOrNull(j['grade_name']),
        points: _int(j['points']),
        correct: _int(j['correct']),
        questions: _int(j['questions']),
        accuracy: _double(j['accuracy']),
        quizzes: _int(j['quizzes']),
      );
}

class TeacherNotificationItem {
  const TeacherNotificationItem({
    required this.id,
    required this.type,
    required this.actorName,
    required this.title,
    required this.read,
    required this.createdAt,
  });

  final int id;
  final String type;
  final String? actorName;
  final String title;
  final bool read;
  final DateTime? createdAt;

  factory TeacherNotificationItem.fromJson(Map<String, dynamic> j) =>
      TeacherNotificationItem(
        id: _int(j['id']),
        type: _str(j['type']),
        actorName: _strOrNull(j['actor_name']),
        title: _str(j['title']),
        read: _bool(j['read']),
        createdAt: DateTime.tryParse(_str(j['created_at']))?.toLocal(),
      );
}

class TeacherTalentData {
  const TeacherTalentData({
    required this.signal,
    required this.stats,
    required this.leaderboards,
  });

  final TeacherTalentSignal signal;
  final TeacherTalentStats stats;
  final List<TeacherTalentLeaderboard> leaderboards;

  factory TeacherTalentData.fromJson(Map<String, dynamic> j) =>
      TeacherTalentData(
        signal: TeacherTalentSignal.fromJson(
          (j['signal'] as Map<String, dynamic>?) ?? const {},
        ),
        stats: TeacherTalentStats.fromJson(
          (j['stats'] as Map<String, dynamic>?) ?? const {},
        ),
        leaderboards: _mapList(
          j['leaderboards'],
          TeacherTalentLeaderboard.fromJson,
        ),
      );
}

class TeacherTalentSignal {
  const TeacherTalentSignal({
    required this.headline,
    required this.sufficient,
    required this.engagedStudents,
    required this.engagement,
    required this.quality,
    required this.breadth,
    required this.outcome,
  });

  final double? headline;
  final bool sufficient;
  final int engagedStudents;
  final double engagement;
  final double quality;
  final int breadth;
  final double? outcome;

  factory TeacherTalentSignal.fromJson(Map<String, dynamic> j) =>
      TeacherTalentSignal(
        headline: j['headline'] == null ? null : _double(j['headline']),
        sufficient: _bool(j['sufficient']),
        engagedStudents: _int(j['engaged_students']),
        engagement: _double(j['engagement']),
        quality: _double(j['quality']),
        breadth: _int(j['breadth']),
        outcome: j['outcome'] == null ? null : _double(j['outcome']),
      );
}

class TeacherTalentStats {
  const TeacherTalentStats({
    required this.views,
    required this.favourites,
    required this.downloads,
    required this.attempts,
  });

  final int views;
  final int favourites;
  final int downloads;
  final int attempts;

  factory TeacherTalentStats.fromJson(Map<String, dynamic> j) =>
      TeacherTalentStats(
        views: _int(j['views']),
        favourites: _int(j['favourites']),
        downloads: _int(j['downloads']),
        attempts: _int(j['attempts']),
      );
}

class TeacherTalentLeaderboard {
  const TeacherTalentLeaderboard({
    required this.kind,
    required this.title,
    required this.items,
  });

  final String kind;
  final String title;
  final List<TeacherTalentItem> items;

  factory TeacherTalentLeaderboard.fromJson(Map<String, dynamic> j) =>
      TeacherTalentLeaderboard(
        kind: _str(j['kind']),
        title: _str(j['title']),
        items: _mapList(j['items'], TeacherTalentItem.fromJson),
      );
}

class TeacherTalentItem {
  const TeacherTalentItem({
    required this.title,
    required this.subjectName,
    required this.chapterLabel,
    required this.value,
  });

  final String title;
  final String? subjectName;
  final String? chapterLabel;
  final int value;

  factory TeacherTalentItem.fromJson(Map<String, dynamic> j) =>
      TeacherTalentItem(
        title: _str(j['title']),
        subjectName: _strOrNull(j['subject_name']),
        chapterLabel: _strOrNull(j['chapter_label']),
        value: _int(j['value']),
      );
}

// --- Content Hub ---

class TeacherVideo {
  const TeacherVideo({
    required this.id,
    required this.title,
    required this.description,
    required this.chapterId,
    required this.subjectId,
    required this.gradeId,
    required this.chapterLabel,
    required this.subjectName,
    required this.gradeName,
    required this.published,
    required this.views,
    required this.source,
    required this.ownership,
    required this.youtubeUrl,
    required this.thumbnailUrl,
  });

  final int id;
  final String title;
  final String? description;
  final int? chapterId;
  final int? subjectId;
  final int? gradeId;
  final String? chapterLabel;
  final String? subjectName;
  final String? gradeName;
  final bool published;
  final int views;
  final String source; // 'upload' | 'youtube'
  final String ownership; // 'upload' | 'owned' | 'reference'
  final String? youtubeUrl;
  final String? thumbnailUrl;

  /// Human label for the ownership attribution shown on the card.
  String get ownershipLabel => switch (ownership) {
    'owned' => 'YouTube — Anda',
    'reference' => 'YouTube — Rujukan',
    _ => 'Muat naik',
  };

  factory TeacherVideo.fromJson(Map<String, dynamic> j) => TeacherVideo(
    id: _int(j['id']),
    title: _str(j['title']),
    description: _strOrNull(j['description']),
    chapterId: j['chapter_id'] == null ? null : _int(j['chapter_id']),
    subjectId: j['subject_id'] == null ? null : _int(j['subject_id']),
    gradeId: j['grade_id'] == null ? null : _int(j['grade_id']),
    chapterLabel: _strOrNull(j['chapter_label']),
    subjectName: _strOrNull(j['subject_name']),
    gradeName: _strOrNull(j['grade_name']),
    published: _bool(j['published']),
    views: _int(j['views']),
    source: _str(j['source']),
    ownership: _str(j['ownership']),
    youtubeUrl: _strOrNull(j['youtube_url']),
    thumbnailUrl: _strOrNull(j['thumbnail_url']),
  );
}

class TeacherMaterial {
  const TeacherMaterial({
    required this.id,
    required this.title,
    required this.chapterId,
    required this.subjectId,
    required this.gradeId,
    required this.chapterLabel,
    required this.subjectName,
    required this.extension,
    required this.humanSize,
  });

  final int id;
  final String title;
  final int? chapterId;
  final int? subjectId;
  final int? gradeId;
  final String? chapterLabel;
  final String? subjectName;
  final String extension;
  final String humanSize;

  factory TeacherMaterial.fromJson(Map<String, dynamic> j) => TeacherMaterial(
    id: _int(j['id']),
    title: _str(j['title']),
    chapterId: j['chapter_id'] == null ? null : _int(j['chapter_id']),
    subjectId: j['subject_id'] == null ? null : _int(j['subject_id']),
    gradeId: j['grade_id'] == null ? null : _int(j['grade_id']),
    chapterLabel: _strOrNull(j['chapter_label']),
    subjectName: _strOrNull(j['subject_name']),
    extension: _str(j['extension']),
    humanSize: _str(j['human_size']),
  );
}

class TeacherQuiz {
  const TeacherQuiz({
    required this.id,
    required this.title,
    required this.type,
    required this.chapterLabel,
    required this.subjectName,
    required this.published,
    required this.questionCount,
    required this.attempts,
  });

  final int id;
  final String title;
  final String type; // 'interactive' | 'file'
  final String? chapterLabel;
  final String? subjectName;
  final bool published;
  final int questionCount;
  final int attempts;

  bool get isFile => type == 'file';

  factory TeacherQuiz.fromJson(Map<String, dynamic> j) => TeacherQuiz(
    id: _int(j['id']),
    title: _str(j['title']),
    type: _str(j['type']),
    chapterLabel: _strOrNull(j['chapter_label']),
    subjectName: _strOrNull(j['subject_name']),
    published: _bool(j['published']),
    questionCount: _int(j['question_count']),
    attempts: _int(j['attempts']),
  );
}

/// An interactive quiz as loaded by the editor. Kept separate from [TeacherQuiz]
/// so the Content Hub stays lightweight and only fetches questions when needed.
class TeacherQuizDetail {
  const TeacherQuizDetail({
    required this.id,
    required this.title,
    required this.description,
    required this.chapterId,
    required this.subjectId,
    required this.gradeId,
    required this.durationMinutes,
    required this.published,
    required this.attempts,
    required this.questions,
  });

  final int id;
  final String title;
  final String? description;
  final int? chapterId;
  final int? subjectId;
  final int? gradeId;
  final int? durationMinutes;
  final bool published;
  final int attempts;
  final List<TeacherQuizQuestionDraft> questions;

  factory TeacherQuizDetail.fromJson(Map<String, dynamic> j) =>
      TeacherQuizDetail(
        id: _int(j['id']),
        title: _str(j['title']),
        description: _strOrNull(j['description']),
        chapterId: j['chapter_id'] == null ? null : _int(j['chapter_id']),
        subjectId: j['subject_id'] == null ? null : _int(j['subject_id']),
        gradeId: j['grade_id'] == null ? null : _int(j['grade_id']),
        durationMinutes: j['duration_minutes'] == null
            ? null
            : _int(j['duration_minutes']),
        published: _bool(j['published']),
        attempts: _int(j['attempts']),
        questions: _mapList(j['questions'], TeacherQuizQuestionDraft.fromJson),
      );
}

class TeacherQuizStats {
  const TeacherQuizStats({
    required this.completedCount,
    required this.maxScore,
    required this.averageScore,
    required this.averagePercent,
    required this.questions,
    required this.attempts,
  });

  final int completedCount;
  final int maxScore;
  final double averageScore;
  final int averagePercent;
  final List<TeacherQuizQuestionStat> questions;
  final List<TeacherQuizAttempt> attempts;

  factory TeacherQuizStats.fromJson(Map<String, dynamic> j) => TeacherQuizStats(
    completedCount: _int(j['completed_count']),
    maxScore: _int(j['max_score']),
    averageScore: _double(j['average_score']),
    averagePercent: _int(j['average_percent']),
    questions: _mapList(j['questions'], TeacherQuizQuestionStat.fromJson),
    attempts: _mapList(j['attempts'], TeacherQuizAttempt.fromJson),
  );
}

class TeacherQuizQuestionStat {
  const TeacherQuizQuestionStat({
    required this.number,
    required this.questionText,
    required this.answered,
    required this.correct,
    required this.rate,
  });

  final int number;
  final String questionText;
  final int answered;
  final int correct;
  final int rate;

  factory TeacherQuizQuestionStat.fromJson(Map<String, dynamic> j) =>
      TeacherQuizQuestionStat(
        number: _int(j['number']),
        questionText: _str(j['question_text']),
        answered: _int(j['answered']),
        correct: _int(j['correct']),
        rate: _int(j['rate']),
      );
}

class TeacherQuizAttempt {
  const TeacherQuizAttempt({
    required this.studentName,
    required this.gradeName,
    required this.score,
    required this.maxScore,
    required this.percent,
    required this.correctCount,
    required this.questionCount,
    required this.duration,
    required this.countsForRanking,
    required this.completedAt,
  });

  final String? studentName;
  final String? gradeName;
  final int score;
  final int maxScore;
  final int percent;
  final int correctCount;
  final int questionCount;
  final String duration;
  final bool countsForRanking;
  final DateTime? completedAt;

  factory TeacherQuizAttempt.fromJson(Map<String, dynamic> j) =>
      TeacherQuizAttempt(
        studentName: _strOrNull(j['student_name']),
        gradeName: _strOrNull(j['grade_name']),
        score: _int(j['score']),
        maxScore: _int(j['max_score']),
        percent: _int(j['percent']),
        correctCount: _int(j['correct_count']),
        questionCount: _int(j['question_count']),
        duration: _str(j['duration']),
        countsForRanking: _bool(j['counts_for_ranking']),
        completedAt: DateTime.tryParse(_str(j['completed_at']))?.toLocal(),
      );
}

/// A complete question is saved together with its quiz so the server can make the
/// whole builder transactional. These are draft-only models; created questions
/// are returned through [TeacherQuiz] in the Content Hub.
class TeacherQuizQuestionDraft {
  const TeacherQuizQuestionDraft({
    required this.questionText,
    required this.questionType,
    required this.points,
    required this.options,
  });

  final String questionText;
  final String questionType; // 'single' | 'multiple'
  final int points;
  final List<TeacherQuizOptionDraft> options;

  factory TeacherQuizQuestionDraft.fromJson(Map<String, dynamic> j) =>
      TeacherQuizQuestionDraft(
        questionText: _str(j['question_text']),
        questionType: _str(j['question_type']),
        points: _int(j['points']),
        options: _mapList(j['options'], TeacherQuizOptionDraft.fromJson),
      );

  Map<String, dynamic> toJson() => {
    'question_text': questionText,
    'question_type': questionType,
    'points': points,
    'options': options.map((option) => option.toJson()).toList(growable: false),
  };
}

class TeacherQuizOptionDraft {
  const TeacherQuizOptionDraft({
    required this.optionText,
    required this.isCorrect,
  });

  final String optionText;
  final bool isCorrect;

  factory TeacherQuizOptionDraft.fromJson(Map<String, dynamic> j) =>
      TeacherQuizOptionDraft(
        optionText: _str(j['option_text']),
        isCorrect: _bool(j['is_correct']),
      );

  Map<String, dynamic> toJson() => {
    'option_text': optionText,
    'is_correct': isCorrect,
  };
}

// --- Bab management + picker options ---

class OptionItem {
  const OptionItem({required this.id, required this.name, this.level});

  final int id;
  final String name;
  final int? level; // grades only

  factory OptionItem.fromJson(Map<String, dynamic> j) => OptionItem(
    id: _int(j['id']),
    name: _str(j['name']),
    level: j['level'] == null ? null : _int(j['level']),
  );
}

class TeacherOptions {
  const TeacherOptions({required this.subjects, required this.grades});

  final List<OptionItem> subjects;
  final List<OptionItem> grades;

  factory TeacherOptions.fromJson(Map<String, dynamic> j) => TeacherOptions(
    subjects: _mapList(j['subjects'], OptionItem.fromJson),
    grades: _mapList(j['grades'], OptionItem.fromJson),
  );
}

class TeacherChapter {
  const TeacherChapter({
    required this.id,
    required this.number,
    required this.title,
    required this.lessonsCount,
    required this.materialsCount,
    required this.quizzesCount,
    required this.isEmpty,
  });

  final int id;
  final int number;
  final String title;
  final int lessonsCount;
  final int materialsCount;
  final int quizzesCount;
  final bool isEmpty;

  factory TeacherChapter.fromJson(Map<String, dynamic> j) => TeacherChapter(
    id: _int(j['id']),
    number: _int(j['number']),
    title: _str(j['title']),
    lessonsCount: _int(j['lessons_count']),
    materialsCount: _int(j['materials_count']),
    quizzesCount: _int(j['quizzes_count']),
    isEmpty: _bool(j['is_empty']),
  );
}

class ChaptersData {
  const ChaptersData({required this.isOffered, required this.chapters});

  final bool isOffered;
  final List<TeacherChapter> chapters;

  factory ChaptersData.fromJson(Map<String, dynamic> j) => ChaptersData(
    isOffered: _bool(j['is_offered']),
    chapters: _mapList(j['chapters'], TeacherChapter.fromJson),
  );
}
