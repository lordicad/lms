import 'package:flutter/material.dart';

import '../../core/auth/auth_user.dart';
import '../../core/platform/native_file_picker.dart';
import '../../core/settings/app_settings.dart';
import '../../core/theme/lms_theme.dart';
import '../../core/widgets/lms_logo.dart';
import 'profile_edit_screen.dart';

String _profileCopy(BuildContext context, String bm) {
  const english = {
    'Tahun': 'Year',
    'Sekolah': 'School',
    'Kelas': 'Class',
    'Guru kelas': 'Homeroom teacher',
    'Penjaga': 'Guardian',
    'Telefon penjaga': 'Guardian phone',
    'Emel penjaga': 'Guardian email',
    'Jawatan': 'Position',
    'Telefon': 'Phone',
    'Subjek': 'Subjects',
    'Emel': 'Email',
    'Peranan': 'Role',
  };
  return context.copy(bm: bm, en: english[bm] ?? bm);
}

/// The student's profile: identity, Tahun, and a confirmed sign-out.
class ProfileTab extends StatelessWidget {
  const ProfileTab({
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
    this.roleLabel = 'Murid',
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
  final String roleLabel;

  @override
  Widget build(BuildContext context) {
    final english = language == AppLanguage.en;
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 24, 20, 32),
      children: [
        Text(
          english ? 'My profile' : 'Profil saya',
          style: Theme.of(context).textTheme.headlineMedium,
        ),
        const SizedBox(height: 14),
        _ProfileHero(user: user, roleLabel: roleLabel),
        if (user.role == UserRole.student && user.studentStats != null) ...[
          const SizedBox(height: 20),
          _StudentLearningSummary(stats: user.studentStats!),
        ],
        const SizedBox(height: 24),
        Text(
          english ? 'Account information' : 'Maklumat akaun',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w800,
            color: LmsPalette.text(context),
          ),
        ),
        const SizedBox(height: 10),
        Container(
          decoration: BoxDecoration(
            color: LmsPalette.surface(context),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: LmsPalette.border(context)),
          ),
          child: Column(
            children: [
              if (user.role == UserRole.student)
                _InfoRow(
                  icon: Icons.school_rounded,
                  label: _profileCopy(context, 'Tahun'),
                  value: user.grade?.name ?? '—',
                ),
              if (user.role == UserRole.student && user.school != null) ...[
                const Divider(height: 1),
                _InfoRow(
                  icon: Icons.account_balance_outlined,
                  label: _profileCopy(context, 'Sekolah'),
                  value: user.school!.name,
                ),
              ],
              if (user.role == UserRole.student &&
                  user.schoolClass != null) ...[
                const Divider(height: 1),
                _InfoRow(
                  icon: Icons.groups_outlined,
                  label: _profileCopy(context, 'Kelas'),
                  value: user.schoolClass!.label,
                ),
              ],
              if (user.role == UserRole.student &&
                  user.schoolClass?.homeroomTeacherName != null) ...[
                const Divider(height: 1),
                _InfoRow(
                  icon: Icons.person_outline_rounded,
                  label: _profileCopy(context, 'Guru kelas'),
                  value: user.schoolClass!.homeroomTeacherName!,
                ),
              ],
              if (user.role == UserRole.student &&
                  user.guardianName != null) ...[
                const Divider(height: 1),
                _InfoRow(
                  icon: Icons.family_restroom_outlined,
                  label: _profileCopy(context, 'Penjaga'),
                  value: user.guardianName!,
                ),
              ],
              if (user.role == UserRole.student &&
                  user.guardianPhone != null) ...[
                const Divider(height: 1),
                _InfoRow(
                  icon: Icons.phone_outlined,
                  label: _profileCopy(context, 'Telefon penjaga'),
                  value: user.guardianPhone!,
                ),
              ],
              if (user.role == UserRole.student &&
                  user.guardianEmail != null) ...[
                const Divider(height: 1),
                _InfoRow(
                  icon: Icons.alternate_email_rounded,
                  label: _profileCopy(context, 'Emel penjaga'),
                  value: user.guardianEmail!,
                ),
              ],
              if (user.role == UserRole.teacher && user.school != null) ...[
                const Divider(height: 1),
                _InfoRow(
                  icon: Icons.account_balance_outlined,
                  label: _profileCopy(context, 'Sekolah'),
                  value: user.school!.name,
                ),
              ],
              if (user.role == UserRole.teacher && user.position != null) ...[
                const Divider(height: 1),
                _InfoRow(
                  icon: Icons.work_outline_rounded,
                  label: _profileCopy(context, 'Jawatan'),
                  value: user.position!,
                ),
              ],
              if (user.role == UserRole.teacher && user.phone != null) ...[
                const Divider(height: 1),
                _InfoRow(
                  icon: Icons.phone_outlined,
                  label: _profileCopy(context, 'Telefon'),
                  value: user.phone!,
                ),
              ],
              if (user.role == UserRole.teacher &&
                  user.homeroomClass != null) ...[
                const Divider(height: 1),
                _InfoRow(
                  icon: Icons.groups_outlined,
                  label: _profileCopy(context, 'Guru kelas'),
                  value: user.homeroomClass!.label,
                ),
              ],
              if (user.role == UserRole.teacher &&
                  user.subjects.isNotEmpty) ...[
                const Divider(height: 1),
                _InfoRow(
                  icon: Icons.menu_book_outlined,
                  label: _profileCopy(context, 'Subjek'),
                  value: user.subjects
                      .map((subject) => subject.name)
                      .join(', '),
                ),
              ],
              if (user.email != null) ...[
                const Divider(height: 1),
                _InfoRow(
                  icon: Icons.mail_outline_rounded,
                  label: _profileCopy(context, 'Emel'),
                  value: user.email!,
                ),
              ],
              const Divider(height: 1),
              _InfoRow(
                icon: Icons.badge_outlined,
                label: _profileCopy(context, 'Peranan'),
                value: roleLabel,
              ),
            ],
          ),
        ),
        const SizedBox(height: 26),
        OutlinedButton.icon(
          onPressed: () => _openEditProfile(context),
          icon: const Icon(Icons.edit_outlined),
          label: Text(english ? 'Edit profile' : 'Sunting profil'),
          style: OutlinedButton.styleFrom(
            foregroundColor: LmsColors.brandStrong,
            minimumSize: const Size.fromHeight(50),
            side: const BorderSide(color: Color(0x334A7C3A)),
          ),
        ),
        const SizedBox(height: 12),
        OutlinedButton.icon(
          onPressed: () => _openAppSettings(context),
          icon: const Icon(Icons.tune_rounded),
          label: Text(
            '${english ? 'Display & language' : 'Paparan & bahasa'}  ·  ${themeMode == ThemeMode.dark ? 'Dark' : 'Light'} · ${language.shortLabel}',
          ),
          style: OutlinedButton.styleFrom(
            foregroundColor: LmsColors.brandStrong,
            minimumSize: const Size.fromHeight(50),
            side: const BorderSide(color: Color(0x334A7C3A)),
          ),
        ),
        const SizedBox(height: 12),
        OutlinedButton.icon(
          onPressed: () => _confirmSignOut(context),
          icon: const Icon(Icons.logout_rounded),
          label: Text(english ? 'Sign out' : 'Log keluar'),
          style: OutlinedButton.styleFrom(
            foregroundColor: LmsColors.danger,
            minimumSize: const Size.fromHeight(50),
            side: const BorderSide(color: Color(0x33B91C1C)),
          ),
        ),
      ],
    );
  }

  Future<void> _openAppSettings(BuildContext context) async {
    var selectedTheme = themeMode;
    var selectedLanguage = language;

    await showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      builder: (sheetContext) => StatefulBuilder(
        builder: (context, setSheetState) => SafeArea(
          top: false,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(20, 4, 20, 28),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  selectedLanguage == AppLanguage.en
                      ? 'Display & language'
                      : 'Paparan & bahasa',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 16),
                Text(
                  selectedLanguage == AppLanguage.en ? 'Theme' : 'Tema',
                  style: TextStyle(fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 6),
                SegmentedButton<ThemeMode>(
                  segments: const [
                    ButtonSegment(
                      value: ThemeMode.light,
                      icon: Icon(Icons.light_mode_outlined),
                      label: Text('Light'),
                    ),
                    ButtonSegment(
                      value: ThemeMode.dark,
                      icon: Icon(Icons.dark_mode_outlined),
                      label: Text('Dark'),
                    ),
                  ],
                  selected: {selectedTheme},
                  onSelectionChanged: (value) async {
                    final mode = value.first;
                    setSheetState(() => selectedTheme = mode);
                    await onThemeModeChanged(mode);
                  },
                  showSelectedIcon: false,
                ),
                const SizedBox(height: 20),
                Text(
                  selectedLanguage == AppLanguage.en ? 'Language' : 'Bahasa',
                  style: TextStyle(fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 6),
                SegmentedButton<AppLanguage>(
                  segments: const [
                    ButtonSegment(
                      value: AppLanguage.bm,
                      label: Text('Bahasa Melayu'),
                    ),
                    ButtonSegment(
                      value: AppLanguage.en,
                      label: Text('English'),
                    ),
                  ],
                  selected: {selectedLanguage},
                  onSelectionChanged: (value) async {
                    final selected = value.first;
                    setSheetState(() => selectedLanguage = selected);
                    await onLanguageChanged(selected);
                  },
                  showSelectedIcon: false,
                ),
                const SizedBox(height: 12),
                Text(
                  selectedLanguage == AppLanguage.bm
                      ? 'Pilihan disimpan pada peranti ini.'
                      : 'Your preference is saved on this device.',
                  style: const TextStyle(
                    fontSize: 12,
                    color: LmsColors.inkMuted,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _confirmSignOut(BuildContext context) async {
    final go = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(language == AppLanguage.en ? 'Sign out?' : 'Log keluar?'),
        content: Text(
          language == AppLanguage.en
              ? 'You need to sign in again to use the app.'
              : 'Anda perlu log masuk semula untuk menggunakan aplikasi.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(language == AppLanguage.en ? 'Cancel' : 'Batal'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(language == AppLanguage.en ? 'Sign out' : 'Log keluar'),
          ),
        ],
      ),
    );
    if (go == true) await onSignOut();
  }

  Future<void> _openEditProfile(BuildContext context) async {
    final updated = await Navigator.of(context).push<AuthUser>(
      MaterialPageRoute(
        builder: (_) => ProfileEditScreen(
          user: user,
          roleLabel: roleLabel,
          loadOptions: loadProfileOptions,
          onSave: onUpdateProfile,
          onSaveAvatar: onUpdateAvatar,
        ),
      ),
    );

    if (updated != null && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Profil berjaya dikemas kini.')),
      );
    }
  }
}

/// The same earned-learning summary shown on the web profile. All values
/// come from the authenticated user payload, rather than local counters.
class _StudentLearningSummary extends StatelessWidget {
  const _StudentLearningSummary({required this.stats});

  final StudentProfileStats stats;

  @override
  Widget build(BuildContext context) {
    final badges = [
      _StudentBadge(
        icon: Icons.local_fire_department_outlined,
        title: 'Rajin Belajar',
        caption: '${stats.quizzes}/5 kuiz selesai',
        earned: stats.quizzes >= 5,
        color: const Color(0xFFE78A24),
      ),
      _StudentBadge(
        icon: Icons.gps_fixed_rounded,
        title: 'Markah Penuh',
        caption: '100% dalam kuiz',
        earned: stats.perfect,
        color: const Color(0xFF397FD6),
      ),
      _StudentBadge(
        icon: Icons.ondemand_video_outlined,
        title: 'Penonton Setia',
        caption: '${stats.videos}/25 video ditonton',
        earned: stats.videos >= 25,
        color: const Color(0xFF7B5CCB),
      ),
      _StudentBadge(
        icon: Icons.workspace_premium_outlined,
        title: 'Top 10',
        caption: 'Capai ranking top 10',
        earned: stats.rank != null && stats.rank! <= 10,
        color: const Color(0xFFB37A18),
      ),
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          context.copy(bm: 'Kemajuan pembelajaran', en: 'Learning progress'),
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w800,
            color: LmsColors.ink,
          ),
        ),
        const SizedBox(height: 10),
        LayoutBuilder(
          builder: (context, constraints) {
            final wide = constraints.maxWidth >= 580;
            return GridView.count(
              crossAxisCount: wide ? 4 : 2,
              crossAxisSpacing: 10,
              mainAxisSpacing: 10,
              childAspectRatio: wide ? 1.22 : 1.75,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              children: [
                _StudentStatCard(
                  icon: Icons.stars_rounded,
                  label: 'Mata',
                  value: '${stats.points}',
                  color: const Color(0xFFB37A18),
                ),
                _StudentStatCard(
                  icon: Icons.quiz_outlined,
                  label: 'Kuiz selesai',
                  value: '${stats.quizzes}',
                  color: const Color(0xFF397FD6),
                ),
                _StudentStatCard(
                  icon: Icons.play_circle_outline_rounded,
                  label: 'Video ditonton',
                  value: '${stats.videos}',
                  color: LmsColors.brandStrong,
                ),
                _StudentStatCard(
                  icon: Icons.emoji_events_outlined,
                  label: 'Kedudukan',
                  value: stats.rank == null ? '—' : '#${stats.rank}',
                  color: const Color(0xFF7B5CCB),
                ),
              ],
            );
          },
        ),
        const SizedBox(height: 20),
        Text(
          context.copy(bm: 'Lencana saya', en: 'My badges'),
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w800,
            color: LmsColors.ink,
          ),
        ),
        const SizedBox(height: 10),
        LayoutBuilder(
          builder: (context, constraints) {
            final itemWidth = (constraints.maxWidth - 10) / 2;
            return Wrap(
              spacing: 10,
              runSpacing: 10,
              children: badges
                  .map((badge) => SizedBox(width: itemWidth, child: badge))
                  .toList(growable: false),
            );
          },
        ),
      ],
    );
  }
}

