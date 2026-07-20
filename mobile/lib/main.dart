import 'dart:async';

import 'package:flutter/material.dart';

import 'core/auth/auth_repository.dart';
import 'core/auth/auth_user.dart';
import 'core/platform/native_file_picker.dart';
import 'core/theme/lms_theme.dart';
import 'core/widgets/lms_logo.dart';
import 'features/auth/login_screen.dart';
import 'features/student/student_shell.dart';
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
  var _sessionRestoreStarted = false;

  @override
  void initState() {
    super.initState();
  }

  void _startSessionRestore() {
    if (_sessionRestoreStarted) return;
    _sessionRestoreStarted = true;
    unawaited(_restoreSession());
  }

  Future<void> _restoreSession() async {
    final startedAt = DateTime.now();
    final user = await _auth.restoreSession();

    // Let the branded animation register even when the encrypted token is read
    // immediately from disk. It also makes cold and warm starts feel consistent.
    const minimumSplash = Duration(milliseconds: 1300);
    final elapsed = DateTime.now().difference(startedAt);
    if (elapsed < minimumSplash) {
      await Future<void>.delayed(minimumSplash - elapsed);
    }

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

  Future<AuthUser> _updateProfile({
    required String name,
    required String username,
    String? email,
  }) async {
    final user = _user;
    if (user == null) {
      throw StateError('Sila log masuk semula untuk mengemas kini profil.');
    }

    final updated = await _auth.updateProfile(
      currentUser: user,
      name: name,
      username: username,
      email: email,
    );

    if (mounted) {
      setState(() => _user = updated);
    }

    return updated;
  }

  Future<AuthUser> _updateAvatar(NativeUploadFile file) async {
    final user = _user;
    if (user == null) {
      throw StateError('Sila log masuk semula untuk mengemas kini profil.');
    }

    final updated = await _auth.updateAvatar(currentUser: user, file: file);
    if (mounted) {
      setState(() => _user = updated);
    }
    return updated;
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'LMS MOE',
      debugShowCheckedModeBanner: false,
      theme: buildLmsTheme(),
      home: _initialising
          ? _SplashScreen(onPresented: _startSessionRestore)
          : _user == null
          ? LoginScreen(auth: _auth, onSignedIn: _signedIn)
          : _RoleHome(
              user: _user!,
              onSignOut: _signOut,
              onUpdateProfile: _updateProfile,
              onUpdateAvatar: _updateAvatar,
            ),
    );
  }
}

class _RoleHome extends StatelessWidget {
  const _RoleHome({
    required this.user,
    required this.onSignOut,
    required this.onUpdateProfile,
    required this.onUpdateAvatar,
  });

  final AuthUser user;
  final Future<void> Function() onSignOut;
  final Future<AuthUser> Function({
    required String name,
    required String username,
    String? email,
  })
  onUpdateProfile;
  final Future<AuthUser> Function(NativeUploadFile file) onUpdateAvatar;

  @override
  Widget build(BuildContext context) {
    return switch (user.role) {
      UserRole.student => StudentShell(
        user: user,
        onSignOut: onSignOut,
        onUpdateProfile: onUpdateProfile,
        onUpdateAvatar: onUpdateAvatar,
      ),
      UserRole.teacher => TeacherDashboardScreen(
        user: user,
        onSignOut: onSignOut,
        onUpdateProfile: onUpdateProfile,
        onUpdateAvatar: onUpdateAvatar,
      ),
      UserRole.admin => _AdminWebOnlyScreen(onSignOut: onSignOut),
    };
  }
}

class _SplashScreen extends StatefulWidget {
  const _SplashScreen({required this.onPresented});

  final VoidCallback onPresented;

  @override
  State<_SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<_SplashScreen>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;
  late final Animation<double> _logoScale;
  late final Animation<double> _contentOpacity;
  late final Animation<Offset> _taglineOffset;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) widget.onPresented();
    });
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    )..forward();
    _logoScale = Tween<double>(begin: .76, end: 1).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0, .72, curve: Curves.easeOutBack),
      ),
    );
    _contentOpacity = CurvedAnimation(
      parent: _controller,
      curve: const Interval(.08, .72, curve: Curves.easeOut),
    );
    _taglineOffset =
        Tween<Offset>(begin: const Offset(0, .36), end: Offset.zero).animate(
          CurvedAnimation(
            parent: _controller,
            curve: const Interval(.35, 1, curve: Curves.easeOutCubic),
          ),
        );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: LmsColors.forest,
      body: Stack(
        fit: StackFit.expand,
        children: [
          const _SplashBackdrop(),
          SafeArea(
            child: Center(
              child: FadeTransition(
                opacity: _contentOpacity,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    ScaleTransition(
                      scale: _logoScale,
                      child: const LmsLogo(
                        size: 112,
                        radius: 34,
                        backgroundColor: Colors.white,
                      ),
                    ),
                    const SizedBox(height: 22),
                    const Text(
                      'WeLearn',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 30,
                        fontWeight: FontWeight.w800,
                        letterSpacing: -.6,
                      ),
                    ),
                    const SizedBox(height: 7),
                    SlideTransition(
                      position: _taglineOffset,
                      child: const Text(
                        'Belajar, teroka, berjaya.',
                        style: TextStyle(
                          color: Color(0xFFD8E8D2),
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                    const SizedBox(height: 36),
                    const _SplashLoadingIndicator(),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SplashBackdrop extends StatelessWidget {
  const _SplashBackdrop();

  @override
  Widget build(BuildContext context) => const Stack(
    children: [
      Positioned(
        top: -126,
        right: -90,
        child: _SplashOrb(size: 300, color: Color(0x164F8D42)),
      ),
      Positioned(
        bottom: -138,
        left: -100,
        child: _SplashOrb(size: 330, color: Color(0x142F7040)),
      ),
      Positioned(
        top: 118,
        left: 34,
        child: _SplashOrb(size: 78, color: Color(0x18FFFFFF)),
      ),
    ],
  );
}

class _SplashOrb extends StatelessWidget {
  const _SplashOrb({required this.size, required this.color});

  final double size;
  final Color color;

  @override
  Widget build(BuildContext context) => Container(
    width: size,
    height: size,
    decoration: BoxDecoration(color: color, shape: BoxShape.circle),
  );
}

class _SplashLoadingIndicator extends StatefulWidget {
  const _SplashLoadingIndicator();

  @override
  State<_SplashLoadingIndicator> createState() =>
      _SplashLoadingIndicatorState();
}

class _SplashLoadingIndicatorState extends State<_SplashLoadingIndicator>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    )..repeat(reverse: true);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => AnimatedBuilder(
    animation: _controller,
    builder: (context, _) => Row(
      mainAxisSize: MainAxisSize.min,
      children: List.generate(3, (index) {
        final distance = (_controller.value - index * .18).abs();
        final opacity = (1 - distance * 1.7).clamp(.28, 1.0);
        return Container(
          width: 7,
          height: 7,
          margin: const EdgeInsets.symmetric(horizontal: 4),
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: opacity),
            shape: BoxShape.circle,
          ),
        );
      }),
    ),
  );
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
