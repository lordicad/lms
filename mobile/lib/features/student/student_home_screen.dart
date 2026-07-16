import 'package:flutter/material.dart';

import '../../core/auth/auth_user.dart';
import '../../core/theme/lms_theme.dart';
import '../../shared/widgets/app_header.dart';
import '../../shared/widgets/surface_card.dart';

class StudentHomeScreen extends StatelessWidget {
  const StudentHomeScreen({
    super.key,
    required this.user,
    required this.onSignOut,
  });

  final AuthUser user;
  final Future<void> Function() onSignOut;

  @override
  Widget build(BuildContext context) {
    final gradeName = user.grade?.name ?? 'Tahun anda';

    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            AppHeader(
              user: user,
              title: 'Belajar',
              subtitle: gradeName,
              onSignOut: onSignOut,
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 20, 20, 32),
                children: [
                  Text(
                    'Hai, ${_firstName(user.name)}!',
                    style: Theme.of(context).textTheme.headlineLarge,
                  ),
                  const SizedBox(height: 6),
                  Text('Mari teruskan pembelajaran untuk $gradeName.'),
                  const SizedBox(height: 24),
                  _ContinueLearningCard(gradeName: gradeName),
                  const SizedBox(height: 28),
                  Text('Teroka', style: Theme.of(context).textTheme.titleLarge),
                  const SizedBox(height: 12),
                  const _StudentActions(),
                  const SizedBox(height: 28),
                  Text(
                    'Untuk anda',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const SizedBox(height: 12),
                  const _EmptyRecommendations(),
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
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home),
            label: 'Belajar',
          ),
          NavigationDestination(
            icon: Icon(Icons.bookmark_outline),
            selectedIcon: Icon(Icons.bookmark),
            label: 'Simpanan',
          ),
          NavigationDestination(
            icon: Icon(Icons.emoji_events_outlined),
            selectedIcon: Icon(Icons.emoji_events),
            label: 'Ranking',
          ),
        ],
      ),
    );
  }

  static String _firstName(String name) =>
      name.trim().split(RegExp(r'\s+')).first;
}

class _ContinueLearningCard extends StatelessWidget {
  const _ContinueLearningCard({required this.gradeName});

  final String gradeName;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: LmsColors.brand,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Row(
          children: [
            const DecoratedBox(
              decoration: BoxDecoration(
                color: Color(0x33FFFFFF),
                shape: BoxShape.circle,
              ),
              child: Padding(
                padding: EdgeInsets.all(14),
                child: Icon(
                  Icons.play_arrow_rounded,
                  color: Colors.white,
                  size: 28,
                ),
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Sambung belajar',
                    style: TextStyle(
                      color: Colors.white70,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Video dan kuiz untuk $gradeName akan muncul di sini.',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _StudentActions extends StatelessWidget {
  const _StudentActions();

  @override
  Widget build(BuildContext context) {
    const actions = [
      (Icons.menu_book_outlined, 'Subjek'),
      (Icons.favorite_border, 'Kegemaran'),
      (Icons.quiz_outlined, 'Kuiz Saya'),
      (Icons.download_outlined, 'Simpanan'),
    ];

    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: actions.length,
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        mainAxisSpacing: 12,
        crossAxisSpacing: 12,
        childAspectRatio: 1.75,
      ),
      itemBuilder: (context, index) {
        final action = actions[index];
        return SurfaceCard(
          child: Row(
            children: [
              DecoratedBox(
                decoration: BoxDecoration(
                  color: LmsColors.brandSoft,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(10),
                  child: Icon(action.$1, color: LmsColors.brand),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  action.$2,
                  style: Theme.of(context).textTheme.titleMedium,
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

class _EmptyRecommendations extends StatelessWidget {
  const _EmptyRecommendations();

  @override
  Widget build(BuildContext context) {
    return const SurfaceCard(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.auto_awesome_outlined, color: LmsColors.brand),
          SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Belum ada cadangan',
                  style: TextStyle(
                    fontWeight: FontWeight.w800,
                    color: LmsColors.ink,
                  ),
                ),
                SizedBox(height: 4),
                Text(
                  'Kandungan daripada sistem LMS akan dimuatkan dalam langkah seterusnya.',
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
