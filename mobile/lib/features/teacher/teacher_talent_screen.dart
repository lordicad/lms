import 'package:flutter/material.dart';

import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../student/widgets/content_widgets.dart';

/// Read-only engagement overview for a teacher's own content.
class TeacherTalentScreen extends StatefulWidget {
  const TeacherTalentScreen({super.key, required this.repository});

  final TeacherRepository repository;

  @override
  State<TeacherTalentScreen> createState() => _TeacherTalentScreenState();
}

class _TeacherTalentScreenState extends State<TeacherTalentScreen> {
  late Future<TeacherTalentData> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.repository.talent();
  }

  void _reload() => setState(() => _future = widget.repository.talent());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Bakat Kandungan')),
      body: FutureBuilder<TeacherTalentData>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return StateMessage(
              icon: Icons.wifi_off_outlined,
              title: 'Tidak dapat memuatkan Bakat Kandungan',
              subtitle: '${snapshot.error}',
              onRetry: _reload,
            );
          }
          return _TalentBody(data: snapshot.data!);
        },
      ),
    );
  }
}

class _TalentBody extends StatelessWidget {
  const _TalentBody({required this.data});
  final TeacherTalentData data;

  @override
  Widget build(BuildContext context) {
    final signal = data.signal;
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
      children: [
        _SignalCard(signal: signal),
        const SizedBox(height: 22),
        Text(
          'Ringkasan penglibatan',
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: 10),
        Wrap(
          spacing: 10,
          runSpacing: 10,
          children: [
            _Metric(
              icon: Icons.visibility_outlined,
              label: 'Tontonan',
              value: data.stats.views,
            ),
            _Metric(
              icon: Icons.favorite_border_rounded,
              label: 'Kegemaran',
              value: data.stats.favourites,
            ),
            _Metric(
              icon: Icons.file_download_outlined,
              label: 'Muat turun',
              value: data.stats.downloads,
            ),
            _Metric(
              icon: Icons.quiz_outlined,
              label: 'Percubaan',
              value: data.stats.attempts,
            ),
          ],
        ),
        const SizedBox(height: 26),
        Text(
          'Kandungan paling mendapat sambutan',
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: 10),
        for (final board in data.leaderboards) _Leaderboard(board),
      ],
    );
  }
}

class _SignalCard extends StatelessWidget {
  const _SignalCard({required this.signal});
  final TeacherTalentSignal signal;

  @override
  Widget build(BuildContext context) {
    final headline = signal.headline;
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [LmsColors.forest, LmsColors.brandStrong],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'SKOR BAKAT KANDUNGAN',
            style: TextStyle(
              color: Color(0xFFD0E1CD),
              fontSize: 11,
              fontWeight: FontWeight.w800,
              letterSpacing: .5,
            ),
          ),
          const SizedBox(height: 5),
          Text(
            signal.sufficient && headline != null
                ? '${headline.toStringAsFixed(1)}/100'
                : 'Data belum mencukupi',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 26,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 5),
          Text(
            signal.sufficient
                ? '${signal.engagedStudents} murid telah terlibat dengan kandungan anda.'
                : 'Skor akan muncul apabila data penglibatan murid mencukupi.',
            style: const TextStyle(color: Color(0xFFD0E1CD), height: 1.4),
          ),
          const SizedBox(height: 16),
          Wrap(
            spacing: 12,
            runSpacing: 6,
            children: [
              _SignalLabel('Penglibatan', signal.engagement.toStringAsFixed(1)),
              _SignalLabel('Kualiti', '${signal.quality.toStringAsFixed(1)}%'),
              _SignalLabel('Liputan', '${signal.breadth} bab'),
              if (signal.outcome != null)
                _SignalLabel('Hasil', '${signal.outcome!.toStringAsFixed(1)}%'),
            ],
          ),
        ],
      ),
    );
  }
}

class _SignalLabel extends StatelessWidget {
  const _SignalLabel(this.label, this.value);
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Text(
    '$label: $value',
    style: const TextStyle(
      color: Color(0xFFE8F3E5),
      fontSize: 11.5,
      fontWeight: FontWeight.w700,
    ),
  );
}

class _Metric extends StatelessWidget {
  const _Metric({required this.icon, required this.label, required this.value});
  final IconData icon;
  final String label;
  final int value;

  @override
  Widget build(BuildContext context) => Container(
    width: 145,
    padding: const EdgeInsets.all(13),
    decoration: BoxDecoration(
      color: LmsColors.surface,
      border: Border.all(color: LmsColors.border),
      borderRadius: BorderRadius.circular(14),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 19, color: LmsColors.brand),
        const SizedBox(height: 8),
        Text('$value', style: Theme.of(context).textTheme.titleLarge),
        Text(
          label,
          style: const TextStyle(fontSize: 11.5, color: LmsColors.inkMuted),
        ),
      ],
    ),
  );
}

class _Leaderboard extends StatelessWidget {
  const _Leaderboard(this.board);
  final TeacherTalentLeaderboard board;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: LmsColors.surface,
        border: Border.all(color: LmsColors.border),
        borderRadius: BorderRadius.circular(15),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 14, 14, 8),
            child: Text(
              board.title,
              style: const TextStyle(fontWeight: FontWeight.w800),
            ),
          ),
          if (board.items.isEmpty)
            const Padding(
              padding: EdgeInsets.fromLTRB(14, 2, 14, 14),
              child: Text(
                'Belum ada data.',
                style: TextStyle(color: LmsColors.inkMuted),
              ),
            )
          else
            for (var index = 0; index < board.items.length; index++)
              _LeaderboardItem(item: board.items[index], rank: index + 1),
        ],
      ),
    );
  }
}

class _LeaderboardItem extends StatelessWidget {
  const _LeaderboardItem({required this.item, required this.rank});
  final TeacherTalentItem item;
  final int rank;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.fromLTRB(14, 8, 14, 12),
    child: Row(
      children: [
        SizedBox(
          width: 25,
          child: Text(
            '$rank',
            style: const TextStyle(
              fontWeight: FontWeight.w800,
              color: LmsColors.inkMuted,
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
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
              Text(
                [
                  if (item.subjectName != null) item.subjectName!,
                  if (item.chapterLabel != null) item.chapterLabel!,
                ].join(' · '),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontSize: 11.5,
                  color: LmsColors.inkMuted,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(width: 8),
        Text(
          '${item.value}',
          style: const TextStyle(
            fontWeight: FontWeight.w800,
            color: LmsColors.brandStrong,
          ),
        ),
      ],
    ),
  );
}
