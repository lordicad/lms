import 'package:flutter/material.dart';

import '../../core/auth/auth_user.dart';
import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/platform/native_file_picker.dart';
import '../../core/theme/lms_theme.dart';
import '../../core/widgets/lms_logo.dart';
import '../student/profile_tab.dart';
import '../student/widgets/content_widgets.dart';
import 'content_hub_tab.dart';
import 'material_form_screen.dart';
import 'quiz_builder_screen.dart';
import 'teacher_notifications_screen.dart';
import 'teacher_ranking_screen.dart';
import 'teacher_talent_screen.dart';
import 'video_form_screen.dart';

/// The teacher shell: a bottom nav between the (live) dashboard, the content hub and the
/// profile. Each tab owns its top area — the dashboard bleeds a forest header under the
/// status bar to match the WeLearn design.
class TeacherDashboardScreen extends StatefulWidget {
  const TeacherDashboardScreen({
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
  State<TeacherDashboardScreen> createState() => _TeacherDashboardScreenState();
}

class _TeacherDashboardScreenState extends State<TeacherDashboardScreen> {
  final TeacherRepository _repository = TeacherRepository();
  int _index = 0;
  int _contentVersion = 0;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(
        index: _index,
        children: [
          _DashboardTab(
            repository: _repository,
            user: widget.user,
            onContentChanged: () => setState(() => _contentVersion++),
          ),
          _content(),
          SafeArea(
            child: ProfileTab(
              user: widget.user,
              onSignOut: widget.onSignOut,
              onUpdateProfile: widget.onUpdateProfile,
              onUpdateAvatar: widget.onUpdateAvatar,
              roleLabel: 'Guru',
            ),
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
          padding: const EdgeInsets.fromLTRB(20, 18, 20, 2),
          child: Row(
            children: [
              const LmsLogo(size: 46, radius: 15),
              const SizedBox(width: 13),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Kandungan saya',
                      style: Theme.of(context).textTheme.headlineMedium,
                    ),
                    const SizedBox(height: 2),
                    const Text(
                      'Urus video, bahan dan kuiz anda.',
                      style: TextStyle(
                        fontSize: 11.5,
                        color: LmsColors.inkMuted,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        Expanded(
          child: ContentHubTab(
            key: ValueKey(_contentVersion),
            repository: _repository,
          ),
        ),
      ],
    ),
  );
}

class _DashboardTab extends StatefulWidget {
  const _DashboardTab({
    required this.repository,
    required this.user,
    required this.onContentChanged,
  });

  final TeacherRepository repository;
  final AuthUser user;
  final VoidCallback onContentChanged;

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
    setState(() {
      _future = widget.repository.dashboard();
    });
    await _future.catchError((_) => throw Exception());
  }

  void _soon() => ScaffoldMessenger.of(context).showSnackBar(
    const SnackBar(content: Text('Ciri ini akan datang tidak lama lagi.')),
  );

  Future<void> _openAddVideo() async {
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => VideoFormScreen(repository: widget.repository),
      ),
    );
    if (created == true) {
      widget.onContentChanged();
      _reload();
    }
  }

  Future<void> _openAddQuiz() async {
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => QuizBuilderScreen(repository: widget.repository),
      ),
    );
    if (created == true) {
      widget.onContentChanged();
      _reload();
    }
  }

