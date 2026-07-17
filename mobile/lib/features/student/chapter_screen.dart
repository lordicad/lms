import 'package:flutter/material.dart';

import '../../core/content/content_models.dart';
import '../../core/content/content_repository.dart';
import '../../core/theme/lms_theme.dart';
import 'quiz_intro_screen.dart';
import 'watch_screen.dart';
import 'widgets/content_widgets.dart';

/// Everything inside one Bab: the videos, supporting materials, and quizzes.
/// Quiz taking arrives in a later phase; for now quizzes are listed for context.
class ChapterScreen extends StatefulWidget {
  const ChapterScreen({super.key, required this.repository, required this.chapterId, required this.title});

  final ContentRepository repository;
  final int chapterId;
  final String title;

  @override
  State<ChapterScreen> createState() => _ChapterScreenState();
}

class _ChapterScreenState extends State<ChapterScreen> {
  late Future<ChapterDetail> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.repository.chapter(widget.chapterId);
  }

  void _reload() => setState(() => _future = widget.repository.chapter(widget.chapterId));

  Future<void> _openLesson(int id) async {
    await Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => WatchScreen(repository: widget.repository, lessonId: id)),
    );
    _reload(); // refresh watched/progress ticks after returning
  }

  Future<void> _openQuiz(QuizItem quiz) async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => QuizIntroScreen(
          repository: widget.repository,
          quizId: quiz.id,
          title: quiz.title,
        ),
      ),
    );
    _reload(); // attempt counts may have changed
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.title, overflow: TextOverflow.ellipsis)),
      body: FutureBuilder<ChapterDetail>(
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
          if (data.lessons.isEmpty && data.materials.isEmpty && data.quizzes.isEmpty) {
            return const StateMessage(
              icon: Icons.inbox_outlined,
              title: 'Bab ini masih kosong',
              subtitle: 'Belum ada video atau bahan diterbitkan.',
            );
          }

          return ListView(
            padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
            children: [
              Text(data.subject.displayName, style: Theme.of(context).textTheme.bodyMedium),
              Text(data.label, style: Theme.of(context).textTheme.headlineMedium),
              if (data.description != null && data.description!.isNotEmpty) ...[
                const SizedBox(height: 8),
                Text(data.description!),
              ],
              const SizedBox(height: 20),
              if (data.lessons.isNotEmpty) ...[
                SectionTitle('Video (${data.lessons.length})'),
                const SizedBox(height: 4),
                ...data.lessons.map((l) => LessonRow(lesson: l, onTap: () => _openLesson(l.id))),
                const SizedBox(height: 20),
              ],
              if (data.materials.isNotEmpty) ...[
                const SectionTitle('Bahan'),
                const SizedBox(height: 8),
                ...data.materials.map((m) => _MaterialTile(material: m)),
                const SizedBox(height: 20),
              ],
              if (data.quizzes.isNotEmpty) ...[
                const SectionTitle('Kuiz'),
                const SizedBox(height: 8),
                ...data.quizzes.map((q) => _QuizTile(quiz: q, onTap: () => _openQuiz(q))),
              ],
            ],
          );
        },
      ),
    );
  }
}

class _MaterialTile extends StatelessWidget {
  const _MaterialTile({required this.material});
  final MaterialItem material;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: const Icon(Icons.description_outlined, color: LmsColors.brand),
      title: Text(material.title, style: Theme.of(context).textTheme.titleMedium),
      subtitle: Text('${material.extension.toUpperCase()} · ${material.humanSize}'),
    );
  }
}

class _QuizTile extends StatelessWidget {
  const _QuizTile({required this.quiz, required this.onTap});
  final QuizItem quiz;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final isFile = quiz.type == 'file';
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: Icon(isFile ? Icons.description_outlined : Icons.quiz_outlined, color: LmsColors.brand),
      title: Text(quiz.title, style: Theme.of(context).textTheme.titleMedium),
      subtitle: Text(quiz.myAttemptsCount > 0 ? 'Percubaan: ${quiz.myAttemptsCount}' : 'Belum dicuba'),
      trailing: const Icon(Icons.chevron_right, color: LmsColors.inkMuted),
      onTap: onTap,
    );
  }
}
