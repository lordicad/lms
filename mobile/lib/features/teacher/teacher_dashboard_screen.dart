import 'package:flutter/material.dart';

import '../../core/auth/auth_user.dart';
import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../../shared/widgets/app_header.dart';
import '../student/profile_tab.dart';
import '../student/widgets/content_widgets.dart';

/// The teacher shell: a branded header and a bottom nav between the (live) dashboard
/// and the profile. Content management and analytics arrive in later teacher phases.
class TeacherDashboardScreen extends StatefulWidget {
  const TeacherDashboardScreen({super.key, required this.user, required this.onSignOut});

  final AuthUser user;
  final Future<void> Function() onSignOut;

  @override
  State<TeacherDashboardScreen> createState() => _TeacherDashboardScreenState();
}

class _TeacherDashboardScreenState extends State<TeacherDashboardScreen> {
  final TeacherRepository _repository = TeacherRepository();
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final tabs = [
      _DashboardTab(repository: _repository, user: widget.user),
      ProfileTab(user: widget.user, onSignOut: widget.onSignOut, roleLabel: 'Guru'),
    ];

    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            AppHeader(
              user: widget.user,
              title: 'WeLearn',
              subtitle: 'Guru',
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
            icon: Icon(Icons.dashboard_outlined),
            selectedIcon: Icon(Icons.dashboard),
            label: 'Papan Pemuka',
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
}

class _DashboardTab extends StatefulWidget {
  const _DashboardTab({required this.repository, required this.user});

  final TeacherRepository repository;
  final AuthUser user;

  @override
  State<_DashboardTab> createState() => _DashboardTabState();
}

class _DashboardTabState extends State<_DashboardTab> {
  late Future<TeacherDashboardData> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.repository.dashboard();
  }

  Future<void> _reload() async {
    setState(() => _future = widget.repository.dashboard());
    await _future.catchError((_) => throw Exception());
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<TeacherDashboardData>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return StateMessage(
            icon: Icons.wifi_off_outlined,
            title: 'Tidak dapat memuatkan',
            subtitle: '${snapshot.error}',
            onRetry: _reload,
          );
        }

        final data = snapshot.data!;
        final s = data.stats;

        return RefreshIndicator(
          onRefresh: _reload,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
            children: [
              Text('Hai, ${_firstName(widget.user.name)}!',
                  style: Theme.of(context).textTheme.headlineLarge),
              const SizedBox(height: 4),
              const Text('Urus kandungan pembelajaran anda di sini.'),
              const SizedBox(height: 20),
              GridView.count(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                crossAxisCount: 2,
                mainAxisSpacing: 12,
                crossAxisSpacing: 12,
                childAspectRatio: 1.7,
                children: [
                  _StatCard(icon: Icons.play_circle_outline, label: 'Video', value: '${s.videos}'),
                  _StatCard(icon: Icons.description_outlined, label: 'Bahan', value: '${s.materials}'),
                  _StatCard(icon: Icons.quiz_outlined, label: 'Kuiz', value: '${s.quizzes}'),
                  _StatCard(icon: Icons.visibility_outlined, label: 'Tontonan', value: '${s.views}'),
                ],
              ),
              const SizedBox(height: 24),
              const SectionTitle('Percubaan kuiz terkini'),
              const SizedBox(height: 8),
              if (data.recentAttempts.isEmpty)
                const StateMessage(
                  icon: Icons.inbox_outlined,
                  title: 'Belum ada percubaan',
                  subtitle: 'Percubaan kuiz murid akan dipaparkan di sini.',
                )
              else
                ...data.recentAttempts.map((a) => _AttemptTile(attempt: a)),
            ],
          ),
        );
      },
    );
  }

  static String _firstName(String name) => name.trim().split(RegExp(r'\s+')).first;
}

class _StatCard extends StatelessWidget {
  const _StatCard({required this.icon, required this.label, required this.value});
  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: LmsColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: LmsColors.border),
      ),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: LmsColors.brand),
          const Spacer(),
          Text(value, style: Theme.of(context).textTheme.headlineMedium),
          Text(label, style: Theme.of(context).textTheme.bodyMedium),
        ],
      ),
    );
  }
}

class _AttemptTile extends StatelessWidget {
  const _AttemptTile({required this.attempt});
  final RecentAttempt attempt;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: LmsColors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: LmsColors.border),
      ),
      padding: const EdgeInsets.all(12),
      child: Row(
        children: [
          Container(
            decoration: BoxDecoration(
              color: LmsColors.brandSoft,
              borderRadius: BorderRadius.circular(10),
            ),
            padding: const EdgeInsets.all(9),
            child: const Icon(Icons.task_alt, color: LmsColors.brand, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  attempt.quizTitle ?? 'Kuiz',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 2),
                Text(
                  [
                    if (attempt.studentName != null) attempt.studentName!,
                    if (attempt.gradeName != null) attempt.gradeName!,
                  ].join(' · '),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Text('${attempt.percent}%',
              style: const TextStyle(fontWeight: FontWeight.w800, color: LmsColors.brandStrong)),
        ],
      ),
    );
  }
}
