import 'dart:async';

import 'package:flutter/material.dart';

import '../../core/auth/auth_user.dart';
import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/platform/native_file_picker.dart';
import '../../core/settings/app_settings.dart';
import '../../core/theme/lms_theme.dart';
import '../../core/widgets/lms_logo.dart';
import '../../core/widgets/loading_skeleton.dart';
import '../../core/widgets/role_tutorial.dart';
import '../student/profile_tab.dart';
import '../student/widgets/content_widgets.dart';
import 'content_hub_tab.dart';
import 'chapters_manage_tab.dart';
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
    required this.loadProfileOptions,
    required this.onUpdateProfile,
    required this.onUpdateAvatar,
    required this.themeMode,
    required this.language,
    required this.onThemeModeChanged,
    required this.onLanguageChanged,
  });

  final AuthUser user;
  final Future<void> Function() onSignOut;
  final Future<ProfileOptions> Function() loadProfileOptions;
  final Future<AuthUser> Function(ProfileUpdate update) onUpdateProfile;
  final Future<AuthUser> Function(NativeUploadFile file) onUpdateAvatar;
  final ThemeMode themeMode;
  final AppLanguage language;
  final Future<void> Function(ThemeMode value) onThemeModeChanged;
  final Future<void> Function(AppLanguage value) onLanguageChanged;

  @override
  State<TeacherDashboardScreen> createState() => _TeacherDashboardScreenState();
}

