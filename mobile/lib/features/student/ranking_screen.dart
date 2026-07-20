import 'package:flutter/material.dart';

import '../../core/content/content_models.dart';
import '../../core/content/content_repository.dart';
import '../../core/theme/lms_theme.dart';
import 'widgets/content_widgets.dart';

/// Student leaderboard for their Tahun. Top 10 with medals for the top three, and the
/// student's own row pinned at the bottom when they sit outside the Top 10.
class RankingScreen extends StatefulWidget {
  const RankingScreen({super.key, required this.repository});

  final ContentRepository repository;

  @override
  State<RankingScreen> createState() => _RankingScreenState();
}

class _RankingScreenState extends State<RankingScreen> {
  late Future<RankingData> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.repository.ranking();
  }

  void _reload() => setState(() {
    _future = widget.repository.ranking();
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Ranking')),
      body: FutureBuilder<RankingData>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return StateMessage(
              icon: Icons.wifi_off_outlined,
              title: 'Tidak dapat memuatkan ranking',
              subtitle: '${snapshot.error}',
              onRetry: _reload,
            );
          }

          final data = snapshot.data!;
          if (data.top.isEmpty && data.myRow == null) {
            return const StateMessage(
              icon: Icons.emoji_events_outlined,
              title: 'Belum ada kedudukan',
              subtitle: 'Jawab kuiz untuk kumpul mata dan naik ranking.',
            );
          }

          return Column(
            children: [
              Expanded(
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
                  children: [
                    if (data.gradeName != null)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: Text(
                          'Kedudukan • ${data.gradeName}',
                          style: Theme.of(context).textTheme.bodyMedium,
                        ),
                      ),
                    ...data.top.map((r) => _RankTile(row: r)),
                  ],
                ),
              ),
              if (data.showMyRow && data.myRow != null)
                _MyRowFooter(row: data.myRow!),
            ],
          );
        },
      ),
    );
  }
}

class _RankTile extends StatelessWidget {
  const _RankTile({required this.row});
  final RankRow row;

  @override
  Widget build(BuildContext context) {
    final medal = _medalColor(row.rank);

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: row.isMe ? LmsColors.brandSoft : LmsColors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: row.isMe ? LmsColors.brand : LmsColors.border,
          width: row.isMe ? 1.5 : 1,
        ),
      ),
      padding: const EdgeInsets.all(12),
      child: Row(
        children: [
          Container(
            width: 34,
            height: 34,
            decoration: BoxDecoration(
              color: medal ?? LmsColors.surfaceMuted,
              shape: BoxShape.circle,
            ),
            alignment: Alignment.center,
            child: Text(
              '${row.rank}',
              style: TextStyle(
                fontWeight: FontWeight.w800,
                color: medal != null ? Colors.white : LmsColors.inkMuted,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  row.isMe ? '${row.name} (anda)' : row.name,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 2),
                Text(
                  '${row.accuracy.toStringAsFixed(0)}% tepat · ${row.quizzes} kuiz',
                  style: const TextStyle(
                    fontSize: 11,
                    color: LmsColors.inkMuted,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Text(
            '${row.points}',
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: LmsColors.brandStrong,
            ),
          ),
          const Text(
            ' mata',
            style: TextStyle(fontSize: 11, color: LmsColors.inkMuted),
          ),
        ],
      ),
    );
  }

  static Color? _medalColor(int rank) => switch (rank) {
    1 => const Color(0xFFE0A422),
    2 => const Color(0xFF9AA0A6),
    3 => const Color(0xFFC0803F),
    _ => null,
  };
}

class _MyRowFooter extends StatelessWidget {
  const _MyRowFooter({required this.row});
  final RankRow row;

  @override
  Widget build(BuildContext context) {
    return Material(
      elevation: 8,
      color: LmsColors.surface,
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          child: Row(
            children: [
              Container(
                width: 34,
                height: 34,
                decoration: const BoxDecoration(
                  color: LmsColors.brand,
                  shape: BoxShape.circle,
                ),
                alignment: Alignment.center,
                child: Text(
                  '${row.rank}',
                  style: const TextStyle(
                    fontWeight: FontWeight.w800,
                    color: Colors.white,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Kedudukan anda',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: LmsColors.brandStrong,
                      ),
                    ),
                    Text(
                      '${row.accuracy.toStringAsFixed(0)}% tepat · ${row.quizzes} kuiz',
                      style: const TextStyle(
                        fontSize: 11,
                        color: LmsColors.inkMuted,
                      ),
                    ),
                  ],
                ),
              ),
              Text(
                '${row.points} mata',
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w800,
                  color: LmsColors.brandStrong,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
