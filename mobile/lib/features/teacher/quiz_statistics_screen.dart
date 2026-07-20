import 'package:flutter/material.dart';

import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../student/widgets/content_widgets.dart';

/// Detailed results for one interactive quiz, matching the teacher web statistics.
class QuizStatisticsScreen extends StatefulWidget {
  const QuizStatisticsScreen({
    super.key,
    required this.repository,
    required this.quiz,
  });

  final TeacherRepository repository;
  final TeacherQuiz quiz;

  @override
  State<QuizStatisticsScreen> createState() => _QuizStatisticsScreenState();
}

class _QuizStatisticsScreenState extends State<QuizStatisticsScreen> {
  late Future<TeacherQuizStats> _stats;

  @override
  void initState() {
    super.initState();
    _stats = widget.repository.quizStats(widget.quiz.id);
  }

  void _reload() =>
      setState(() => _stats = widget.repository.quizStats(widget.quiz.id));

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Statistik Kuiz')),
      body: FutureBuilder<TeacherQuizStats>(
        future: _stats,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return StateMessage(
              icon: Icons.wifi_off_outlined,
              title: 'Tidak dapat memuatkan statistik',
              subtitle: '${snapshot.error}',
              onRetry: _reload,
            );
          }
          return _StatsBody(quiz: widget.quiz, stats: snapshot.data!);
        },
      ),
    );
  }
}

class _StatsBody extends StatelessWidget {
  const _StatsBody({required this.quiz, required this.stats});

  final TeacherQuiz quiz;
  final TeacherQuizStats stats;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
      children: [
        Text(quiz.title, style: Theme.of(context).textTheme.headlineSmall),
        const SizedBox(height: 4),
        Text(
          [
            if (quiz.subjectName != null) quiz.subjectName!,
            if (quiz.chapterLabel != null) quiz.chapterLabel!,
          ].join(' · '),
          style: const TextStyle(color: LmsColors.inkMuted),
        ),
        const SizedBox(height: 18),
        _SummaryGrid(stats: stats),
        const SizedBox(height: 28),
        Text(
          'Kadar betul setiap soalan',
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: 10),
        if (stats.completedCount == 0)
          const StateMessage(
            icon: Icons.bar_chart_outlined,
            title: 'Belum ada data',
            subtitle: 'Statistik akan muncul selepas murid menjawab kuiz ini.',
          )
        else
          ...stats.questions.map((question) => _QuestionStatCard(question)),
        const SizedBox(height: 24),
        Text('Percubaan murid', style: Theme.of(context).textTheme.titleLarge),
        const SizedBox(height: 10),
        if (stats.attempts.isEmpty)
          const StateMessage(
            icon: Icons.person_search_outlined,
            title: 'Belum ada murid mencuba',
            subtitle: 'Terbitkan kuiz supaya murid boleh menjawabnya.',
          )
        else
          ...stats.attempts.map((attempt) => _AttemptCard(attempt)),
      ],
    );
  }
}

class _SummaryGrid extends StatelessWidget {
  const _SummaryGrid({required this.stats});
  final TeacherQuizStats stats;

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: 10,
      runSpacing: 10,
      children: [
        _SummaryTile(
          label: 'Percubaan selesai',
          value: '${stats.completedCount}',
        ),
        _SummaryTile(
          label: 'Purata markah',
          value: '${_score(stats.averageScore)}/${stats.maxScore}',
        ),
        _SummaryTile(
          label: 'Purata ketepatan',
          value: '${stats.averagePercent}%',
        ),
      ],
    );
  }

  String _score(double score) => score == score.roundToDouble()
      ? score.toInt().toString()
      : score.toStringAsFixed(1);
}

class _SummaryTile extends StatelessWidget {
  const _SummaryTile({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 160,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: LmsColors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: LmsColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(fontSize: 12, color: LmsColors.inkMuted),
          ),
          const SizedBox(height: 5),
          Text(
            value,
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
              color: LmsColors.brandStrong,
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }
}

class _QuestionStatCard extends StatelessWidget {
  const _QuestionStatCard(this.question);
  final TeacherQuizQuestionStat question;

  @override
  Widget build(BuildContext context) {
    final color = question.rate >= 70
        ? LmsColors.brand
        : question.rate >= 40
        ? LmsColors.warning
        : LmsColors.danger;
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: LmsColors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: LmsColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  'Soalan ${question.number}',
                  style: const TextStyle(
                    fontWeight: FontWeight.w800,
                    color: LmsColors.inkMuted,
                  ),
                ),
              ),
              Text(
                '${question.rate}% betul',
                style: TextStyle(fontWeight: FontWeight.w800, color: color),
              ),
            ],
          ),
          const SizedBox(height: 5),
          Text(question.questionText),
          const SizedBox(height: 12),
          ClipRRect(
            borderRadius: BorderRadius.circular(99),
            child: LinearProgressIndicator(
              value: question.rate / 100,
              minHeight: 8,
              color: color,
              backgroundColor: LmsColors.surfaceMuted,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            '${question.correct} daripada ${question.answered} murid menjawab dengan betul.',
            style: const TextStyle(fontSize: 12, color: LmsColors.inkMuted),
          ),
        ],
      ),
    );
  }
}

class _AttemptCard extends StatelessWidget {
  const _AttemptCard(this.attempt);
  final TeacherQuizAttempt attempt;

  @override
  Widget build(BuildContext context) {
    final name = attempt.studentName?.trim().isNotEmpty == true
        ? attempt.studentName!
        : 'Murid';
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: LmsColors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: LmsColors.border),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            backgroundColor: LmsColors.brandSoft,
            foregroundColor: LmsColors.brandStrong,
            child: Text(_initials(name)),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(name, style: const TextStyle(fontWeight: FontWeight.w800)),
                const SizedBox(height: 2),
                Text(
                  [
                    attempt.gradeName ?? 'Tahun tidak diketahui',
                    '${attempt.correctCount}/${attempt.questionCount} betul',
                    attempt.duration,
                  ].join(' · '),
                  style: const TextStyle(
                    fontSize: 12,
                    color: LmsColors.inkMuted,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  '${attempt.countsForRanking ? 'Dikira untuk ranking' : 'Latihan'} · ${_date(attempt.completedAt)}',
                  style: const TextStyle(
                    fontSize: 11.5,
                    color: LmsColors.inkFaint,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                '${attempt.score}/${attempt.maxScore}',
                style: const TextStyle(fontWeight: FontWeight.w800),
              ),
              Text(
                '${attempt.percent}%',
                style: const TextStyle(
                  color: LmsColors.brandStrong,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  String _initials(String value) => value
      .split(RegExp(r'\s+'))
      .where((part) => part.isNotEmpty)
      .take(2)
      .map((part) => part[0].toUpperCase())
      .join();

  String _date(DateTime? date) {
    if (date == null) return 'Tarikh tidak diketahui';
    final hour = date.hour.toString().padLeft(2, '0');
    final minute = date.minute.toString().padLeft(2, '0');
    return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}, $hour:$minute';
  }
}
