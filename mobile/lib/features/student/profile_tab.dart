import 'package:flutter/material.dart';

import '../../core/auth/auth_user.dart';
import '../../core/platform/native_file_picker.dart';
import '../../core/theme/lms_theme.dart';
import '../../core/widgets/lms_logo.dart';
import 'profile_edit_screen.dart';

/// The student's profile: identity, Tahun, and a confirmed sign-out.
class ProfileTab extends StatelessWidget {
  const ProfileTab({
    super.key,
    required this.user,
    required this.onSignOut,
    required this.onUpdateProfile,
    required this.onUpdateAvatar,
    this.roleLabel = 'Murid',
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
  final String roleLabel;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 24, 20, 32),
      children: [
        Text('Profil saya', style: Theme.of(context).textTheme.headlineMedium),
        const SizedBox(height: 14),
        _ProfileHero(user: user, roleLabel: roleLabel),
        const SizedBox(height: 24),
        const Text(
          'Maklumat akaun',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w800,
            color: LmsColors.ink,
          ),
        ),
        const SizedBox(height: 10),
        Container(
          decoration: BoxDecoration(
            color: LmsColors.surface,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: LmsColors.border),
          ),
          child: Column(
            children: [
              _InfoRow(
                icon: Icons.school_rounded,
                label: 'Tahun',
                value: user.grade?.name ?? '—',
              ),
              if (user.email != null) ...[
                const Divider(height: 1),
                _InfoRow(
                  icon: Icons.mail_outline_rounded,
                  label: 'Emel',
                  value: user.email!,
                ),
              ],
              const Divider(height: 1),
              _InfoRow(
                icon: Icons.badge_outlined,
                label: 'Peranan',
                value: roleLabel,
              ),
            ],
          ),
        ),
        const SizedBox(height: 26),
        OutlinedButton.icon(
          onPressed: () => _openEditProfile(context),
          icon: const Icon(Icons.edit_outlined),
          label: const Text('Sunting profil'),
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
          label: const Text('Log keluar'),
          style: OutlinedButton.styleFrom(
            foregroundColor: LmsColors.danger,
            minimumSize: const Size.fromHeight(50),
            side: const BorderSide(color: Color(0x33B91C1C)),
          ),
        ),
      ],
    );
  }

  Future<void> _confirmSignOut(BuildContext context) async {
    final go = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Log keluar?'),
        content: const Text(
          'Anda perlu log masuk semula untuk menggunakan aplikasi.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Log keluar'),
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
          Text(label, style: const TextStyle(color: LmsColors.inkMuted)),
          const Spacer(),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: const TextStyle(
                fontWeight: FontWeight.w700,
                color: LmsColors.ink,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
