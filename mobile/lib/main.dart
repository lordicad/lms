import 'package:flutter/material.dart';

import 'core/auth/auth_repository.dart';
import 'core/auth/auth_user.dart';
import 'core/theme/lms_theme.dart';
import 'features/auth/login_screen.dart';
import 'features/student/student_home_screen.dart';
import 'features/teacher/teacher_dashboard_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const LmsMobileApp());
}

class LmsMobileApp extends StatefulWidget {
  const LmsMobileApp({super.key});

  @override
  State<LmsMobileApp> createState() => _LmsMobileAppState();
}

class _LmsMobileAppState extends State<LmsMobileApp> {
  final AuthRepository _auth = AuthRepository();
  AuthUser? _user;
  var _initialising = true;

  @override
  void initState() {
    super.initState();
    _restoreSession();
  }

  Future<void> _restoreSession() async {
    final user = await _auth.restoreSession();

    if (!mounted) {
      return;
    }

    setState(() {
      _user = user;
      _initialising = false;
    });
  }

  void _signedIn(AuthUser user) {
    setState(() => _user = user);
  }

  Future<void> _signOut() async {
    await _auth.logout();

    if (mounted) {
      setState(() => _user = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'LMS MOE',
      debugShowCheckedModeBanner: false,
      theme: buildLmsTheme(),
      home: _initialising
          ? const _SplashScreen()
          : _user == null
          ? LoginScreen(auth: _auth, onSignedIn: _signedIn)
          : _RoleHome(user: _user!, onSignOut: _signOut),
    );
  }
}

class _RoleHome extends StatelessWidget {
  const _RoleHome({required this.user, required this.onSignOut});

  final AuthUser user;
  final Future<void> Function() onSignOut;

  @override
  Widget build(BuildContext context) {
    return switch (user.role) {
      UserRole.student => StudentHomeScreen(user: user, onSignOut: onSignOut),
      UserRole.teacher => TeacherDashboardScreen(
        user: user,
        onSignOut: onSignOut,
      ),
      UserRole.admin => _AdminWebOnlyScreen(onSignOut: onSignOut),
    };
  }
}

class _SplashScreen extends StatelessWidget {
  const _SplashScreen();

  @override
  Widget build(BuildContext context) {
    return const Scaffold(body: Center(child: CircularProgressIndicator()));
  }
}

class _AdminWebOnlyScreen extends StatelessWidget {
  const _AdminWebOnlyScreen({required this.onSignOut});

  final Future<void> Function() onSignOut;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Spacer(),
              const Icon(Icons.admin_panel_settings_outlined, size: 48),
              const SizedBox(height: 20),
              Text(
                'Portal MOE di web',
                style: Theme.of(context).textTheme.headlineMedium,
              ),
              const SizedBox(height: 8),
              const Text(
                'Akaun pentadbir MOE kekal menggunakan portal web buat masa ini.',
              ),
              const SizedBox(height: 24),
              FilledButton(
                onPressed: () => onSignOut(),
                child: const Text('Log keluar'),
              ),
              const Spacer(),
            ],
          ),
        ),
      ),
    );
  }
}