class _TeacherDashboardScreenState extends State<TeacherDashboardScreen> {
  final TeacherRepository _repository = TeacherRepository();
  int _index = 0;
  int _contentVersion = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      unawaited(RoleTutorial.showForNewUser(context, widget.user));
    });
  }

  @override
  Widget build(BuildContext context) {
    final english = widget.language == AppLanguage.en;
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
              loadProfileOptions: widget.loadProfileOptions,
              onUpdateProfile: widget.onUpdateProfile,
              onUpdateAvatar: widget.onUpdateAvatar,
              themeMode: widget.themeMode,
              language: widget.language,
              onThemeModeChanged: widget.onThemeModeChanged,
              onLanguageChanged: widget.onLanguageChanged,
              roleLabel: 'Guru',
            ),
          ),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: [
          NavigationDestination(
            icon: const Icon(Icons.dashboard_outlined),
            selectedIcon: const Icon(Icons.dashboard),
            label: english ? 'Dashboard' : 'Papan Pemuka',
          ),
          NavigationDestination(
            icon: const Icon(Icons.folder_open_outlined),
            selectedIcon: const Icon(Icons.folder),
            label: english ? 'Content' : 'Kandungan',
          ),
          NavigationDestination(
            icon: const Icon(Icons.person_outline),
            selectedIcon: const Icon(Icons.person),
            label: english ? 'Profile' : 'Profil',
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

  Future<void> _openChapters() async {
    await Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) => Scaffold(
          appBar: AppBar(title: const Text('Urus Bab')),
          body: SafeArea(
            top: false,
            child: ChaptersManageTab(repository: widget.repository),
          ),
        ),
      ),
    );
    widget.onContentChanged();
    _reload();
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
          return const TeacherDashboardSkeleton();
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
        final actionColor = Theme.of(context).brightness == Brightness.dark
            ? LmsColors.accent
            : LmsColors.brandStrong;

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
                        _TeacherHeaderAvatar(user: widget.user),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Portal Cikgu',
                                style: TextStyle(
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.w700,
                                  color: Color(0xFFC8DDC5),
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                'Cikgu ${widget.user.username}',
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
                      LayoutBuilder(
                        builder: (context, constraints) => GridView(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 2,
                            mainAxisSpacing: 10,
                            crossAxisSpacing: 10,
                            // A fixed, responsive-safe height leaves room for
                            // the icon, value and label even on narrow phones.
                            // Tablets keep the cards compact instead of huge.
                            mainAxisExtent: constraints.maxWidth < 600
                                ? 118
                                : 128,
                          ),
                          children: [
                            _StatCard(
                              icon: Icons.visibility_outlined,
                              value: '${s.views}',
                              label: 'Tontonan video',
                              tint: const Color(0xFFE7EFFD),
                            ),
                            _StatCard(
                              icon: Icons.favorite_border_rounded,
                              value: '${s.favourites}',
                              label: 'Video digemari',
                              tint: const Color(0xFFFBE4ED),
                            ),
                            _StatCard(
                              icon: Icons.file_download_outlined,
                              value: '${s.downloads}',
                              label: 'Bahan dimuat turun',
                              tint: const Color(0xFFDCF2EE),
                            ),
                            _StatCard(
                              icon: Icons.quiz_outlined,
                              value: '${s.attempts}',
                              label: 'Percubaan kuiz',
                              tint: const Color(0xFFFFF0D9),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 12),
                      _PassFailCard(data: data.passFail),
                      if (data.leaderboards.isNotEmpty) ...[
                        const SizedBox(height: 22),
                        const _Heading('Kandungan paling mendapat sambutan'),
                        const SizedBox(height: 10),
                        for (final board in data.leaderboards)
                          _DashboardLeaderboard(board: board),
                      ],
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
                                foregroundColor: actionColor,
                                side: BorderSide(
                                  color: actionColor.withValues(alpha: .34),
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
                                foregroundColor: actionColor,
                                side: BorderSide(
                                  color: actionColor.withValues(alpha: .34),
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
                            onTap: _openChapters,
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

/// Mirrors the web Cikgu userbar: the uploaded profile photo when available,
/// otherwise the teacher's initials instead of the application logo.
class _TeacherHeaderAvatar extends StatelessWidget {
  const _TeacherHeaderAvatar({required this.user});

  final AuthUser user;

  String get _initials {
    final parts = user.name.trim().split(RegExp(r'\s+'));
    final letters = parts
        .where((part) => part.isNotEmpty)
        .take(2)
        .map((part) => part.substring(0, 1).toUpperCase())
        .join();
    if (letters.isNotEmpty) return letters;
    final username = user.username.trim();
    if (username.isEmpty) return '?';
    return username
        .substring(0, username.length < 2 ? username.length : 2)
        .toUpperCase();
  }

  @override
  Widget build(BuildContext context) {
    final url = user.avatarUrl;

    return Container(
      width: 48,
      height: 48,
      padding: const EdgeInsets.all(2),
      decoration: const BoxDecoration(
        color: Colors.white,
        shape: BoxShape.circle,
      ),
      child: ClipOval(
        child: url == null || url.isEmpty
            ? _AvatarInitials(initials: _initials)
            : Image.network(
                url,
                fit: BoxFit.cover,
                errorBuilder: (_, _, _) => _AvatarInitials(initials: _initials),
              ),
      ),
    );
  }
}

class _AvatarInitials extends StatelessWidget {
  const _AvatarInitials({required this.initials});

  final String initials;

  @override
  Widget build(BuildContext context) => ColoredBox(
    color: LmsColors.brandSoft,
    child: Center(
      child: Text(
        initials,
        style: const TextStyle(
          color: LmsColors.brandStrong,
          fontSize: 14,
          fontWeight: FontWeight.w800,
        ),
      ),
    ),
  );
}

/// Compact mobile version of the four engagement leaderboards from the web
/// teacher dashboard: views, favourites, downloads and quiz attempts.
class _DashboardLeaderboard extends StatelessWidget {
  const _DashboardLeaderboard({required this.board});

  final TeacherTalentLeaderboard board;

  IconData get _icon => switch (board.kind) {
    'views' => Icons.visibility_outlined,
    'favourites' => Icons.favorite_border_rounded,
    'downloads' => Icons.file_download_outlined,
    'attempts' => Icons.quiz_outlined,
    _ => Icons.insights_outlined,
  };

  String get _metric => switch (board.kind) {
    'views' => 'tontonan',
    'favourites' => 'suka',
    'downloads' => 'muat turun',
    'attempts' => 'cubaan',
    _ => '',
  };

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: LmsPalette.surface(context),
        border: Border.all(color: LmsPalette.border(context)),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 13, 14, 9),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(7),
                  decoration: BoxDecoration(
                    color: LmsPalette.soft(context),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(_icon, size: 17, color: LmsColors.brandStrong),
                ),
                const SizedBox(width: 9),
                Expanded(
                  child: Text(
                    board.title,
                    style: TextStyle(
                      fontSize: 13.5,
                      fontWeight: FontWeight.w800,
                      color: LmsPalette.text(context),
                    ),
                  ),
                ),
              ],
            ),
          ),
          if (board.items.isEmpty)
            Padding(
              padding: const EdgeInsets.fromLTRB(14, 2, 14, 15),
              child: Text(
                'Belum ada data.',
                style: TextStyle(
                  fontSize: 12,
                  color: LmsPalette.muted(context),
                ),
              ),
            )
          else
            for (var index = 0; index < board.items.length; index++)
              _DashboardLeaderboardItem(
                item: board.items[index],
                rank: index + 1,
                metric: _metric,
              ),
        ],
      ),
    );
  }
}

class _DashboardLeaderboardItem extends StatelessWidget {
  const _DashboardLeaderboardItem({
    required this.item,
    required this.rank,
    required this.metric,
  });

  final TeacherTalentItem item;
  final int rank;
  final String metric;

  @override
  Widget build(BuildContext context) {
    final details = [
      if (item.subjectName != null) item.subjectName!,
      if (item.chapterLabel != null) item.chapterLabel!,
    ].join(' • ');
    final medal = switch (rank) {
      1 => '1',
      2 => '2',
      3 => '3',
      _ => '$rank',
    };

    return Container(
      padding: const EdgeInsets.fromLTRB(14, 9, 14, 11),
      decoration: BoxDecoration(
        border: Border(top: BorderSide(color: LmsPalette.border(context))),
      ),
      child: Row(
        children: [
          SizedBox(
            width: 23,
            child: Text(
              medal,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w800,
                color: rank <= 3
                    ? LmsColors.warning
                    : LmsPalette.muted(context),
              ),
            ),
          ),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w800,
                    color: LmsPalette.text(context),
                  ),
                ),
                if (details.isNotEmpty)
                  Text(
                    details,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontSize: 10.5,
                      color: LmsPalette.muted(context),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(width: 9),
          Text(
            '${item.value}${metric.isEmpty ? '' : ' $metric'}',
            style: TextStyle(
              fontSize: 11.5,
              fontWeight: FontWeight.w800,
              color: LmsColors.brandStrong,
            ),
          ),
        ],
      ),
    );
  }
}

