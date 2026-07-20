import 'package:flutter/material.dart';

import '../../core/auth/auth_user.dart';
import '../../core/theme/lms_theme.dart';

class AppHeader extends StatelessWidget {
  const AppHeader({
    super.key,
    required this.user,
    required this.title,
    required this.subtitle,
    required this.onSignOut,
    this.onSearch,
  });

  final AuthUser user;
  final String title;
  final String subtitle;
  final Future<void> Function() onSignOut;
  final VoidCallback? onSearch;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
      child: Row(
        children: [
          const DecoratedBox(
            decoration: BoxDecoration(
              color: LmsColors.brand,
              borderRadius: BorderRadius.all(Radius.circular(9)),
            ),
            child: Padding(
              padding: EdgeInsets.symmetric(horizontal: 8, vertical: 5),
              child: Text(
                'W',
                style: TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
          ),
          const SizedBox(width: 8),
          const Text(
            'WeLearn',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
          ),
          const Spacer(),
          if (onSearch != null)
            IconButton(
              tooltip: 'Cari kandungan',
              onPressed: onSearch,
              icon: const Icon(Icons.search_rounded),
            ),
          PopupMenuButton<String>(
            tooltip: 'Menu akaun',
            onSelected: (value) {
              if (value == 'logout') {
                onSignOut();
              }
            },
            itemBuilder: (context) => const [
              PopupMenuItem(value: 'logout', child: Text('Log keluar')),
            ],
            child: CircleAvatar(
              backgroundColor: LmsColors.brandSoft,
              foregroundColor: LmsColors.brand,
              child: Text(_initials(user.name)),
            ),
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
