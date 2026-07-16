import 'package:flutter_test/flutter_test.dart';
import 'package:lms_moe_mobile/core/auth/auth_repository.dart';
import 'package:lms_moe_mobile/core/auth/auth_user.dart';
import 'package:lms_moe_mobile/features/auth/login_screen.dart';
import 'package:lms_moe_mobile/core/theme/lms_theme.dart';
import 'package:flutter/material.dart';

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
    expect(find.text('Nama pengguna atau emel'), findsOneWidget);
    expect(find.text('Kata laluan'), findsOneWidget);
  });
}