class _StudentStatCard extends StatelessWidget {
  const _StudentStatCard({
    required this.icon,
    required this.label,
    required this.value,
    required this.color,
  });

  final IconData icon;
  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(13),
      decoration: BoxDecoration(
        color: LmsPalette.surface(context),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: LmsPalette.border(context)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Container(
            padding: const EdgeInsets.all(7),
            decoration: BoxDecoration(
              color: color.withValues(alpha: .12),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: color, size: 18),
          ),
          Text(
            value,
            style: TextStyle(
              color: LmsPalette.text(context),
              fontSize: 19,
              fontWeight: FontWeight.w800,
            ),
          ),
          Text(
            label,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(fontSize: 10.5, color: LmsPalette.muted(context)),
          ),
        ],
      ),
    );
  }
}

class _StudentBadge extends StatelessWidget {
  const _StudentBadge({
    required this.icon,
    required this.title,
    required this.caption,
    required this.earned,
    required this.color,
  });

  final IconData icon;
  final String title;
  final String caption;
  final bool earned;
  final Color color;

  @override
  Widget build(BuildContext context) {
    final iconColor = earned ? color : LmsPalette.muted(context);
    return Opacity(
      opacity: earned ? 1 : .52,
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: LmsPalette.surface(context),
          borderRadius: BorderRadius.circular(15),
          border: Border.all(
            color: earned
                ? color.withValues(alpha: .25)
                : LmsPalette.border(context),
          ),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: iconColor.withValues(alpha: .12),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: iconColor, size: 18),
            ),
            const SizedBox(width: 9),
            Expanded(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontSize: 11.5,
                      fontWeight: FontWeight.w800,
                      color: LmsPalette.text(context),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    caption,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontSize: 9.5,
                      color: LmsPalette.muted(context),
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

class _ProfileHero extends StatelessWidget {
  const _ProfileHero({required this.user, required this.roleLabel});

  final AuthUser user;
  final String roleLabel;

  @override
  Widget build(BuildContext context) {
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        gradient: const LinearGradient(
          colors: [LmsColors.forest, LmsColors.brandStrong],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        boxShadow: const [
          BoxShadow(
            color: Color(0x1E1B3520),
            blurRadius: 18,
            offset: Offset(0, 8),
          ),
        ],
      ),
      padding: const EdgeInsets.all(18),
      child: Stack(
        children: [
          Positioned(
            right: -28,
            top: -52,
            child: Container(
              height: 134,
              width: 134,
              decoration: const BoxDecoration(
                color: Color(0x1699C883),
                shape: BoxShape.circle,
              ),
            ),
          ),
          Row(
            children: [
              _ProfileAvatar(user: user),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      user.name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      '@${user.username}',
                      style: const TextStyle(
                        color: Color(0xFFD0E1CD),
                        fontSize: 11.5,
                      ),
                    ),
                    const SizedBox(height: 10),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 9,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0x22FFFFFF),
                        borderRadius: BorderRadius.circular(999),
                        border: Border.all(color: const Color(0x35FFFFFF)),
                      ),
                      child: Text(
                        roleLabel,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 10.5,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _ProfileAvatar extends StatelessWidget {
  const _ProfileAvatar({required this.user});
  final AuthUser user;

  @override
  Widget build(BuildContext context) {
    final url = user.avatarUrl;
    if (url == null || url.isEmpty) {
      return const LmsLogo(size: 58, radius: 18);
    }
    return ClipRRect(
      borderRadius: BorderRadius.circular(18),
      child: Image.network(
        url,
        width: 58,
        height: 58,
        fit: BoxFit.cover,
        errorBuilder: (_, _, _) => const LmsLogo(size: 58, radius: 18),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({
    required this.icon,
    required this.label,
    required this.value,
  });
  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      child: Row(
        children: [
          Icon(icon, color: LmsColors.brand, size: 22),
          const SizedBox(width: 14),
          Text(label, style: TextStyle(color: LmsPalette.muted(context))),
          const Spacer(),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: TextStyle(
                fontWeight: FontWeight.w700,
                color: LmsPalette.text(context),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
