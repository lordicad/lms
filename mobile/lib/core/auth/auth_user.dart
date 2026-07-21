enum UserRole { student, teacher, admin }

class Grade {
  const Grade({required this.id, required this.level, required this.name});

  final int id;
  final int level;
  final String name;

  factory Grade.fromJson(Map<String, dynamic> json) {
    return Grade(
      id: json['id'] as int,
      level: json['level'] as int,
      name: json['name'] as String,
    );
  }
}

class SchoolInfo {
  const SchoolInfo({required this.id, required this.name});

  final int id;
  final String name;

  factory SchoolInfo.fromJson(Map<String, dynamic> json) =>
      SchoolInfo(id: _asInt(json['id']), name: json['name']?.toString() ?? '');
}

class SchoolClassInfo {
  const SchoolClassInfo({
    required this.id,
    required this.schoolId,
    required this.gradeId,
    required this.label,
    this.homeroomTeacherName,
  });

  final int id;
  final int schoolId;
  final int gradeId;
  final String label;
  final String? homeroomTeacherName;

  factory SchoolClassInfo.fromJson(Map<String, dynamic> json) =>
      SchoolClassInfo(
        id: _asInt(json['id']),
        schoolId: _asInt(json['school_id']),
        gradeId: _asInt(json['grade_id']),
        label: json['label']?.toString() ?? '',
        homeroomTeacherName: json['homeroom_teacher_name']?.toString(),
      );
}

class SubjectInfo {
  const SubjectInfo({required this.id, required this.name});

  final int id;
  final String name;

  factory SubjectInfo.fromJson(Map<String, dynamic> json) =>
      SubjectInfo(id: _asInt(json['id']), name: json['name']?.toString() ?? '');
}

class ProfileOptions {
  const ProfileOptions({
    required this.schools,
    required this.grades,
    required this.classes,
    required this.subjects,
  });

  final List<SchoolInfo> schools;
  final List<Grade> grades;
  final List<SchoolClassInfo> classes;
  final List<SubjectInfo> subjects;

  factory ProfileOptions.fromJson(Map<String, dynamic> json) => ProfileOptions(
    schools: _items(json['schools'], SchoolInfo.fromJson),
    grades: _items(json['grades'], Grade.fromJson),
    classes: _items(json['classes'], SchoolClassInfo.fromJson),
    subjects: _items(json['subjects'], SubjectInfo.fromJson),
  );
}

class ProfileUpdate {
  const ProfileUpdate({
    required this.name,
    required this.username,
    this.email,
    this.schoolId,
    this.gradeLevel,
    this.schoolClassId,
    this.guardianName,
    this.guardianPhone,
    this.guardianEmail,
    this.phone,
    this.position,
    this.homeroomClassId,
    this.subjectIds = const [],
  });

  final String name;
  final String username;
  final String? email;
  final int? schoolId;
  final int? gradeLevel;
  final int? schoolClassId;
  final String? guardianName;
  final String? guardianPhone;
  final String? guardianEmail;
  final String? phone;
  final String? position;
  final int? homeroomClassId;
  final List<int> subjectIds;

  Map<String, dynamic> toJson(UserRole role) {
    final data = <String, dynamic>{
      'name': name,
      'username': username,
      'email': email,
    };
    if (role == UserRole.student) {
      data.addAll({
        'school_id': schoolId,
        'grade_level': gradeLevel,
        'school_class_id': schoolClassId,
        'guardian_name': guardianName,
        'guardian_phone': guardianPhone,
        'guardian_email': guardianEmail,
      });
    } else if (role == UserRole.teacher) {
      data.addAll({
        'phone': phone,
        'position': position,
        'school_id': schoolId,
        'homeroom_class_id': homeroomClassId,
        'subjects': subjectIds,
      });
    }
    return data;
  }
}

class AuthUser {
  const AuthUser({
    required this.id,
    required this.name,
    required this.username,
    required this.role,
    this.email,
    this.avatarUrl,
    this.grade,
    this.school,
    this.schoolClass,
    this.guardianName,
    this.guardianPhone,
    this.guardianEmail,
    this.phone,
    this.position,
    this.subjects = const [],
    this.homeroomClass,
  });

  final int id;
  final String name;
  final String username;
  final String? email;
  final String? avatarUrl;
  final UserRole role;
  final Grade? grade;
  final SchoolInfo? school;
  final SchoolClassInfo? schoolClass;
  final String? guardianName;
  final String? guardianPhone;
  final String? guardianEmail;
  final String? phone;
  final String? position;
  final List<SubjectInfo> subjects;
  final SchoolClassInfo? homeroomClass;

  AuthUser copyWith({
    String? name,
    String? username,
    String? email,
    String? avatarUrl,
  }) {
    return AuthUser(
      id: id,
      name: name ?? this.name,
      username: username ?? this.username,
      email: email ?? this.email,
      avatarUrl: avatarUrl ?? this.avatarUrl,
      role: role,
      grade: grade,
      school: school,
      schoolClass: schoolClass,
      guardianName: guardianName,
      guardianPhone: guardianPhone,
      guardianEmail: guardianEmail,
      phone: phone,
      position: position,
      subjects: subjects,
      homeroomClass: homeroomClass,
    );
  }

  factory AuthUser.fromJson(Map<String, dynamic> json) {
    final rawRole = json['role'] as String;

    return AuthUser(
      id: json['id'] as int,
      name: json['name'] as String,
      username: json['username'] as String,
      email: json['email'] as String?,
      avatarUrl: json['avatar_url'] as String?,
      role: switch (rawRole) {
        'student' => UserRole.student,
        'teacher' => UserRole.teacher,
        'admin' => UserRole.admin,
        _ => throw FormatException('Peranan akaun tidak dikenali.'),
      },
      grade: json['grade'] == null
          ? null
          : Grade.fromJson(json['grade'] as Map<String, dynamic>),
      school: _map(json['school'], SchoolInfo.fromJson),
      schoolClass: _map(json['school_class'], SchoolClassInfo.fromJson),
      guardianName: json['guardian_name'] as String?,
      guardianPhone: json['guardian_phone'] as String?,
      guardianEmail: json['guardian_email'] as String?,
      phone: json['phone'] as String?,
      position: json['position'] as String?,
      subjects: _items(json['subjects'], SubjectInfo.fromJson),
      homeroomClass: _map(json['homeroom_class'], SchoolClassInfo.fromJson),
    );
  }
}

T? _map<T>(Object? value, T Function(Map<String, dynamic>) fromJson) {
  if (value is! Map<String, dynamic>) return null;
  return fromJson(value);
}

List<T> _items<T>(Object? value, T Function(Map<String, dynamic>) fromJson) {
  if (value is! List) return const [];
  return value
      .whereType<Map<String, dynamic>>()
      .map(fromJson)
      .toList(growable: false);
}

int _asInt(Object? value) => switch (value) {
  int number => number,
  num number => number.toInt(),
  String text => int.tryParse(text) ?? 0,
  _ => 0,
};
