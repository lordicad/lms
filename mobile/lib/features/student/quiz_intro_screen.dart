import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/content/content_models.dart';
import '../../core/content/content_repository.dart';
import '../../core/theme/lms_theme.dart';
import 'quiz_take_screen.dart';
import 'widgets/content_widgets.dart';

/// Quiz cover: rules, question count, past attempts. Starts an attempt (interactive) or
/// opens the file (file quiz). Reachable from a chapter's quiz list.
class QuizIntroScreen extends StatefulWidget {
  const QuizIntroScreen({
    super.key,
    required this.repository,
    required this.quizId,
    required this.title,
  });

  final ContentRepository repository;
  final int quizId;
  final String title;

  @override
  State<QuizIntroScreen> createState() => _QuizIntroScreenState();
}

class _QuizIntroScreenState extends State<QuizIntroScreen> {
  late Future<QuizIntro> _future;
  bool _starting = false;

  @override
  void initState() {
    super.initState();
    _future = widget.repository.quizIntro(widget.quizId);
  }

  void _reload() => setState(() {
    _future = widget.repository.quizIntro(widget.quizId);
  });

  Future<void> _start() async {
    setState(() => _starting = true);
    try {
      final start = await widget.repository.startQuiz(widget.quizId);
      if (!mounted) return;
      await Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) =>
              QuizTakeScreen(repository: widget.repository, start: start),
        ),
      );
      if (mounted) _reload(); // refresh attempts after returning
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('$e')));
      }
    } finally {
      if (mounted) setState(() => _starting = false);
    }
  }

  Future<void> _openFile(String url) async {
    final uri = Uri.tryParse(url);
    if (uri != null) await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title, overflow: TextOverflow.ellipsis),
      ),
      body: FutureBuilder<QuizIntro>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return StateMessage(
              icon: Icons.error_outline,
              title: 'Tidak dapat memuatkan kuiz',
              subtitle: '${snapshot.error}',
              onRetry: _reload,
            );
          }

          final quiz = snapshot.data!;
          return ListView(
            padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
            children: [
              Container(
                decoration: BoxDecoration(
                  color: LmsColors.brandSoft,
                  borderRadius: BorderRadius.circular(16),
                ),
                padding: const EdgeInsets.all(18),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          decoration: BoxDecoration(
                            color: LmsColors.brand,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          padding: const EdgeInsets.all(10),
                          child: const Icon(
                            Icons.quiz_rounded,
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            [
                              if (quiz.subjectName != null) quiz.subjectName!,
                              quiz.chapterLabel,
                            ].join(' · '),
                            style: const TextStyle(
                              color: LmsColors.brandStrong,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Text(
                      quiz.title,
                      style: Theme.of(context).textTheme.headlineMedium,
                    ),
                    if (quiz.description != null &&
                        quiz.description!.isNotEmpty) ...[
                      const SizedBox(height: 6),
                      Text(quiz.description!),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 16),
              if (!quiz.isFile) ...[
                Row(
                  children: [
                    Expanded(
                      child: _StatBox(
                        label: 'Soalan',
                        value: '${quiz.questionCount}',
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _StatBox(
                        label: 'Markah',
                        value: '${quiz.maxScore}',
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _StatBox(
                        label: 'Masa',
                        value: quiz.durationMinutes != null
                            ? '${quiz.durationMinutes} min'
                            : 'Bebas',
                      ),
                    ),
                  ],
                ),
                if (quiz.hasRankedAttempt) ...[
                  const SizedBox(height: 16),
                  const _Note(
                    text:
                        'Anda sudah membuat percubaan yang dikira. Cubaan seterusnya ialah '
                        'latihan — markah ranking anda tidak berubah.',
                  ),
                ],
                if (quiz.myAttempts.isNotEmpty) ...[
                  const SizedBox(height: 20),
                  const SectionTitle('Percubaan lepas'),
                  const SizedBox(height: 8),
                  ...quiz.myAttempts.map((a) => _AttemptRow(attempt: a)),
                ],
              ] else ...[
                const _Note(
                  text:
                      'Kuiz ini ialah fail untuk dimuat turun dan dijawab di luar aplikasi.',
                ),
              ],
            ],
          );
        },
      ),
      bottomNavigationBar: FutureBuilder<QuizIntro>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) return const SizedBox.shrink();
          final quiz = snapshot.data!;
          return SafeArea(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
              child: quiz.isFile
                  ? FilledButton.icon(
                      onPressed: quiz.fileUrl == null
                          ? null
                          : () => _openFile(quiz.fileUrl!),
                      icon: const Icon(Icons.download_rounded),
                      label: const Text('Muat turun kuiz'),
                    )
                  : FilledButton.icon(
                      onPressed: (_starting || quiz.questionCount == 0)
                          ? null
                          : _start,
                      icon: _starting
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Icon(Icons.play_arrow_rounded),
                      label: Text(
                        quiz.myAttempts.isEmpty
                            ? 'Mula kuiz'
                            : 'Cuba lagi (latihan)',
                      ),
                    ),
            ),
          );
        },
      ),
    );
  }
}

class _StatBox extends StatelessWidget {
  const _StatBox({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: LmsColors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: LmsColors.border),
      ),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            value,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w800,
              color: LmsColors.ink,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: const TextStyle(fontSize: 11, color: LmsColors.inkMuted),
          ),
        ],
      ),
    );
  }
}

class _Note extends StatelessWidget {
  const _Note({required this.text});
  final String text;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: LmsColors.surfaceMuted,
        borderRadius: BorderRadius.circular(12),
      ),
      padding: const EdgeInsets.all(14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(
            Icons.info_outline_rounded,
            size: 18,
            color: LmsColors.inkMuted,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: const TextStyle(color: LmsColors.inkMuted),
            ),
          ),
        ],
      ),
    );
  }
}

class _AttemptRow extends StatelessWidget {
  const _AttemptRow({required this.attempt});
  final QuizAttemptSummary attempt;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: LmsColors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: LmsColors.border),
      ),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      child: Row(
        children: [
          Text(
            '${attempt.percent}%',
            style: const TextStyle(
              fontWeight: FontWeight.w800,
              color: LmsColors.brandStrong,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              '${attempt.score} / ${attempt.maxScore} markah',
              style: const TextStyle(color: LmsColors.inkMuted),
            ),
          ),
          if (attempt.countsForRanking)
            const Text(
              'Dikira',
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color: LmsColors.success,
              ),
            )
          else
            const Text(
              'Latihan',
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color: LmsColors.inkFaint,
              ),
            ),
        ],
      ),
    );
  }
}
