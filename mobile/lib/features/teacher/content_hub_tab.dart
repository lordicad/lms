import 'package:flutter/material.dart';

import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../student/widgets/content_widgets.dart';

/// The teacher's Content Hub: a segmented list of their videos, materials and quizzes
/// with publish status. Read-only for now (edit/publish/delete arrive later).
class ContentHubTab extends StatefulWidget {
  const ContentHubTab({super.key, required this.repository});

  final TeacherRepository repository;

  @override
  State<ContentHubTab> createState() => _ContentHubTabState();
}

class _ContentHubTabState extends State<ContentHubTab> {
  int _segment = 0;
  Future<List<TeacherVideo>>? _videos;
  Future<List<TeacherMaterial>>? _materials;
  Future<List<TeacherQuiz>>? _quizzes;

  @override
  void initState() {
    super.initState();
    _videos = widget.repository.videos();
  }

  void _select(int i) {
    setState(() {
      _segment = i;
      if (i == 0) _videos ??= widget.repository.videos();
      if (i == 1) _materials ??= widget.repository.materials();
      if (i == 2) _quizzes ??= widget.repository.quizzes();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 8),
          child: SegmentedButton<int>(
            segments: const [
              ButtonSegment(value: 0, label: Text('Video')),
              ButtonSegment(value: 1, label: Text('Bahan')),
              ButtonSegment(value: 2, label: Text('Kuiz')),
            ],
            selected: {_segment},
            onSelectionChanged: (s) => _select(s.first),
            showSelectedIcon: false,
          ),
        ),
        Expanded(child: _body()),
      ],
    );
  }

  Widget _body() {
    switch (_segment) {
      case 1:
        return _MaterialsList(
          future: _materials!,
          onReload: () => setState(() => _materials = widget.repository.materials()),
        );
      case 2:
        return _QuizzesList(
          future: _quizzes!,
          onReload: () => setState(() => _quizzes = widget.repository.quizzes()),
        );
      default:
        return _VideosList(
          future: _videos!,
          onReload: () => setState(() => _videos = widget.repository.videos()),
        );
    }
  }
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.published});
  final bool published;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 3),
      decoration: BoxDecoration(
        color: published ? LmsColors.brandSoft : const Color(0xFFFBEEDC),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        published ? 'Diterbitkan' : 'Draf',
        style: TextStyle(
          fontSize: 10.5,
          fontWeight: FontWeight.w800,
          color: published ? LmsColors.brandStrong : LmsColors.warning,
        ),
      ),
    );
  }
}

class _VideosList extends StatelessWidget {
  const _VideosList({required this.future, required this.onReload});
  final Future<List<TeacherVideo>> future;
  final VoidCallback onReload;

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<TeacherVideo>>(
      future: future,
      builder: (context, snap) {
        if (snap.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snap.hasError) {
          return StateMessage(
            icon: Icons.wifi_off_outlined,
            title: 'Tidak dapat memuatkan',
            subtitle: '${snap.error}',
            onRetry: onReload,
          );
        }
        final items = snap.data!;
        if (items.isEmpty) {
          return const StateMessage(
            icon: Icons.video_library_outlined,
            title: 'Belum ada video',
            subtitle: 'Video yang anda tambah akan disenaraikan di sini.',
          );
        }
        return ListView.separated(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
          itemCount: items.length,
          separatorBuilder: (_, _) => const SizedBox(height: 10),
          itemBuilder: (context, i) {
            final v = items[i];
            return _ContentCard(
              icon: Icons.play_circle_outline,
              title: v.title,
              subtitle: [
                if (v.subjectName != null) v.subjectName!,
                if (v.chapterLabel != null) v.chapterLabel!,
                '${v.views} tontonan',
                v.ownershipLabel,
              ].join(' · '),
              published: v.published,
            );
          },
        );
      },
    );
  }
}

class _MaterialsList extends StatelessWidget {
  const _MaterialsList({required this.future, required this.onReload});
  final Future<List<TeacherMaterial>> future;
  final VoidCallback onReload;

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<TeacherMaterial>>(
      future: future,
      builder: (context, snap) {
        if (snap.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snap.hasError) {
          return StateMessage(
            icon: Icons.wifi_off_outlined,
            title: 'Tidak dapat memuatkan',
            subtitle: '${snap.error}',
            onRetry: onReload,
          );
        }
        final items = snap.data!;
        if (items.isEmpty) {
          return const StateMessage(
            icon: Icons.description_outlined,
            title: 'Belum ada bahan',
            subtitle: 'Bahan yang anda muat naik akan disenaraikan di sini.',
          );
        }
        return ListView.separated(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
          itemCount: items.length,
          separatorBuilder: (_, _) => const SizedBox(height: 10),
          itemBuilder: (context, i) {
            final m = items[i];
            return _ContentCard(
              icon: Icons.insert_drive_file_outlined,
              title: m.title,
              subtitle: [
                m.extension.toUpperCase(),
                if (m.humanSize.isNotEmpty) m.humanSize,
              ].join(' · '),
            );
          },
        );
      },
    );
  }
}

class _QuizzesList extends StatelessWidget {
  const _QuizzesList({required this.future, required this.onReload});
  final Future<List<TeacherQuiz>> future;
  final VoidCallback onReload;

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<TeacherQuiz>>(
      future: future,
      builder: (context, snap) {
        if (snap.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snap.hasError) {
          return StateMessage(
            icon: Icons.wifi_off_outlined,
            title: 'Tidak dapat memuatkan',
            subtitle: '${snap.error}',
            onRetry: onReload,
          );
        }
        final items = snap.data!;
        if (items.isEmpty) {
          return const StateMessage(
            icon: Icons.quiz_outlined,
            title: 'Belum ada kuiz',
            subtitle: 'Kuiz yang anda cipta akan disenaraikan di sini.',
          );
        }
        return ListView.separated(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
          itemCount: items.length,
          separatorBuilder: (_, _) => const SizedBox(height: 10),
          itemBuilder: (context, i) {
            final q = items[i];
            return _ContentCard(
              icon: q.isFile ? Icons.description_outlined : Icons.quiz_outlined,
              title: q.title,
              subtitle: [
                if (q.subjectName != null) q.subjectName!,
                if (q.chapterLabel != null) q.chapterLabel!,
                if (!q.isFile) '${q.questionCount} soalan',
                '${q.attempts} percubaan',
              ].join(' · '),
              published: q.published,
            );
          },
        );
      },
    );
  }
}

class _ContentCard extends StatelessWidget {
  const _ContentCard({
    required this.icon,
    required this.title,
    required this.subtitle,
    this.published,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final bool? published;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: LmsColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: LmsColors.border),
      ),
      padding: const EdgeInsets.all(14),
      child: Row(
        children: [
          Container(
            decoration: BoxDecoration(
              color: LmsColors.brandSoft,
              borderRadius: BorderRadius.circular(12),
            ),
            padding: const EdgeInsets.all(11),
            child: Icon(icon, color: LmsColors.brand),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        title,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                    ),
                    if (published != null) ...[
                      const SizedBox(width: 8),
                      _StatusChip(published: published!),
                    ],
                  ],
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
