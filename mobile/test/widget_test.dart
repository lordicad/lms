import 'package:flutter_test/flutter_test.dart';
import 'package:lms_moe_mobile/core/auth/auth_repository.dart';
import 'package:lms_moe_mobile/core/auth/auth_user.dart';
import 'package:lms_moe_mobile/features/auth/login_screen.dart';
import 'package:lms_moe_mobile/core/teacher/teacher_models.dart';
import 'package:lms_moe_mobile/core/teacher/teacher_repository.dart';
import 'package:lms_moe_mobile/core/theme/lms_theme.dart';
import 'package:lms_moe_mobile/features/teacher/quiz_builder_screen.dart';
import 'package:flutter/material.dart';

class _QuizBuilderRepository extends TeacherRepository {
  @override
  Future<TeacherOptions> options() async => const TeacherOptions(
    subjects: [OptionItem(id: 1, name: 'Matematik')],
    grades: [OptionItem(id: 4, name: 'Tahun 4', level: 4)],
  );

  @override
  Future<ChaptersData> chapters(int subjectId, int gradeId) async =>
      const ChaptersData(
        isOffered: true,
        chapters: [
          TeacherChapter(
            id: 12,
            number: 1,
            title: 'Nombor Bulat',
            lessonsCount: 0,
            materialsCount: 0,
            quizzesCount: 0,
            isEmpty: true,
          ),
        ],
      );
}

void main() {
  testWidgets('login screen shows credentials fields', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: buildLmsTheme(),
        home: LoginScreen(
          auth: AuthRepository(),
          onSignedIn: (AuthUser user) {},
        ),
      ),
    );

    expect(find.text('Log Masuk'), findsNWidgets(2));
    expect(find.text('E-mel log masuk'), findsOneWidget);
    expect(find.text('Kata laluan'), findsOneWidget);
  });

  testWidgets('quiz builder loads an offered chapter and validates the title', (
    tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: buildLmsTheme(),
        home: QuizBuilderScreen(repository: _QuizBuilderRepository()),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Cipta Kuiz Interaktif'), findsOneWidget);
    expect(find.text('Bab 1: Nombor Bulat'), findsOneWidget);

    await tester.tap(find.text('Simpan kuiz'));
    await tester.pump();

    expect(find.text('Sila isi tajuk kuiz.'), findsOneWidget);
  });
}
