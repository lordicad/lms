import 'package:flutter/material.dart';

import '../../core/auth/auth_user.dart';
import '../../core/content/content_repository.dart';
import '../../core/platform/native_file_picker.dart';
import '../../shared/widgets/app_header.dart';
import 'dashboard_tab.dart';
import 'offline_tab.dart';
import 'profile_tab.dart';
import 'quizzes_tab.dart';
import 'ranking_screen.dart';
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
    required this.loadProfileOptions,
    required this.onUpdateProfile,
    required this.onUpdateAvatar,
  });

  final AuthUser user;
  final Future<void> Function() onSignOut;
  final Future<ProfileOptions> Function() loadProfileOptions;
  final Future<AuthUser> Function(ProfileUpdate update) onUpdateProfile;
  final Future<AuthUser> Function(NativeUploadFile file) onUpdateAvatar;

  @override
  State<StudentShell> createState() => _StudentShellState();
}

class _StudentShellState extends State<StudentShell> {
  final ContentRepository _repository = ContentRepository();
  int _index = 0;
  late int? _activeGrade;

  @override
  void initState() {
    super.initState();
    _activeGrade = widget.user.grade?.level;
  }

  @override
  Widget build(BuildContext context) {
    final gradeName = _activeGrade == null
        ? (widget.user.grade?.name ?? 'Tahun anda')
        : 'Tahun $_activeGrade';

    final tabs = [
      DashboardTab(
        repository: _repository,
        grade: _activeGrade,
        onSeeAllSubjects: () => setState(() => _index = 1),
      ),
      SubjectsTab(repository: _repository, grade: _activeGrade),
      SavedTab(repository: _repository),
      OfflineTab(repository: _repository, grade: _activeGrade),
      RankingScreen(repository: _repository),
      QuizzesTab(repository: _repository, grade: _activeGrade),
    ];

    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            AppHeader(
              user: widget.user,
              gradeLabel: gradeName,
              onSelectGrade: _pickGrade,
              onProfile: _openProfile,
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
            label: 'Utama',
          ),
          NavigationDestination(
            icon: Icon(Icons.menu_book_outlined),
            selectedIcon: Icon(Icons.menu_book),
            label: 'Subjek',
          ),
          NavigationDestination(
            icon: Icon(Icons.bookmark_border),
            selectedIcon: Icon(Icons.bookmark),
            label: 'Kegemaran',
          ),
          NavigationDestination(
            icon: Icon(Icons.download_for_offline_outlined),
            selectedIcon: Icon(Icons.download_for_offline),
            label: 'Offline',
          ),
          NavigationDestination(
            icon: Icon(Icons.emoji_events_outlined),
            selectedIcon: Icon(Icons.emoji_events),
            label: 'Ranking',
          ),
          NavigationDestination(
            icon: Icon(Icons.quiz_outlined),
            selectedIcon: Icon(Icons.quiz),
            label: 'Kuiz',
          ),
        ],
      ),
    );
  }

  void _openSearch() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) =>
            SearchScreen(repository: _repository, grade: _activeGrade),
      ),
    );
  }

  Future<void> _pickGrade() async {
    final selected = await showModalBottomSheet<int>(
      context: context,
      showDragHandle: true,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const ListTile(
              leading: Icon(Icons.school_outlined),
              title: Text('Pilih Tahun'),
              subtitle: Text('Untuk ulang kaji kandungan Tahun lain.'),
            ),
            for (var level = 1; level <= 6; level++)
              ListTile(
                leading: Icon(
                  _activeGrade == level
                      ? Icons.check_circle_rounded
                      : Icons.circle_outlined,
                ),
                title: Text('Tahun $level'),
                onTap: () => Navigator.pop(context, level),
              ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );

    if (selected != null && selected != _activeGrade && mounted) {
      setState(() => _activeGrade = selected);
    }
  }

  Future<void> _openProfile() async {
    await Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) => Scaffold(
          appBar: AppBar(title: const Text('Profil saya')),
          body: SafeArea(
            child: ProfileTab(
              user: widget.user,
              onSignOut: widget.onSignOut,
              loadProfileOptions: widget.loadProfileOptions,
              onUpdateProfile: widget.onUpdateProfile,
              onUpdateAvatar: widget.onUpdateAvatar,
            ),
          ),
        ),
      ),
    );
  }
}
