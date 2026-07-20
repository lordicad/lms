import 'package:flutter/material.dart';

import '../../core/content/content_models.dart';
import '../../core/content/content_repository.dart';
import '../../core/theme/lms_theme.dart';
import 'quiz_result_screen.dart';

/// Answer an interactive quiz: single (radio) or multiple (checkbox) questions, then submit.
/// Grading is server-side; on success we replace this screen with the result.
class QuizTakeScreen extends StatefulWidget {
  const QuizTakeScreen({
    super.key,
    required this.repository,
    required this.start,
  });

  final ContentRepository repository;
  final QuizStart start;

  @override
  State<QuizTakeScreen> createState() => _QuizTakeScreenState();
}

class _QuizTakeScreenState extends State<QuizTakeScreen> {
  // question id -> selected option ids
  final Map<int, Set<int>> _answers = {};
  bool _submitting = false;

  void _toggle(QuizQuestion q, int optionId) {
    setState(() {
      final set = _answers.putIfAbsent(q.id, () => <int>{});
      if (q.isMultiple) {
        if (!set.add(optionId)) set.remove(optionId);
      } else {
        set
          ..clear()
          ..add(optionId);
      }
    });
  }

  int get _answeredCount => _answers.values.where((s) => s.isNotEmpty).length;

  Future<void> _submit() async {
    final unanswered = widget.start.questions.length - _answeredCount;
    if (unanswered > 0) {
      final go = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Text('Hantar kuiz?'),
          content: Text('$unanswered soalan belum dijawab. Hantar juga?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Batal'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('Hantar'),
            ),
          ],
        ),
      );
      if (go != true) return;
    }

    setState(() => _submitting = true);
    try {
      final payload = _answers.map((k, v) => MapEntry(k, v.toList()));
      final result = await widget.repository.submitQuiz(
        widget.start.attemptId,
        payload,
      );
      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => QuizResultScreen(result: result)),
      );
    } catch (e) {
      if (mounted) {
        setState(() => _submitting = false);
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('$e')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final questions = widget.start.questions;
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.start.quizTitle, overflow: TextOverflow.ellipsis),
      ),
      body: ListView.builder(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
        itemCount: questions.length,
        itemBuilder: (context, i) => _QuestionCard(
          index: i + 1,
          question: questions[i],
          selected: _answers[questions[i].id] ?? const {},
          onToggle: (oid) => _toggle(questions[i], oid),
        ),
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
          child: FilledButton.icon(
            onPressed: _submitting ? null : _submit,
            icon: _submitting
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white,
                    ),
                  )
                : const Icon(Icons.send_rounded),
            label: Text('Hantar ($_answeredCount/${questions.length})'),
          ),
        ),
      ),
    );
  }
}

class _QuestionCard extends StatelessWidget {
  const _QuestionCard({
    required this.index,
    required this.question,
    required this.selected,
    required this.onToggle,
  });

  final int index;
  final QuizQuestion question;
  final Set<int> selected;
  final void Function(int optionId) onToggle;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: LmsColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: LmsColors.border),
      ),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Soalan $index',
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w800,
              letterSpacing: 0.4,
              color: LmsColors.brandStrong,
            ),
          ),
          const SizedBox(height: 4),
          Text(question.text, style: Theme.of(context).textTheme.titleMedium),
          if (question.isMultiple) ...[
            const SizedBox(height: 4),
            const Text(
              'Pilih semua yang betul',
              style: TextStyle(fontSize: 12, color: LmsColors.inkFaint),
            ),
          ],
          const SizedBox(height: 12),
          ...question.options.map(
            (o) => _OptionRow(
              option: o,
              selected: selected.contains(o.id),
              multiple: question.isMultiple,
              onTap: () => onToggle(o.id),
            ),
          ),
        ],
      ),
    );
  }
}

class _OptionRow extends StatelessWidget {
  const _OptionRow({
    required this.option,
    required this.selected,
    required this.multiple,
    required this.onTap,
  });

  final QuizOption option;
  final bool selected;
  final bool multiple;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          decoration: BoxDecoration(
            color: selected ? LmsColors.brandSoft : LmsColors.surface,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: selected ? LmsColors.brand : LmsColors.border,
              width: selected ? 1.5 : 1,
            ),
          ),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
          child: Row(
            children: [
              Icon(
                multiple
                    ? (selected
                          ? Icons.check_box_rounded
                          : Icons.check_box_outline_blank_rounded)
                    : (selected
                          ? Icons.radio_button_checked_rounded
                          : Icons.radio_button_unchecked_rounded),
                color: selected ? LmsColors.brand : LmsColors.inkFaint,
                size: 22,
              ),
              const SizedBox(width: 12),
              Text(
                '${option.letter}. ',
                style: const TextStyle(
                  fontWeight: FontWeight.w800,
                  color: LmsColors.inkMuted,
                ),
              ),
              Expanded(
                child: Text(
                  option.text,
                  style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    color: LmsColors.ink,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
