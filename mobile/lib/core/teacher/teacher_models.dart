// Data models for the teacher mobile surface (/api/teacher/*).

int _int(Object? v) => v is int ? v : int.tryParse('$v') ?? 0;
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

// --- Content Hub ---

class TeacherVideo {
  const TeacherVideo({
    required this.id,
    required this.title,
    required this.chapterLabel,
    required this.subjectName,
    required this.gradeName,
    required this.published,
    required this.views,
    required this.source,
    required this.ownership,
    required this.thumbnailUrl,
  });

  final int id;
  final String title;
  final String? chapterLabel;
  final String? subjectName;
  final String? gradeName;
  final bool published;
  final int views;
  final String source; // 'upload' | 'youtube'
  final String ownership; // 'upload' | 'owned' | 'reference'
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
    chapterLabel: _strOrNull(j['chapter_label']),
    subjectName: _strOrNull(j['subject_name']),
    gradeName: _strOrNull(j['grade_name']),
    published: _bool(j['published']),
    views: _int(j['views']),
    source: _str(j['source']),
    ownership: _str(j['ownership']),
    thumbnailUrl: _strOrNull(j['thumbnail_url']),
  );
}

class TeacherMaterial {
  const TeacherMaterial({
    required this.id,
    required this.title,
    required this.extension,
    required this.humanSize,
  });

  final int id;
  final String title;
  final String extension;
  final String humanSize;

  factory TeacherMaterial.fromJson(Map<String, dynamic> j) => TeacherMaterial(
    id: _int(j['id']),
    title: _str(j['title']),
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
