import 'package:flutter/material.dart';

import '../../core/auth/auth_user.dart';
import '../../core/theme/lms_theme.dart';
import '../../core/widgets/lms_logo.dart';

class AppHeader extends StatelessWidget {
  const AppHeader({
    super.key,
    required this.user,
    required this.gradeLabel,
    required this.onSelectGrade,
    required this.onProfile,
    this.onSearch,
  });

  final AuthUser user;
  final String gradeLabel;
  final VoidCallback onSelectGrade;
  final VoidCallback onProfile;
  final VoidCallback? onSearch;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
      child: Row(
        children: [
          const LmsLogo(size: 42, radius: 13),
          const SizedBox(width: 8),
          const Text(
            'WeLearn',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
          ),
          const Spacer(),
          TextButton.icon(
            onPressed: onSelectGrade,
            icon: const Icon(Icons.school_outlined, size: 17),
            label: Text(gradeLabel),
            style: TextButton.styleFrom(
              foregroundColor: LmsColors.ink,
              backgroundColor: LmsColors.surface,
              minimumSize: const Size(0, 42),
              side: const BorderSide(color: LmsColors.border),
            ),
          ),
          const SizedBox(width: 4),
          if (onSearch != null)
            IconButton(
              tooltip: 'Cari kandungan',
              onPressed: onSearch,
              icon: const Icon(Icons.search_rounded),
            ),
          IconButton(
            tooltip: 'Profil',
            onPressed: onProfile,
            icon: _Avatar(user: user),
          ),
        ],
      ),
    );
  }

  static String _initials(String name) {
    final words = name.trim().split(RegExp(r'\s+'));
    return words
        .take(2)
        .map((word) => word.isEmpty ? '' : word[0])
        .join()
        .toUpperCase();
  }
}

class _Avatar extends StatelessWidget {
  const _Avatar({required this.user});
  final AuthUser user;

  @override
  Widget build(BuildContext context) {
    final url = user.avatarUrl;
    return CircleAvatar(
      radius: 17,
      backgroundColor: LmsColors.brandSoft,
      foregroundColor: LmsColors.brandStrong,
      backgroundImage: url == null || url.isEmpty ? null : NetworkImage(url),
      onBackgroundImageError: url == null || url.isEmpty ? null : (_, _) {},
      child: url == null || url.isEmpty
          ? Text(AppHeader._initials(user.name))
          : null,
    );
  }
}
