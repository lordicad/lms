import 'package:flutter/material.dart';

import '../../core/content/content_models.dart';
import '../../core/content/content_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../../core/widgets/loading_skeleton.dart';
import 'quiz_intro_screen.dart';
import 'widgets/content_widgets.dart';

/// Every quiz offered in the student's Tahun. Backed by GET /student/quizzes.
class QuizzesTab extends StatefulWidget {
  const QuizzesTab({super.key, required this.repository, this.grade});

  final ContentRepository repository;
  final int? grade;

  @override
  State<QuizzesTab> createState() => _QuizzesTabState();
}

class _QuizzesTabState extends State<QuizzesTab> {
  late Future<List<QuizListItem>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  @override
  void didUpdateWidget(covariant QuizzesTab oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.grade != widget.grade) _future = _load();
  }

  Future<List<QuizListItem>> _load() =>
      widget.repository.quizzes(grade: widget.grade);

  Future<void> _reload() async {
    setState(() {
      _future = _load();
    });
    await _future.catchError((_) => throw Exception());
  }

  void _open(QuizListItem quiz) {
    Navigator.of(context)
        .push(
          MaterialPageRoute(
            builder: (_) => QuizIntroScreen(
              repository: widget.repository,
              quizId: quiz.id,
              title: quiz.title,
            ),
          ),
        )
        .then((_) => _reload());
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<QuizListItem>>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const ContentListSkeleton(count: 3);
        }
        if (snapshot.hasError) {
          return StateMessage(
            icon: Icons.wifi_off_outlined,
            title: 'Tidak dapat memuatkan kuiz',
            subtitle: '${snapshot.error}',
            onRetry: _reload,
          );
        }

        final quizzes = snapshot.data!;
        if (quizzes.isEmpty) {
          return const StateMessage(
            icon: Icons.quiz_outlined,
            title: 'Belum ada kuiz',
            subtitle: 'Tiada kuiz ditawarkan untuk Tahun anda buat masa ini.',
          );
        }

        return RefreshIndicator(
          onRefresh: _reload,
          child: ListView.separated(
            padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
            itemCount: quizzes.length,
            separatorBuilder: (_, _) => const SizedBox(height: 10),
            itemBuilder: (context, i) =>
                _QuizCard(quiz: quizzes[i], onTap: () => _open(quizzes[i])),
          ),
        );
      },
    );
  }
}

class _QuizCard extends StatelessWidget {
  const _QuizCard({required this.quiz, required this.onTap});
  final QuizListItem quiz;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
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
              child: Icon(
                quiz.isFile ? Icons.description_rounded : Icons.quiz_rounded,
                color: LmsColors.brand,
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    quiz.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  const SizedBox(height: 2),
                  Text(
                    [
                      if (quiz.subjectName != null) quiz.subjectName!,
                      if (quiz.chapterLabel != null) quiz.chapterLabel!,
                      if (!quiz.isFile) '${quiz.questionCount} soalan',
                    ].join(' · '),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                ],
              ),
            ),
            const SizedBox(width: 10),
            if (quiz.attempted && quiz.percent != null)
              Text(
                '${quiz.percent}%',
                style: const TextStyle(
                  fontWeight: FontWeight.w800,
                  color: LmsColors.success,
                ),
              )
            else
              const Text(
                'Belum cuba',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: LmsColors.inkFaint,
                ),
              ),
          ],
        ),
      ),
    );
  }
}
