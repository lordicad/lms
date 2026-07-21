import 'package:flutter/material.dart';

import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../../core/widgets/app_feedback.dart';
import '../../core/widgets/loading_skeleton.dart';
import '../student/widgets/content_widgets.dart';
import 'chapters_manage_tab.dart';
import 'material_form_screen.dart';
import 'quiz_builder_screen.dart';
import 'quiz_statistics_screen.dart';
import 'video_form_screen.dart';

/// The teacher's Content Hub: a segmented list of their videos, materials and quizzes
/// with publish status and the actions allowed on each item.
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

  void _reloadCurrent() {
    setState(() {
      switch (_segment) {
        case 0:
          _videos = widget.repository.videos();
          break;
        case 1:
          _materials = widget.repository.materials();
          break;
        case 2:
          _quizzes = widget.repository.quizzes();
          break;
        case 3:
          break;
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 14, 20, 8),
          child: Row(
            children: [
              Expanded(
                child: Container(
                  padding: const EdgeInsets.all(3),
                  decoration: BoxDecoration(
                    color: LmsPalette.soft(context),
                    borderRadius: BorderRadius.circular(13),
                  ),
                  child: SegmentedButton<int>(
                    segments: const [
                      ButtonSegment(value: 0, label: Text('Video')),
                      ButtonSegment(value: 1, label: Text('Bahan')),
                      ButtonSegment(value: 2, label: Text('Kuiz')),
                      ButtonSegment(value: 3, label: Text('Bab')),
                    ],
                    selected: {_segment},
                    onSelectionChanged: (s) => _select(s.first),
                    showSelectedIcon: false,
                    style: ButtonStyle(
                      textStyle: const WidgetStatePropertyAll(
                        TextStyle(fontSize: 11, fontWeight: FontWeight.w800),
                      ),
                      foregroundColor: WidgetStateProperty.resolveWith(
                        (states) => states.contains(WidgetState.selected)
                            ? Colors.white
                            : LmsPalette.muted(context),
                      ),
                      backgroundColor: WidgetStateProperty.resolveWith(
                        (states) => states.contains(WidgetState.selected)
                            ? LmsColors.brand
                            : Colors.transparent,
                      ),
                      side: const WidgetStatePropertyAll(BorderSide.none),
                      shape: WidgetStatePropertyAll(
                        RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Container(
                decoration: BoxDecoration(
                  color: LmsPalette.surface(context),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: LmsPalette.border(context)),
                ),
                child: IconButton(
                  tooltip: 'Muat semula',
                  onPressed: _segment == 3 ? null : _reloadCurrent,
                  icon: const Icon(Icons.refresh_rounded),
                ),
              ),
            ],
          ),
        ),
        Expanded(child: _body()),
      ],
    );
  }

  Future<void> _togglePublishVideo(int id) async {
    try {
      final published = await widget.repository.togglePublishVideo(id);
      if (mounted) {
        setState(() {
          _videos = widget.repository.videos();
        });
        AppFeedback.success(
          published ? 'Video diterbitkan' : 'Video disembunyikan',
          description: published
              ? 'Murid kini boleh melihat video ini.'
              : 'Video ini tidak lagi dipaparkan kepada murid.',
        );
      }
    } catch (e) {
      if (mounted) {
        AppFeedback.error('Tidak dapat mengemas kini video', description: '$e');
      }
    }
  }

  Future<void> _togglePublishQuiz(int id) async {
    try {
      final published = await widget.repository.togglePublishQuiz(id);
      if (mounted) {
        setState(() {
          _quizzes = widget.repository.quizzes();
        });
        AppFeedback.success(
          published ? 'Kuiz diterbitkan' : 'Kuiz disembunyikan',
          description: published
              ? 'Murid kini boleh menjawab kuiz ini.'
              : 'Kuiz ini tidak lagi dipaparkan kepada murid.',
        );
      }
    } catch (e) {
      if (mounted) {
        AppFeedback.error('Tidak dapat mengemas kini kuiz', description: '$e');
      }
    }
  }

  Future<bool> _confirmDelete(String what) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Padam?'),
        content: Text('Padam $what? Tindakan ini tidak boleh dibatalkan.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Batal'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: LmsColors.danger),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Padam'),
          ),
        ],
      ),
    );
    return ok ?? false;
  }

  Future<void> _deleteVideo(int id) async {
    if (!await _confirmDelete('video ini')) return;
    try {
      await widget.repository.deleteVideo(id);
      if (mounted) {
        setState(() {
          _videos = widget.repository.videos();
        });
        AppFeedback.success(
          'Video dipadam',
          description: 'Video telah dikeluarkan daripada Kandungan.',
        );
      }
    } catch (e) {
      if (mounted) {
        AppFeedback.error('Tidak dapat memadam video', description: '$e');
      }
    }
  }

  Future<void> _deleteMaterial(int id) async {
    if (!await _confirmDelete('bahan ini')) return;
    try {
      await widget.repository.deleteMaterial(id);
      if (mounted) {
        setState(() {
          _materials = widget.repository.materials();
        });
        AppFeedback.success(
          'Bahan dipadam',
          description: 'Bahan telah dikeluarkan daripada Kandungan.',
        );
      }
    } catch (e) {
      if (mounted) {
        AppFeedback.error('Tidak dapat memadam bahan', description: '$e');
      }
    }
  }

  Future<void> _deleteQuiz(int id) async {
    if (!await _confirmDelete('kuiz ini')) return;
    try {
      await widget.repository.deleteQuiz(id);
      if (mounted) {
        setState(() {
          _quizzes = widget.repository.quizzes();
        });
        AppFeedback.success(
          'Kuiz dipadam',
          description: 'Kuiz telah dikeluarkan daripada Kandungan.',
        );
      }
    } catch (e) {
      if (mounted) {
        AppFeedback.error('Tidak dapat memadam kuiz', description: '$e');
      }
    }
  }

  Future<void> _editVideo(TeacherVideo video) async {
    final updated = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) =>
            VideoFormScreen(repository: widget.repository, video: video),
      ),
    );
    if (updated == true && mounted) {
      setState(() => _videos = widget.repository.videos());
    }
  }

  Future<void> _editMaterial(TeacherMaterial material) async {
    final updated = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => MaterialFormScreen(
          repository: widget.repository,
          material: material,
        ),
      ),
    );
    if (updated == true && mounted) {
      setState(() => _materials = widget.repository.materials());
    }
  }

  Future<void> _editQuiz(TeacherQuiz quiz) async {
    final updated = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) =>
            QuizBuilderScreen(repository: widget.repository, quizId: quiz.id),
      ),
    );
    if (updated == true && mounted) {
      setState(() => _quizzes = widget.repository.quizzes());
    }
  }

  Future<void> _viewQuizStats(TeacherQuiz quiz) async {
    await Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) =>
            QuizStatisticsScreen(repository: widget.repository, quiz: quiz),
      ),
    );
  }

  Widget _body() {
    switch (_segment) {
      case 3:
        return ChaptersManageTab(repository: widget.repository);
      case 1:
        return _MaterialsList(
          future: _materials!,
          onReload: () => setState(() {
            _materials = widget.repository.materials();
          }),
          onDelete: _deleteMaterial,
          onEdit: _editMaterial,
        );
      case 2:
        return _QuizzesList(
          future: _quizzes!,
          onReload: () => setState(() {
            _quizzes = widget.repository.quizzes();
          }),
          onToggle: _togglePublishQuiz,
          onDelete: _deleteQuiz,
          onEdit: _editQuiz,
          onStats: _viewQuizStats,
        );
      default:
        return _VideosList(
          future: _videos!,
          onReload: () => setState(() {
            _videos = widget.repository.videos();
          }),
          onToggle: _togglePublishVideo,
          onDelete: _deleteVideo,
          onEdit: _editVideo,
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
        color: published
            ? LmsPalette.soft(context)
            : Theme.of(context).brightness == Brightness.dark
            ? const Color(0xFF4C3C25)
            : const Color(0xFFFBEEDC),
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
  const _VideosList({
    required this.future,
    required this.onReload,
    required this.onToggle,
    required this.onDelete,
    required this.onEdit,
  });
  final Future<List<TeacherVideo>> future;
  final VoidCallback onReload;
  final void Function(int id) onToggle;
  final void Function(int id) onDelete;
  final void Function(TeacherVideo video) onEdit;

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<TeacherVideo>>(
      future: future,
      builder: (context, snap) {
        if (snap.connectionState == ConnectionState.waiting) {
          return const ContentListSkeleton(count: 4);
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
              onEdit: v.source == 'youtube' ? () => onEdit(v) : null,
              onTogglePublish: () => onToggle(v.id),
              onDelete: () => onDelete(v.id),
            );
          },
        );
      },
    );
  }
}

