import 'package:flutter/material.dart';

import '../../core/content/content_models.dart';
import '../../core/theme/lms_theme.dart';

/// The graded result: a big score header (celebration >= 80%), a practice note when the
/// attempt didn't count for ranking, and a per-question review with correct answers marked.
class QuizResultScreen extends StatelessWidget {
  const QuizResultScreen({super.key, required this.result});

  final QuizResult result;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(result.quizTitle, overflow: TextOverflow.ellipsis)),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
        children: [
          _ScoreHeader(result: result),
          if (!result.countsForRanking) ...[
            const SizedBox(height: 12),
            const _Banner(
              icon: Icons.fitness_center_rounded,
              text: 'Ini latihan — markah ranking anda tidak berubah.',
            ),
          ],
          const SizedBox(height: 20),
          Text('Semakan jawapan', style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 8),
          ...result.questions.asMap().entries.map(
                (e) => _ReviewCard(index: e.key + 1, question: e.value),
              ),
        ],
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
          child: FilledButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Selesai'),
          ),
        ),
      ),
    );
  }
}

class _ScoreHeader extends StatelessWidget {
  const _ScoreHeader({required this.result});
  final QuizResult result;

  @override
  Widget build(BuildContext context) {
    final celebrate = result.isCelebration;
    return Container(
      decoration: BoxDecoration(
        color: celebrate ? LmsColors.forest : LmsColors.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: celebrate ? Colors.transparent : LmsColors.border),
      ),
      padding: const EdgeInsets.all(22),
      child: Column(
        children: [
          Icon(
            celebrate ? Icons.emoji_events_rounded : Icons.check_circle_outline_rounded,
            size: 42,
            color: celebrate ? LmsColors.accent : LmsColors.brand,
          ),
          const SizedBox(height: 10),
          Text(
            '${result.percent}%',
            style: TextStyle(
              fontSize: 40,
              fontWeight: FontWeight.w800,
              color: celebrate ? Colors.white : LmsColors.ink,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            '${result.score} / ${result.maxScore} markah · ${result.correctCount}/${result.questionCount} betul',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontWeight: FontWeight.w600,
              color: celebrate ? const Color(0xFFB9CCB8) : LmsColors.inkMuted,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            celebrate ? 'Hebat! Kerja yang bagus.' : 'Bagus percubaan — teruskan berlatih!',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontWeight: FontWeight.w700,
              color: celebrate ? Colors.white : LmsColors.brandStrong,
            ),
          ),
        ],
      ),
    );
  }
}

class _Banner extends StatelessWidget {
  const _Banner({required this.icon, required this.text});
  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(color: LmsColors.brandSoft, borderRadius: BorderRadius.circular(12)),
      padding: const EdgeInsets.all(14),
      child: Row(
        children: [
          Icon(icon, size: 18, color: LmsColors.brandStrong),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: const TextStyle(fontWeight: FontWeight.w600, color: LmsColors.brandStrong),
            ),
          ),
        ],
      ),
    );
  }
}

class _ReviewCard extends StatelessWidget {
  const _ReviewCard({required this.index, required this.question});
  final int index;
  final QuizResultQuestion question;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: LmsColors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: LmsColors.border),
      ),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(
                question.isCorrect ? Icons.check_circle_rounded : Icons.cancel_rounded,
                size: 20,
                color: question.isCorrect ? LmsColors.success : LmsColors.danger,
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text('$index. ${question.text}',
                    style: Theme.of(context).textTheme.titleMedium),
              ),
            ],
          ),
          const SizedBox(height: 10),
          ...question.options.map(
            (o) => _OptionReviewRow(option: o, chosen: question.yourOptionIds.contains(o.id)),
          ),
        ],
      ),
    );
  }
}

class _OptionReviewRow extends StatelessWidget {
  const _OptionReviewRow({required this.option, required this.chosen});
  final QuizOption option;
  final bool chosen;

  @override
  Widget build(BuildContext context) {
    final correct = option.isCorrect;
    final Color bg = correct
        ? const Color(0xFFE7F3E9)
        : (chosen ? const Color(0xFFFBE9E9) : LmsColors.surface);
    final Color border = correct
        ? LmsColors.success
        : (chosen ? LmsColors.danger : LmsColors.border);

    IconData? mark;
    Color? markColor;
    if (correct) {
      mark = Icons.check;
      markColor = LmsColors.success;
    } else if (chosen) {
      mark = Icons.close;
      markColor = LmsColors.danger;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 6),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: border),
      ),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      child: Row(
        children: [
          Text('${option.letter}. ',
              style: const TextStyle(fontWeight: FontWeight.w800, color: LmsColors.inkMuted)),
          Expanded(child: Text(option.text)),
          if (mark != null) Icon(mark, size: 18, color: markColor),
        ],
      ),
    );
  }
}