  Future<void> _openAddMaterial() async {
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => MaterialFormScreen(repository: widget.repository),
      ),
    );
    if (created == true) {
      widget.onContentChanged();
      _reload();
    }
  }

  Future<void> _openNotifications() async {
    await Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) =>
            TeacherNotificationsScreen(repository: widget.repository),
      ),
    );
  }

  Future<void> _openTalent() async {
    await Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) => TeacherTalentScreen(repository: widget.repository),
      ),
    );
  }

  Future<void> _openRanking() async {
    await Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) => TeacherRankingScreen(repository: widget.repository),
      ),
    );
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
              // Branded header, bleeding under the status bar.
              Container(
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    colors: [LmsColors.forest, LmsColors.brandStrong],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                ),
                padding: EdgeInsets.fromLTRB(20, topInset + 16, 20, 54),
                child: Stack(
                  clipBehavior: Clip.none,
                  children: [
                    Positioned(
                      right: 84,
                      top: -40,
                      child: Container(
                        width: 130,
                        height: 130,
                        decoration: const BoxDecoration(
                          color: Color(0x1299C883),
                          shape: BoxShape.circle,
                        ),
                      ),
                    ),
                    Row(
                      children: [
                        const LmsLogo(size: 48, radius: 16),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Ruang Guru',
                                style: TextStyle(
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.w700,
                                  color: Color(0xFFC8DDC5),
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                widget.user.name,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontSize: 17,
                                  fontWeight: FontWeight.w800,
                                  color: Colors.white,
                                ),
                              ),
                            ],
                          ),
                        ),
                        IconButton(
                          tooltip: 'Notifikasi',
                          onPressed: _openNotifications,
                          icon: const Icon(
                            Icons.notifications_none_rounded,
                            color: Colors.white,
                          ),
                        ),
                        Material(
                          color: Colors.transparent,
                          child: InkWell(
                            onTap: _openAddVideo,
                            borderRadius: BorderRadius.circular(13),
                            child: Ink(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 13,
                                vertical: 10,
                              ),
                              decoration: BoxDecoration(
                                color: LmsColors.accent,
                                borderRadius: BorderRadius.circular(13),
                              ),
                              child: const Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(
                                    Icons.add_rounded,
                                    size: 17,
                                    color: LmsColors.onAccent,
                                  ),
                                  SizedBox(width: 4),
                                  Text(
                                    'Tambah',
                                    style: TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w800,
                                      color: LmsColors.onAccent,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ],
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
                        // The tablet has wide columns; a higher ratio prevents the cards from
                        // becoming tall empty boxes when they only contain one number.
                        childAspectRatio: 2.15,
                        children: [
                          _StatCard(
                            icon: Icons.play_circle_outline,
                            value: '${s.videos}',
                            label: 'Video',
                            tint: const Color(0xFFE5F3E0),
                          ),
                          _StatCard(
                            icon: Icons.description_outlined,
                            value: '${s.materials}',
                            label: 'Bahan',
                            tint: const Color(0xFFFFF0D9),
                          ),
                          _StatCard(
                            icon: Icons.quiz_outlined,
                            value: '${s.quizzes}',
                            label: 'Kuiz',
                            tint: const Color(0xFFE7EFFD),
                          ),
                          _StatCard(
                            icon: Icons.visibility_outlined,
                            value: '${s.views}',
                            label: 'Tontonan',
                            tint: const Color(0xFFF2E9FB),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: _openTalent,
                              icon: const Icon(Icons.insights_outlined),
                              label: const Text('Bakat'),
                              style: OutlinedButton.styleFrom(
                                minimumSize: const Size.fromHeight(48),
                                foregroundColor: LmsColors.brandStrong,
                                side: const BorderSide(
                                  color: Color(0x334A7C3A),
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: _openRanking,
                              icon: const Icon(Icons.emoji_events_outlined),
                              label: const Text('Ranking'),
                              style: OutlinedButton.styleFrom(
                                minimumSize: const Size.fromHeight(48),
                                foregroundColor: LmsColors.brandStrong,
                                side: const BorderSide(
                                  color: Color(0x334A7C3A),
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 22),
                      const _Heading('Aksi pantas'),
                      const SizedBox(height: 10),
                      Row(
                        children: [
                          _QuickAction(
                            icon: Icons.video_call_outlined,
                            label: 'Video',
                            onTap: _openAddVideo,
                          ),
                          const SizedBox(width: 9),
                          _QuickAction(
                            icon: Icons.upload_file_outlined,
                            label: 'Bahan',
                            onTap: _openAddMaterial,
                          ),
                          const SizedBox(width: 9),
                          _QuickAction(
                            icon: Icons.post_add_outlined,
                            label: 'Kuiz',
                            onTap: _openAddQuiz,
                          ),
                          const SizedBox(width: 9),
                          _QuickAction(
                            icon: Icons.library_add_outlined,
                            label: 'Bab',
                            onTap: _soon,
                          ),
                        ],
                      ),
                      const SizedBox(height: 22),
                      const _Heading('Aktiviti terkini'),
                      const SizedBox(height: 10),
                      if (data.recentAttempts.isEmpty)
                        const StateMessage(
                          icon: Icons.inbox_outlined,
                          title: 'Belum ada aktiviti',
                          subtitle:
                              'Percubaan kuiz murid akan dipaparkan di sini.',
                        )
                      else
                        ...data.recentAttempts.map(
                          (a) => _ActivityRow(attempt: a),
                        ),
                      const SizedBox(height: 16),
                      _TalentCard(onTap: _openTalent),
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
}

class _Heading extends StatelessWidget {
  const _Heading(this.text);
  final String text;
  @override
  Widget build(BuildContext context) => Text(
    text,
    style: const TextStyle(
      fontSize: 15.5,
      fontWeight: FontWeight.w800,
      color: LmsColors.ink,
    ),
  );
}

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.icon,
    required this.value,
    required this.label,
    required this.tint,
  });
  final IconData icon;
  final String value;
  final String label;
  final Color tint;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: LmsColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: LmsColors.border),
        boxShadow: const [
          BoxShadow(
            color: Color(0x121B3520),
            blurRadius: 10,
            offset: Offset(0, 3),
          ),
        ],
      ),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 35,
            height: 35,
            decoration: BoxDecoration(color: tint, shape: BoxShape.circle),
            child: Icon(icon, size: 19, color: LmsColors.brandStrong),
          ),
          const Spacer(),
          Text(
            value,
            style: const TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.w800,
              color: LmsColors.ink,
            ),
          ),
          Text(
            label,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: LmsColors.inkMuted,
            ),
          ),
        ],
      ),
    );
  }
}

