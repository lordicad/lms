import 'package:flutter/material.dart';

import '../../core/auth/auth_user.dart';
import '../../core/theme/lms_theme.dart';
import '../../shared/widgets/app_header.dart';
import '../../shared/widgets/surface_card.dart';

class TeacherDashboardScreen extends StatelessWidget {
  const TeacherDashboardScreen({
    super.key,
    required this.user,
    required this.onSignOut,
  });

  final AuthUser user;
  final Future<void> Function() onSignOut;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            AppHeader(
              user: user,
              title: 'Papan Pemuka',
              subtitle: 'Guru',
              onSignOut: onSignOut,
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 20, 20, 32),
                children: [
                  Text(
                    'Papan Pemuka',
                    style: Theme.of(context).textTheme.headlineLarge,
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'Selamat datang, ${_firstName(user.name)}. Urus kandungan pembelajaran anda di sini.',
                  ),
                  const SizedBox(height: 24),
                  const _TeacherStats(),
                  const SizedBox(height: 28),
                  Text(
                    'Aksi pantas',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const SizedBox(height: 12),
                  const _TeacherActions(),
                  const SizedBox(height: 28),
                  Text(
                    'Aktiviti terkini',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const SizedBox(height: 12),
                  const _EmptyActivity(),
                ],
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: 0,
        destinations: [
          NavigationDestination(
            icon: Icon(Icons.dashboard_outlined),
            selectedIcon: Icon(Icons.dashboard),
            label: 'Utama',
          ),
          NavigationDestination(
            icon: Icon(Icons.video_library_outlined),
            selectedIcon: Icon(Icons.video_library),
            label: 'Kandungan',
          ),
          NavigationDestination(
            icon: Icon(Icons.bar_chart_outlined),
            selectedIcon: Icon(Icons.bar_chart),
            label: 'Analitik',
          ),
        ],
      ),
    );
  }

  static String _firstName(String name) =>
      name.trim().split(RegExp(r'\s+')).first;
}

class _TeacherStats extends StatelessWidget {
  const _TeacherStats();

  @override
  Widget build(BuildContext context) {
    const stats = [
      ('Video', '—', Icons.play_circle_outline),
      ('Bahan', '—', Icons.description_outlined),
      ('Kuiz', '—', Icons.quiz_outlined),
      ('Tontonan', '—', Icons.visibility_outlined),
    ];

    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: stats.length,
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        mainAxisSpacing: 12,
        crossAxisSpacing: 12,
        childAspectRatio: 1.55,
      ),
      itemBuilder: (context, index) {
        final stat = stats[index];
        return SurfaceCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(stat.$3, color: LmsColors.brand),
              const Spacer(),
              Text(stat.$2, style: Theme.of(context).textTheme.headlineMedium),
              Text(stat.$1),
            ],
          ),
        );
      },
    );
  }
}

class _TeacherActions extends StatelessWidget {
  const _TeacherActions();

  @override
  Widget build(BuildContext context) {
    const actions = [
      (
        Icons.add_circle_outline,
        'Video baharu',
        'Muat naik atau pautkan YouTube',
      ),
      (
        Icons.upload_file_outlined,
        'Bahan baharu',
        'Nota, PDF dan lembaran kerja',
      ),
      (Icons.quiz_outlined, 'Cipta kuiz', 'Bina kuiz interaktif'),
      (Icons.insights_outlined, 'Skor bakat saya', 'Lihat penglibatan murid'),
    ];

    return Column(
      children: [
        for (final action in actions) ...[
          SurfaceCard(
            child: Row(
              children: [
                DecoratedBox(
                  decoration: BoxDecoration(
                    color: LmsColors.brandSoft,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(11),
                    child: Icon(action.$1, color: LmsColors.brand),
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        action.$2,
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      const SizedBox(height: 2),
                      Text(action.$3),
                    ],
                  ),
                ),
                const Icon(Icons.chevron_right, color: LmsColors.inkMuted),
              ],
            ),
          ),
          const SizedBox(height: 10),
        ],
      ],
    );
  }
}

class _EmptyActivity extends StatelessWidget {
  const _EmptyActivity();

  @override
  Widget build(BuildContext context) {
    return const SurfaceCard(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.notifications_none_outlined, color: LmsColors.brand),
          SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Tiada aktiviti baharu',
                  style: TextStyle(
                    fontWeight: FontWeight.w800,
                    color: LmsColors.ink,
                  ),
                ),
                SizedBox(height: 4),
                Text(
                  'Data video, kuiz dan tontonan akan dipaparkan daripada API LMS.',
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
