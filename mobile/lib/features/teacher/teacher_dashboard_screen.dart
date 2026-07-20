import 'package:flutter/material.dart';

import '../../core/auth/auth_user.dart';
import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../student/profile_tab.dart';
import '../student/widgets/content_widgets.dart';
import 'content_hub_tab.dart';
import 'video_form_screen.dart';

/// The teacher shell: a bottom nav between the (live) dashboard, the content hub and the
/// profile. Each tab owns its top area — the dashboard bleeds a forest header under the
/// status bar to match the WeLearn design.
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
    return Scaffold(
      body: IndexedStack(
        index: _index,
        children: [
          _DashboardTab(repository: _repository, user: widget.user),
          _content(),
          SafeArea(
            child: ProfileTab(user: widget.user, onSignOut: widget.onSignOut, roleLabel: 'Guru'),
          ),
        ],
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
            icon: Icon(Icons.folder_open_outlined),
            selectedIcon: Icon(Icons.folder),
            label: 'Kandungan',
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

  Widget _content() => SafeArea(
    child: Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 4),
          child: Align(
            alignment: Alignment.centerLeft,
            child: Text('Kandungan saya', style: Theme.of(context).textTheme.headlineMedium),
          ),
        ),
        Expanded(child: ContentHubTab(repository: _repository)),
      ],
    ),
  );
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

  void _soon() => ScaffoldMessenger.of(context).showSnackBar(
    const SnackBar(content: Text('Ciri ini akan datang tidak lama lagi.')),
  );

  Future<void> _openAddVideo() async {
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(builder: (_) => VideoFormScreen(repository: widget.repository)),
    );
    if (created == true) _reload();
  }

  @override
  Widget build(BuildContext context) {
    final topInset = MediaQuery.of(context).padding.top;

    return FutureBuilder<TeacherDashboardData>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return SafeArea(
            child: StateMessage(
              icon: Icons.wifi_off_outlined,
              title: 'Tidak dapat memuatkan',
              subtitle: '${snapshot.error}',
              onRetry: _reload,
            ),
          );
        }

        final data = snapshot.data!;
        final s = data.stats;

        return RefreshIndicator(
          onRefresh: _reload,
          child: ListView(
            padding: EdgeInsets.zero,
            children: [
              // Forest header, bleeding under the status bar.
              Container(
                color: LmsColors.forest,
                padding: EdgeInsets.fromLTRB(20, topInset + 18, 20, 46),
                child: Row(
                  children: [
                    CircleAvatar(
                      radius: 22,
                      backgroundColor: LmsColors.accent,
                      foregroundColor: LmsColors.onAccent,
                      child: Text(_initial(widget.user.name),
                          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800)),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(widget.user.name,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                  fontSize: 17, fontWeight: FontWeight.w800, color: Colors.white)),
                          const Text('Guru',
                              style: TextStyle(fontSize: 11.5, color: Color(0xFFB9CCB8))),
                        ],
                      ),
                    ),
                    GestureDetector(
                      onTap: _openAddVideo,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
                        decoration: BoxDecoration(
                          color: LmsColors.accent,
                          borderRadius: BorderRadius.circular(11),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.add, size: 16, color: LmsColors.onAccent),
                            SizedBox(width: 4),
                            Text('Tambah',
                                style: TextStyle(
                                    fontSize: 12, fontWeight: FontWeight.w800, color: LmsColors.onAccent)),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              // Everything below is pulled up to overlap the header's lower edge.
              Transform.translate(
                offset: const Offset(0, -34),
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 0, 20, 24),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      GridView.count(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        crossAxisCount: 2,
                        mainAxisSpacing: 10,
                        crossAxisSpacing: 10,
                        childAspectRatio: 1.55,
                        children: [
                          _StatCard(icon: Icons.play_circle_outline, value: '${s.videos}', label: 'Video'),
                          _StatCard(icon: Icons.description_outlined, value: '${s.materials}', label: 'Bahan'),
                          _StatCard(icon: Icons.quiz_outlined, value: '${s.quizzes}', label: 'Kuiz'),
                          _StatCard(icon: Icons.visibility_outlined, value: '${s.views}', label: 'Tontonan'),
                        ],
                      ),
                      const SizedBox(height: 22),
                      const _Heading('Aksi pantas'),
                      const SizedBox(height: 10),
                      Row(
                        children: [
                          _QuickAction(icon: Icons.video_call_outlined, label: 'Video', onTap: _openAddVideo),
                          const SizedBox(width: 9),
                          _QuickAction(icon: Icons.upload_file_outlined, label: 'Bahan', onTap: _soon),
                          const SizedBox(width: 9),
                          _QuickAction(icon: Icons.post_add_outlined, label: 'Kuiz', onTap: _soon),
                          const SizedBox(width: 9),
                          _QuickAction(icon: Icons.library_add_outlined, label: 'Bab', onTap: _soon),
                        ],
                      ),
                      const SizedBox(height: 22),
                      const _Heading('Aktiviti terkini'),
                      const SizedBox(height: 10),
                      if (data.recentAttempts.isEmpty)
                        const StateMessage(
                          icon: Icons.inbox_outlined,
                          title: 'Belum ada aktiviti',
                          subtitle: 'Percubaan kuiz murid akan dipaparkan di sini.',
                        )
                      else
                        ...data.recentAttempts.map((a) => _ActivityRow(attempt: a)),
                      const SizedBox(height: 16),
                      _TalentCard(onTap: _soon),
                    ],
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  static String _initial(String name) {
    final t = name.trim();
    return t.isEmpty ? '?' : t[0].toUpperCase();
  }
}

class _Heading extends StatelessWidget {
  const _Heading(this.text);
  final String text;
  @override
  Widget build(BuildContext context) => Text(
    text,
    style: const TextStyle(fontSize: 15.5, fontWeight: FontWeight.w800, color: LmsColors.ink),
  );
}

class _StatCard extends StatelessWidget {
  const _StatCard({required this.icon, required this.value, required this.label});
  final IconData icon;
  final String value;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: LmsColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: LmsColors.border),
        boxShadow: const [
          BoxShadow(color: Color(0x121B3520), blurRadius: 10, offset: Offset(0, 3)),
        ],
      ),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 20, color: LmsColors.brandStrong),
          const Spacer(),
          Text(value,
              style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: LmsColors.ink)),
          Text(label,
              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: LmsColors.inkMuted)),
        ],
      ),
    );
  }
}

