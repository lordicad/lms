import 'package:flutter/material.dart';

import '../../core/auth/auth_user.dart';
import '../../core/content/content_repository.dart';
import '../../shared/widgets/app_header.dart';
import 'dashboard_tab.dart';
import 'subjects_tab.dart';

/// The student's shell: a branded header, a bottom nav switching between the dashboard and
/// the subject index. Drill-down screens (chapters, watch) are pushed on top via Navigator.
class StudentShell extends StatefulWidget {
  const StudentShell({super.key, required this.user, required this.onSignOut});

  final AuthUser user;
  final Future<void> Function() onSignOut;

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
      DashboardTab(repository: _repository, user: widget.user),
      SubjectsTab(repository: _repository),
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
            ),
            Expanded(child: IndexedStack(index: _index, children: tabs)),
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
        ],
      ),
    );
  }
}
