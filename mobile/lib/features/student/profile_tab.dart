import 'package:flutter/material.dart';

import '../../core/auth/auth_user.dart';
import '../../core/theme/lms_theme.dart';

/// The student's profile: identity, Tahun, and a confirmed sign-out.
class ProfileTab extends StatelessWidget {
  const ProfileTab({super.key, required this.user, required this.onSignOut});

  final AuthUser user;
  final Future<void> Function() onSignOut;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 24, 20, 32),
      children: [
        Column(
          children: [
            CircleAvatar(
              radius: 40,
              backgroundColor: LmsColors.brandSoft,
              foregroundColor: LmsColors.brand,
              child: Text(
                _initials(user.name),
                style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w800),
              ),
            ),
            const SizedBox(height: 14),
            Text(user.name,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.headlineMedium),
            const SizedBox(height: 4),
            Text('@${user.username}', style: const TextStyle(color: LmsColors.inkMuted)),
          ],
        ),
        const SizedBox(height: 24),
        Container(
          decoration: BoxDecoration(
            color: LmsColors.surface,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: LmsColors.border),
          ),
          child: Column(
            children: [
              _InfoRow(icon: Icons.school_rounded, label: 'Tahun', value: user.grade?.name ?? '—'),
              if (user.email != null) ...[
                const Divider(height: 1),
                _InfoRow(icon: Icons.mail_outline_rounded, label: 'Emel', value: user.email!),
              ],
              const Divider(height: 1),
              const _InfoRow(icon: Icons.badge_outlined, label: 'Peranan', value: 'Murid'),
            ],
          ),
        ),
        const SizedBox(height: 24),
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
        content: const Text('Anda perlu log masuk semula untuk menggunakan aplikasi.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Log keluar')),
        ],
      ),
    );
    if (go == true) await onSignOut();
  }

  static String _initials(String name) {
    final words = name.trim().split(RegExp(r'\s+'));
    return words.take(2).map((w) => w.isEmpty ? '' : w[0]).join().toUpperCase();
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.icon, required this.label, required this.value});
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
              style: const TextStyle(fontWeight: FontWeight.w700, color: LmsColors.ink),
            ),
          ),
        ],
      ),
    );
  }
}
