import 'dart:ui' show Color;

/// Data models for the student learning surface. Each mirrors a JSON payload from the
/// `/api/student/*` endpoints. Kept plain (fromJson only) to match the existing app style.

int _int(Object? v) => v is int ? v : int.tryParse('$v') ?? 0;
int? _intOrNull(Object? v) => v == null ? null : (v is int ? v : int.tryParse('$v'));
String _str(Object? v) => v?.toString() ?? '';
String? _strOrNull(Object? v) => v?.toString();
bool _bool(Object? v) => v == true;

class GradeInfo {
  const GradeInfo({required this.id, required this.level, required this.name});

  final int id;
  final int level;
  final String name;

  factory GradeInfo.fromJson(Map<String, dynamic> j) =>
      GradeInfo(id: _int(j['id']), level: _int(j['level']), name: _str(j['name']));
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
    percent: _int(j['percent']),
    completed: _bool(j['completed']),
    favourited: _bool(j['favourited']),
    watched: _bool(j['watched']),
  );
}

class DashboardData {
  const DashboardData({
    required this.grade,
    required this.points,
    required this.rank,
    required this.continueWatching,
    required this.newest,
    required this.subjects,
  });

  final GradeInfo? grade;
  final int points;
  final int? rank;
  final List<LessonCard> continueWatching;
  final List<LessonCard> newest;
  final List<SubjectCard> subjects;

  factory DashboardData.fromJson(Map<String, dynamic> j) => DashboardData(
    grade: j['grade'] == null ? null : GradeInfo.fromJson(j['grade'] as Map<String, dynamic>),
    points: _int(j['points']),
    rank: _intOrNull(j['rank']),
    continueWatching: _list(j['continue_watching'], LessonCard.fromJson),
    newest: _list(j['newest'], LessonCard.fromJson),
    subjects: _list(j['subjects'], SubjectCard.fromJson),
  );
}

class SubjectCategoryGroup {
  const SubjectCategoryGroup({required this.key, required this.label, required this.subjects});

  final String key;
  final String label;
  final List<SubjectCard> subjects;

  factory SubjectCategoryGroup.fromJson(Map<String, dynamic> j) => SubjectCategoryGroup(
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
    grade: j['grade'] == null ? null : GradeInfo.fromJson(j['grade'] as Map<String, dynamic>),
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
  const SubjectChaptersData({required this.subject, required this.grade, required this.chapters});

  final SubjectCard subject;
  final GradeInfo grade;
  final List<ChapterListItem> chapters;

  factory SubjectChaptersData.fromJson(Map<String, dynamic> j) => SubjectChaptersData(
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
  const LessonProgressInfo({required this.positionSeconds, required this.percent, required this.completed});

  final int positionSeconds;
  final int percent;
  final bool completed;

  factory LessonProgressInfo.fromJson(Map<String, dynamic> j) => LessonProgressInfo(
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
      progress: j['progress'] == null ? null : LessonProgressInfo.fromJson(j['progress'] as Map<String, dynamic>),
      favourited: _bool(j['favourited']),
      previous: j['previous'] == null ? null : LessonNeighbour.fromJson(j['previous'] as Map<String, dynamic>),
      next: j['next'] == null ? null : LessonNeighbour.fromJson(j['next'] as Map<String, dynamic>),
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
