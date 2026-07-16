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

class AuthUser {
  const AuthUser({
    required this.id,
    required this.name,
    required this.username,
    required this.role,
    this.email,
    this.grade,
  });

  final int id;
  final String name;
  final String username;
  final String? email;
  final UserRole role;
  final Grade? grade;

  factory AuthUser.fromJson(Map<String, dynamic> json) {
    final rawRole = json['role'] as String;

    return AuthUser(
      id: json['id'] as int,
      name: json['name'] as String,
      username: json['username'] as String,
      email: json['email'] as String?,
      role: switch (rawRole) {
        'student' => UserRole.student,
        'teacher' => UserRole.teacher,
        'admin' => UserRole.admin,
        _ => throw FormatException('Peranan akaun tidak dikenali.'),
      },
      grade: json['grade'] == null
          ? null
          : Grade.fromJson(json['grade'] as Map<String, dynamic>),
    );
  }
}
