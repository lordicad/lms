import 'package:flutter/material.dart';

import '../../core/content/content_models.dart';
import '../../core/content/content_repository.dart';
import '../../core/theme/lms_theme.dart';
import 'chapter_screen.dart';
import 'widgets/content_widgets.dart';

/// Chapter list for one Subject in the student's active Tahun, with per-chapter content
/// counts and how many videos the student has already watched.
class SubjectChaptersScreen extends StatefulWidget {
  const SubjectChaptersScreen({
    super.key,
    required this.repository,
    required this.slug,
    required this.title,
    this.grade,
  });

  final ContentRepository repository;
  final String slug;
  final String title;
  final int? grade;

  @override
  State<SubjectChaptersScreen> createState() => _SubjectChaptersScreenState();
}

class _SubjectChaptersScreenState extends State<SubjectChaptersScreen> {
  late Future<SubjectChaptersData> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.repository.subjectChapters(widget.slug, grade: widget.grade);
  }

  void _reload() =>
      setState(() => _future = widget.repository.subjectChapters(widget.slug, grade: widget.grade));

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.title, overflow: TextOverflow.ellipsis)),
      body: FutureBuilder<SubjectChaptersData>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return StateMessage(
              icon: Icons.error_outline,
              title: 'Tidak dapat memuatkan bab',
              subtitle: '${snapshot.error}',
              onRetry: _reload,
            );
          }

          final data = snapshot.data!;
          if (data.chapters.isEmpty) {
            return const StateMessage(
              icon: Icons.inbox_outlined,
              title: 'Belum ada bab',
              subtitle: 'Subjek ini belum mempunyai bab untuk Tahun anda.',
            );
          }

          return ListView.separated(
            padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
            itemCount: data.chapters.length + 1,
            separatorBuilder: (_, i) => SizedBox(height: i == 0 ? 12 : 10),
            itemBuilder: (context, i) {
              if (i == 0) {
                return Text('${data.subject.displayName} · ${data.grade.name}',
                    style: Theme.of(context).textTheme.bodyMedium);
              }
              final chapter = data.chapters[i - 1];
              return _ChapterCard(
                chapter: chapter,
                onTap: () => Navigator.of(context).push(MaterialPageRoute(
                  builder: (_) => ChapterScreen(
                    repository: widget.repository,
                    chapterId: chapter.id,
                    title: chapter.label,
                  ),
                )),
              );
            },
          );
        },
      ),
    );
  }
}

class _ChapterCard extends StatelessWidget {
  const _ChapterCard({required this.chapter, required this.onTap});

  final ChapterListItem chapter;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final done = chapter.lessonsCount > 0 && chapter.watchedCount >= chapter.lessonsCount;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        decoration: BoxDecoration(
          color: LmsColors.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: LmsColors.border),
        ),
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            CircleAvatar(
              backgroundColor: done ? LmsColors.success : LmsColors.brandSoft,
              foregroundColor: done ? Colors.white : LmsColors.brand,
              child: done ? const Icon(Icons.check) : Text('${chapter.number}'),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(chapter.title, style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 4),
                  Text(
                    _summary(chapter),
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                  if (chapter.lessonsCount > 0) ...[
                    const SizedBox(height: 8),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(99),
                      child: LinearProgressIndicator(
                        value: chapter.watchedCount / chapter.lessonsCount,
                        minHeight: 5,
                        backgroundColor: LmsColors.surfaceMuted,
                        valueColor: AlwaysStoppedAnimation(
                          done ? LmsColors.success : LmsColors.brand,
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(width: 8),
            const Icon(Icons.chevron_right, color: LmsColors.inkMuted),
          ],
        ),
      ),
    );
  }

  String _summary(ChapterListItem c) {
    final parts = <String>[];
    if (c.lessonsCount > 0) parts.add('${c.watchedCount}/${c.lessonsCount} video');
    if (c.materialsCount > 0) parts.add('${c.materialsCount} bahan');
    if (c.quizzesCount > 0) parts.add('${c.quizzesCount} kuiz');
    return parts.isEmpty ? 'Belum ada kandungan' : parts.join(' · ');
  }
}