class _MaterialsList extends StatelessWidget {
  const _MaterialsList({
    required this.future,
    required this.onReload,
    required this.onDelete,
    required this.onEdit,
  });
  final Future<List<TeacherMaterial>> future;
  final VoidCallback onReload;
  final void Function(int id) onDelete;
  final void Function(TeacherMaterial material) onEdit;

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<TeacherMaterial>>(
      future: future,
      builder: (context, snap) {
        if (snap.connectionState == ConnectionState.waiting) {
          return const ContentListSkeleton(count: 4);
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
                if (m.subjectName != null) m.subjectName!,
                if (m.chapterLabel != null) m.chapterLabel!,
                m.extension.toUpperCase(),
                if (m.humanSize.isNotEmpty) m.humanSize,
              ].join(' · '),
              onEdit: () => onEdit(m),
              onDelete: () => onDelete(m.id),
            );
          },
        );
      },
    );
  }
}

class _QuizzesList extends StatelessWidget {
  const _QuizzesList({
    required this.future,
    required this.onReload,
    required this.onToggle,
    required this.onDelete,
    required this.onEdit,
    required this.onStats,
  });
  final Future<List<TeacherQuiz>> future;
  final VoidCallback onReload;
  final void Function(int id) onToggle;
  final void Function(int id) onDelete;
  final void Function(TeacherQuiz quiz) onEdit;
  final void Function(TeacherQuiz quiz) onStats;

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<TeacherQuiz>>(
      future: future,
      builder: (context, snap) {
        if (snap.connectionState == ConnectionState.waiting) {
          return const ContentListSkeleton(count: 4);
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
              onEdit: q.isFile ? null : () => onEdit(q),
              onStats: q.isFile ? null : () => onStats(q),
              onTogglePublish: () => onToggle(q.id),
              onDelete: () => onDelete(q.id),
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
    this.onEdit,
    this.onStats,
    this.onTogglePublish,
    this.onDelete,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final bool? published;
  final VoidCallback? onEdit;
  final VoidCallback? onStats;
  final VoidCallback? onTogglePublish;
  final VoidCallback? onDelete;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: LmsPalette.surface(context),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: LmsPalette.border(context)),
        boxShadow: const [
          BoxShadow(
            color: Color(0x0D1B3520),
            blurRadius: 10,
            offset: Offset(0, 3),
          ),
        ],
      ),
      padding: const EdgeInsets.all(14),
      child: Row(
        children: [
          Container(
            decoration: BoxDecoration(
              color: LmsPalette.soft(context),
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
          if (onEdit != null ||
              onStats != null ||
              onTogglePublish != null ||
              onDelete != null)
            PopupMenuButton<String>(
              icon: Icon(Icons.more_vert, color: LmsPalette.faint(context)),
              tooltip: 'Tindakan',
              onSelected: (value) {
                if (value == 'edit') onEdit?.call();
                if (value == 'stats') onStats?.call();
                if (value == 'toggle') onTogglePublish?.call();
                if (value == 'delete') onDelete?.call();
              },
              itemBuilder: (_) => [
                if (onEdit != null)
                  const PopupMenuItem(
                    value: 'edit',
                    child: Row(
                      children: [
                        Icon(Icons.edit_outlined, size: 18),
                        SizedBox(width: 10),
                        Text('Sunting'),
                      ],
                    ),
                  ),
                if (onStats != null)
                  const PopupMenuItem(
                    value: 'stats',
                    child: Row(
                      children: [
                        Icon(Icons.bar_chart_outlined, size: 18),
                        SizedBox(width: 10),
                        Text('Statistik'),
                      ],
                    ),
                  ),
                if (onTogglePublish != null && published != null)
                  PopupMenuItem(
                    value: 'toggle',
                    child: Row(
                      children: [
                        Icon(
                          published!
                              ? Icons.visibility_off_outlined
                              : Icons.public,
                          size: 18,
                          color: LmsColors.ink,
                        ),
                        const SizedBox(width: 10),
                        Text(published! ? 'Sembunyikan' : 'Terbitkan'),
                      ],
                    ),
                  ),
                if (onDelete != null)
                  const PopupMenuItem(
                    value: 'delete',
                    child: Row(
                      children: [
                        Icon(
                          Icons.delete_outline,
                          size: 18,
                          color: LmsColors.danger,
                        ),
                        SizedBox(width: 10),
                        Text(
                          'Padam',
                          style: TextStyle(color: LmsColors.danger),
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
