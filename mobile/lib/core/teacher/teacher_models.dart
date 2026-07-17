/// Data models for the teacher mobile surface (/api/teacher/*).

int _int(Object? v) => v is int ? v : int.tryParse('$v') ?? 0;
String? _strOrNull(Object? v) => v?.toString();

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
  const TeacherDashboardData({required this.stats, required this.recentAttempts});

  final TeacherStats stats;
  final List<RecentAttempt> recentAttempts;

  factory TeacherDashboardData.fromJson(Map<String, dynamic> j) => TeacherDashboardData(
    stats: TeacherStats.fromJson((j['stats'] as Map<String, dynamic>?) ?? const {}),
    recentAttempts: (j['recent_attempts'] as List?)
            ?.whereType<Map<String, dynamic>>()
            .map(RecentAttempt.fromJson)
            .toList(growable: false) ??
        const [],
  );
}
