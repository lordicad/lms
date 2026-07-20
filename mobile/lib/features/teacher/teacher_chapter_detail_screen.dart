import 'package:flutter/material.dart';

import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../student/widgets/content_widgets.dart';

class TeacherChapterDetailScreen extends StatefulWidget {
  const TeacherChapterDetailScreen({
    super.key,
    required this.repository,
    required this.chapter,
    required this.subjectName,
    required this.gradeName,
  });

  final TeacherRepository repository;
  final TeacherChapter chapter;
  final String subjectName;
  final String gradeName;

  @override
  State<TeacherChapterDetailScreen> createState() =>
      _TeacherChapterDetailScreenState();
}

class _TeacherChapterDetailScreenState
    extends State<TeacherChapterDetailScreen> {
  late Future<_ChapterContent> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<_ChapterContent> _load() async {
    final data = await Future.wait([
      widget.repository.videos(),
      widget.repository.materials(),
      widget.repository.quizzes(),
    ]);
    final id = widget.chapter.id;
    return _ChapterContent(
      videos: (data[0] as List<TeacherVideo>)
          .where((item) => item.chapterId == id)
          .toList(growable: false),
      materials: (data[1] as List<TeacherMaterial>)
          .where((item) => item.chapterId == id)
          .toList(growable: false),
      quizzes: (data[2] as List<TeacherQuiz>)
          .where((item) => item.chapterId == id)
          .toList(growable: false),
    );
  }

  Future<void> _reload() async {
    setState(() => _future = _load());
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Bab ${widget.chapter.number}')),
      body: FutureBuilder<_ChapterContent>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return StateMessage(
              icon: Icons.wifi_off_outlined,
              title: 'Tidak dapat memuatkan kandungan',
              subtitle: '${snapshot.error}',
              onRetry: _reload,
            );
          }

          final content = snapshot.data!;
          return RefreshIndicator(
            onRefresh: _reload,
            child: ListView(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
              children: [
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: LmsColors.brandSoft,
                    borderRadius: BorderRadius.circular(18),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '${widget.subjectName} · ${widget.gradeName}',
                        style: const TextStyle(
                          color: LmsColors.brandStrong,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        widget.chapter.title,
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                      if (widget.chapter.description?.isNotEmpty ?? false) ...[
                        const SizedBox(height: 6),
                        Text(
                          widget.chapter.description!,
                          style: const TextStyle(color: LmsColors.inkMuted),
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(height: 24),
                _Section(
                  title: 'Video',
                  icon: Icons.play_circle_outline,
                  count: content.videos.length,
                  empty: 'Anda belum memuat naik video dalam bab ini.',
                  children: content.videos
                      .map(
                        (item) => _Row(
                          title: item.title,
                          subtitle:
                              '${item.views} tontonan · ${item.published ? 'Terbit' : 'Draf'}',
                          icon: Icons.play_circle_outline,
                        ),
                      )
                      .toList(growable: false),
                ),
                const SizedBox(height: 20),
                _Section(
                  title: 'Bahan',
                  icon: Icons.description_outlined,
                  count: content.materials.length,
                  empty: 'Anda belum memuat naik bahan dalam bab ini.',
                  children: content.materials
                      .map(
                        (item) => _Row(
                          title: item.title,
                          subtitle:
                              '${item.extension.toUpperCase()} · ${item.humanSize}',
                          icon: Icons.description_outlined,
                        ),
                      )
                      .toList(growable: false),
                ),
                const SizedBox(height: 20),
                _Section(
                  title: 'Kuiz',
                  icon: Icons.quiz_outlined,
                  count: content.quizzes.length,
                  empty: 'Anda belum mencipta kuiz dalam bab ini.',
                  children: content.quizzes
                      .map(
                        (item) => _Row(
                          title: item.title,
                          subtitle:
                              '${item.attempts} percubaan · ${item.published ? 'Terbit' : 'Draf'}',
                          icon: Icons.quiz_outlined,
                        ),
                      )
                      .toList(growable: false),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _ChapterContent {
  const _ChapterContent({
    required this.videos,
    required this.materials,
    required this.quizzes,
  });

  final List<TeacherVideo> videos;
  final List<TeacherMaterial> materials;
  final List<TeacherQuiz> quizzes;
}

class _Section extends StatelessWidget {
  const _Section({
    required this.title,
    required this.icon,
    required this.count,
    required this.empty,
    required this.children,
  });

  final String title;
  final IconData icon;
  final int count;
  final String empty;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Row(
        children: [
          Icon(icon, color: LmsColors.brand, size: 20),
          const SizedBox(width: 8),
          Text(
            '$title ($count)',
            style: Theme.of(context).textTheme.titleMedium,
          ),
        ],
      ),
      const SizedBox(height: 9),
      if (children.isEmpty)
        Text(empty, style: const TextStyle(color: LmsColors.inkMuted))
      else
        ...children,
    ],
  );
}

class _Row extends StatelessWidget {
  const _Row({required this.title, required this.subtitle, required this.icon});

  final String title;
  final String subtitle;
  final IconData icon;

  @override
  Widget build(BuildContext context) => Container(
    margin: const EdgeInsets.only(bottom: 8),
    padding: const EdgeInsets.all(13),
    decoration: BoxDecoration(
      color: LmsColors.surface,
      borderRadius: BorderRadius.circular(14),
      border: Border.all(color: LmsColors.border),
    ),
    child: Row(
      children: [
        Icon(icon, color: LmsColors.brand),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, maxLines: 1, overflow: TextOverflow.ellipsis),
              const SizedBox(height: 2),
              Text(
                subtitle,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontSize: 12, color: LmsColors.inkMuted),
              ),
            ],
          ),
        ),
      ],
    ),
  );
}
