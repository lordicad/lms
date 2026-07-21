import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../auth/auth_user.dart';
import '../theme/lms_theme.dart';

/// Shows a role-specific guide once per account on this device.
class RoleTutorial {
  const RoleTutorial._();

  static const _version = 'v1';

  static Future<void> showForNewUser(
    BuildContext context,
    AuthUser user,
  ) async {
    try {
      final preferences = await SharedPreferences.getInstance();
      final key = 'role_tutorial_${_version}_${user.role.name}_${user.id}';
      if (preferences.getBool(key) == true || !context.mounted) return;

      await Navigator.of(context).push<void>(
        MaterialPageRoute(
          fullscreenDialog: true,
          builder: (_) => RoleTutorialScreen(role: user.role),
        ),
      );
      await preferences.setBool(key, true);
    } catch (_) {
      // The guide is optional and must never block the user's session.
    }
  }
}

class RoleTutorialScreen extends StatelessWidget {
  const RoleTutorialScreen({super.key, required this.role});
  final UserRole role;

  @override
  Widget build(BuildContext context) {
    final isTeacher = role == UserRole.teacher;
    final guides = isTeacher
        ? const [
            ('1', 'Papan pemuka', 'Semak statistik dan aktiviti murid.'),
            ('2', 'Tambah kandungan', 'Cipta video, bahan atau kuiz baharu.'),
            ('3', 'Urus kandungan', 'Terbit, sembunyi atau kemas kini item.'),
            ('4', 'Pantau murid', 'Lihat Ranking dan kemajuan pembelajaran.'),
          ]
        : const [
            (
              '1',
              'Pilih subjek',
              'Buka Subjek untuk pilih mata pelajaran dan bab.',
            ),
            (
              '2',
              'Tonton dan belajar',
              'Sambung video atau terokai kandungan baharu.',
            ),
            (
              '3',
              'Simpan kegemaran',
              'Tekan hati untuk akses video dengan pantas.',
            ),
            ('4', 'Cuba kuiz', 'Jawab kuiz untuk semak kefahaman dan ranking.'),
          ];

    return Scaffold(
      appBar: AppBar(
        title: const Text('Panduan ringkas'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Langkau'),
          ),
        ],
      ),
      body: SafeArea(
        top: false,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(28, 32, 28, 28),
          children: [
            Icon(
              isTeacher ? Icons.school_rounded : Icons.waving_hand_rounded,
              size: 68,
              color: LmsColors.brand,
            ),
            const SizedBox(height: 14),
            Text(
              isTeacher ? 'Selamat datang, Cikgu!' : 'Jom mula belajar!',
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 25,
                fontWeight: FontWeight.w800,
                color: LmsColors.ink,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Empat perkara ringkas untuk membantu anda bermula.',
              textAlign: TextAlign.center,
              style: TextStyle(color: LmsColors.inkMuted),
            ),
            const SizedBox(height: 28),
            for (final guide in guides)
              Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: _GuideCard(
                  number: guide.$1,
                  title: guide.$2,
                  description: guide.$3,
                ),
              ),
            const SizedBox(height: 12),
            FilledButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Mula sekarang'),
            ),
          ],
        ),
      ),
    );
  }
}

class _GuideCard extends StatelessWidget {
  const _GuideCard({
    required this.number,
    required this.title,
    required this.description,
  });

  final String number;
  final String title;
  final String description;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(14),
    decoration: BoxDecoration(
      color: LmsColors.surface,
      border: Border.all(color: LmsColors.border),
      borderRadius: BorderRadius.circular(16),
    ),
    child: Row(
      children: [
        Container(
          width: 32,
          height: 32,
          decoration: BoxDecoration(
            color: LmsColors.brandSoft,
            shape: BoxShape.circle,
          ),
          alignment: Alignment.center,
          child: Text(
            number,
            style: const TextStyle(
              color: LmsColors.brandStrong,
              fontWeight: FontWeight.w800,
            ),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(
                  fontWeight: FontWeight.w800,
                  color: LmsColors.ink,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                description,
                style: const TextStyle(fontSize: 12, color: LmsColors.inkMuted),
              ),
            ],
          ),
        ),
      ],
    ),
  );
}