class _QuickAction extends StatelessWidget {
  const _QuickAction({
    required this.icon,
    required this.label,
    required this.onTap,
  });
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
            color: const Color(0xFFFBFCFA),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: LmsColors.border),
          ),
          padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 6),
          child: Column(
            children: [
              Container(
                width: 30,
                height: 30,
                decoration: const BoxDecoration(
                  color: LmsColors.brandSoft,
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, size: 17, color: LmsColors.brandStrong),
              ),
              const SizedBox(height: 6),
              Text(
                label,
                style: const TextStyle(
                  fontSize: 10.5,
                  fontWeight: FontWeight.w700,
                  color: LmsColors.ink,
                ),
              ),
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
            decoration: const BoxDecoration(
              color: LmsColors.brandSoft,
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.task_alt,
              size: 18,
              color: LmsColors.brandStrong,
            ),
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
                  style: const TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w700,
                    color: LmsColors.ink,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  [
                    if (attempt.studentName != null) attempt.studentName!,
                    if (attempt.gradeName != null) attempt.gradeName!,
                  ].join(' · '),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 10.5,
                    color: LmsColors.inkMuted,
                  ),
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
              decoration: const BoxDecoration(
                color: LmsColors.brandSoft,
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.insights_rounded,
                color: LmsColors.brandStrong,
              ),
            ),
            const SizedBox(width: 14),
            const Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Skor Bakat Saya',
                    style: TextStyle(
                      fontSize: 13.5,
                      fontWeight: FontWeight.w800,
                      color: LmsColors.ink,
                    ),
                  ),
                  SizedBox(height: 2),
                  Text(
                    'Petunjuk penglibatan murid — bukan penilaian muktamad.',
                    style: TextStyle(fontSize: 11, color: LmsColors.inkMuted),
                  ),
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