class _QuickAction extends StatelessWidget {
  const _QuickAction({required this.icon, required this.label, required this.onTap});
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          decoration: BoxDecoration(
            color: LmsColors.surface,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: LmsColors.border),
          ),
          padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 6),
          child: Column(
            children: [
              Icon(icon, size: 22, color: LmsColors.brandStrong),
              const SizedBox(height: 6),
              Text(label,
                  style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700, color: LmsColors.ink)),
            ],
          ),
        ),
      ),
    );
  }
}

class _ActivityRow extends StatelessWidget {
  const _ActivityRow({required this.attempt});
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
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      child: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: const BoxDecoration(color: LmsColors.brandSoft, shape: BoxShape.circle),
            child: const Icon(Icons.task_alt, size: 18, color: LmsColors.brandStrong),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${attempt.percent}% · ${attempt.quizTitle ?? 'Kuiz'}',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: LmsColors.ink),
                ),
                const SizedBox(height: 2),
                Text(
                  [
                    if (attempt.studentName != null) attempt.studentName!,
                    if (attempt.gradeName != null) attempt.gradeName!,
                  ].join(' · '),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 10.5, color: LmsColors.inkMuted),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _TalentCard extends StatelessWidget {
  const _TalentCard({required this.onTap});
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: LmsColors.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: LmsColors.border),
        ),
        padding: const EdgeInsets.all(15),
        child: Row(
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: const BoxDecoration(color: LmsColors.brandSoft, shape: BoxShape.circle),
              child: const Icon(Icons.insights_rounded, color: LmsColors.brandStrong),
            ),
            const SizedBox(width: 14),
            const Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Skor Bakat Saya',
                      style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w800, color: LmsColors.ink)),
                  SizedBox(height: 2),
                  Text('Petunjuk penglibatan murid — bukan penilaian muktamad.',
                      style: TextStyle(fontSize: 11, color: LmsColors.inkMuted)),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, color: LmsColors.inkFaint),
          ],
        ),
      ),
    );
  }
}