class _PassFailCard extends StatelessWidget {
  const _PassFailCard({required this.data});

  final TeacherPassFail data;

  @override
  Widget build(BuildContext context) {
    if (data.total == 0) {
      return Container(
        width: double.infinity,
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: LmsPalette.surface(context),
          border: Border.all(color: LmsPalette.border(context)),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Row(
          children: [
            Icon(Icons.quiz_outlined, color: LmsColors.brandStrong),
            SizedBox(width: 12),
            Expanded(
              child: Text(
                'Belum ada percubaan kuiz selesai lagi.',
                style: TextStyle(color: LmsPalette.muted(context)),
              ),
            ),
          ],
        ),
      );
    }

    final passedPercent = (data.passed / data.total * 100).round();
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: LmsPalette.surface(context),
        border: Border.all(color: LmsPalette.border(context)),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Lulus / Gagal Kuiz',
            style: TextStyle(
              fontWeight: FontWeight.w800,
              color: LmsPalette.text(context),
            ),
          ),
          const SizedBox(height: 12),
          ClipRRect(
            borderRadius: BorderRadius.circular(99),
            child: SizedBox(
              height: 10,
              child: Row(
                children: [
                  if (data.passed > 0)
                    Expanded(
                      flex: data.passed,
                      child: const ColoredBox(color: LmsColors.brand),
                    ),
                  if (data.failed > 0)
                    Expanded(
                      flex: data.failed,
                      child: const ColoredBox(color: LmsColors.danger),
                    ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              _PassFailLabel(
                color: LmsColors.brand,
                label: 'Lulus',
                value: '${data.passed} ($passedPercent%)',
              ),
              const SizedBox(width: 18),
              _PassFailLabel(
                color: LmsColors.danger,
                label: 'Gagal',
                value: '${data.failed} (${100 - passedPercent}%)',
              ),
              const Spacer(),
              Text(
                '${data.total} kuiz',
                style: TextStyle(
                  fontSize: 11.5,
                  color: LmsPalette.muted(context),
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _PassFailLabel extends StatelessWidget {
  const _PassFailLabel({
    required this.color,
    required this.label,
    required this.value,
  });

  final Color color;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      Container(
        width: 9,
        height: 9,
        decoration: BoxDecoration(color: color, shape: BoxShape.circle),
      ),
      const SizedBox(width: 5),
      Text(
        '$label $value',
        style: TextStyle(
          fontSize: 11.5,
          fontWeight: FontWeight.w700,
          color: LmsPalette.muted(context),
        ),
      ),
    ],
  );
}

class _Heading extends StatelessWidget {
  const _Heading(this.text);
  final String text;
  @override
  Widget build(BuildContext context) => Text(
    text,
    style: TextStyle(
      fontSize: 15.5,
      fontWeight: FontWeight.w800,
      color: LmsPalette.text(context),
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
        color: LmsPalette.surface(context),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: LmsPalette.border(context)),
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
            style: TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.w800,
              color: LmsPalette.text(context),
            ),
          ),
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: LmsPalette.muted(context),
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
            color: LmsPalette.surfaceRaised(context),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: LmsPalette.border(context)),
          ),
          padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 6),
          child: Column(
            children: [
              Container(
                width: 30,
                height: 30,
                decoration: BoxDecoration(
                  color: LmsPalette.soft(context),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, size: 17, color: LmsColors.brandStrong),
              ),
              const SizedBox(height: 6),
              Text(
                label,
                style: TextStyle(
                  fontSize: 10.5,
                  fontWeight: FontWeight.w700,
                  color: LmsPalette.text(context),
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
        color: LmsPalette.surface(context),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: LmsPalette.border(context)),
      ),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      child: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: LmsPalette.soft(context),
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
                  style: TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w700,
                    color: LmsPalette.text(context),
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
                  style: TextStyle(
                    fontSize: 10.5,
                    color: LmsPalette.muted(context),
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
          color: LmsPalette.surface(context),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: LmsPalette.border(context)),
        ),
        padding: const EdgeInsets.all(15),
        child: Row(
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: LmsPalette.soft(context),
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.insights_rounded,
                color: LmsColors.brandStrong,
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Skor Bakat Saya',
                    style: TextStyle(
                      fontSize: 13.5,
                      fontWeight: FontWeight.w800,
                      color: LmsPalette.text(context),
                    ),
                  ),
                  SizedBox(height: 2),
                  Text(
                    'Petunjuk penglibatan murid — bukan penilaian muktamad.',
                    style: TextStyle(
                      fontSize: 11,
                      color: LmsPalette.muted(context),
                    ),
                  ),
                ],
              ),
            ),
            Icon(Icons.chevron_right, color: LmsPalette.faint(context)),
          ],
        ),
      ),
    );
  }
}
