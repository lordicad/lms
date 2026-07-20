import 'package:flutter/material.dart';

import '../../core/auth/auth_user.dart';
import '../../core/content/content_repository.dart';
import '../../core/platform/native_file_picker.dart';
import '../../shared/widgets/app_header.dart';
import 'dashboard_tab.dart';
import 'profile_tab.dart';
import 'quizzes_tab.dart';
import 'saved_tab.dart';
import 'search_screen.dart';
import 'subjects_tab.dart';

/// The student's shell: a branded header, a bottom nav switching between the dashboard and
/// the subject index. Drill-down screens (chapters, watch) are pushed on top via Navigator.
class StudentShell extends StatefulWidget {
  const StudentShell({
    super.key,
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
  State<StudentShell> createState() => _StudentShellState();
}

class _StudentShellState extends State<StudentShell> {
  final ContentRepository _repository = ContentRepository();
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final gradeName = widget.user.grade?.name ?? 'Tahun anda';

    final tabs = [
      DashboardTab(
        repository: _repository,
        user: widget.user,
        onSeeAllSubjects: () => setState(() => _index = 1),
      ),
      SubjectsTab(repository: _repository),
      SavedTab(repository: _repository),
      QuizzesTab(repository: _repository),
      ProfileTab(
        user: widget.user,
        onSignOut: widget.onSignOut,
        onUpdateProfile: widget.onUpdateProfile,
        onUpdateAvatar: widget.onUpdateAvatar,
      ),
    ];

    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            AppHeader(
              user: widget.user,
              title: 'Belajar',
              subtitle: gradeName,
              onSignOut: widget.onSignOut,
              onSearch: _openSearch,
            ),
            Expanded(
              child: IndexedStack(index: _index, children: tabs),
            ),
          ],
        ),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home),
            label: 'Belajar',
          ),
          NavigationDestination(
            icon: Icon(Icons.menu_book_outlined),
            selectedIcon: Icon(Icons.menu_book),
            label: 'Subjek',
          ),
          NavigationDestination(
            icon: Icon(Icons.bookmark_border),
            selectedIcon: Icon(Icons.bookmark),
            label: 'Simpan',
          ),
          NavigationDestination(
            icon: Icon(Icons.quiz_outlined),
            selectedIcon: Icon(Icons.quiz),
            label: 'Kuiz',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: 'Profil',
          ),
        ],
      ),
    );
  }

  void _openSearch() {
    Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => SearchScreen(repository: _repository)),
    );
  }
}
