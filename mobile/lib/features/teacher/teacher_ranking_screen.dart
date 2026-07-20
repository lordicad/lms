import 'package:flutter/material.dart';

import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../student/widgets/content_widgets.dart';

/// The full student leaderboard for teachers. Rankings are the same first-attempt
/// scores shown to students and on the web Cikgu page; filters only change the view.
class TeacherRankingScreen extends StatefulWidget {
  const TeacherRankingScreen({super.key, required this.repository});

  final TeacherRepository repository;

  @override
  State<TeacherRankingScreen> createState() => _TeacherRankingScreenState();
}

class _TeacherRankingScreenState extends State<TeacherRankingScreen> {
  late Future<TeacherRankingData> _future;
  int? _gradeId;
  int? _subjectId;
  int? _quizId;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<TeacherRankingData> _load() => widget.repository.ranking(
    gradeId: _gradeId,
    subjectId: _subjectId,
    quizId: _quizId,
  );

  Future<void> _reload() async {
    setState(() => _future = _load());
    await _future;
  }

  void _setGrade(int? gradeId) {
    setState(() {
      _gradeId = gradeId;
      _quizId = null;
      _future = _load();
    });
  }

  void _setSubject(int? subjectId) {
    setState(() {
      _subjectId = subjectId;
      _quizId = null;
      _future = _load();
    });
  }

  void _setQuiz(int? quizId) {
    setState(() {
      _quizId = quizId;
      _future = _load();
    });
  }

  void _clearFilters() {
    setState(() {
      _gradeId = null;
      _subjectId = null;
      _quizId = null;
      _future = _load();
    });
  }

  @override
  Widget build(BuildContext context) {
    final hasFilters =
        _gradeId != null || _subjectId != null || _quizId != null;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Ranking Murid'),
        actions: [
          IconButton(
            tooltip: 'Muat semula',
            onPressed: _reload,
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: FutureBuilder<TeacherRankingData>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return StateMessage(
              icon: Icons.wifi_off_outlined,
              title: 'Tidak dapat memuatkan ranking',
              subtitle: '${snapshot.error}',
              onRetry: _reload,
            );
          }

          final data = snapshot.data!;
          return RefreshIndicator(
            onRefresh: _reload,
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 28),
              children: [
                const Text(
                  'Prestasi murid',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    color: LmsColors.ink,
                  ),
                ),
                const SizedBox(height: 4),
                const Text(
                  'Mata hanya daripada percubaan pertama setiap kuiz.',
                  style: TextStyle(fontSize: 12, color: LmsColors.inkMuted),
                ),
                const SizedBox(height: 16),
                _FiltersCard(
                  filters: data.filters,
                  gradeId: _gradeId,
                  subjectId: _subjectId,
                  quizId: _quizId,
                  onGradeChanged: _setGrade,
                  onSubjectChanged: _setSubject,
                  onQuizChanged: _setQuiz,
                  onClear: hasFilters ? _clearFilters : null,
                ),
                const SizedBox(height: 18),
                if (data.rows.isEmpty)
                  const Padding(
                    padding: EdgeInsets.only(top: 18),
                    child: StateMessage(
                      icon: Icons.emoji_events_outlined,
                      title: 'Belum ada data ranking',
                      subtitle:
                          'Ranking akan muncul selepas murid menyelesaikan kuiz interaktif.',
                    ),
                  )
                else ...[
                  Text(
                    '${data.rows.length} murid dalam senarai',
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: LmsColors.inkMuted,
                    ),
                  ),
                  const SizedBox(height: 9),
                  ...data.rows.map((row) => _RankingTile(row: row)),
                ],
              ],
            ),
          );
        },
      ),
    );
  }
}

class _FiltersCard extends StatelessWidget {
  const _FiltersCard({
    required this.filters,
    required this.gradeId,
    required this.subjectId,
    required this.quizId,
    required this.onGradeChanged,
    required this.onSubjectChanged,
    required this.onQuizChanged,
    this.onClear,
  });

  final TeacherRankingFilters filters;
  final int? gradeId;
  final int? subjectId;
  final int? quizId;
  final ValueChanged<int?> onGradeChanged;
  final ValueChanged<int?> onSubjectChanged;
  final ValueChanged<int?> onQuizChanged;
  final VoidCallback? onClear;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: LmsColors.surface,
        border: Border.all(color: LmsColors.border),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        children: [
          _FilterDropdown<OptionItem>(
            label: 'Tahun',
            value: gradeId,
            items: filters.grades,
            allLabel: 'Semua tahun',
            itemLabel: (item) => item.name,
            onChanged: onGradeChanged,
          ),
          const SizedBox(height: 10),
          _FilterDropdown<OptionItem>(
            label: 'Subjek',
            value: subjectId,
            items: filters.subjects,
            allLabel: 'Semua subjek',
            itemLabel: (item) => item.name,
            onChanged: onSubjectChanged,
          ),
          const SizedBox(height: 10),
          _FilterDropdown<TeacherRankingQuiz>(
            label: 'Kuiz',
            value: quizId,
            items: filters.quizzes,
            allLabel: 'Semua kuiz',
            itemLabel: (item) => item.title,
            onChanged: onQuizChanged,
          ),
          if (onClear != null) ...[
            const SizedBox(height: 6),
            Align(
              alignment: Alignment.centerRight,
              child: TextButton.icon(
                onPressed: onClear,
                icon: const Icon(Icons.filter_alt_off_outlined, size: 17),
                label: const Text('Kosongkan tapis'),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _FilterDropdown<T> extends StatelessWidget {
  const _FilterDropdown({
    required this.label,
    required this.value,
    required this.items,
    required this.allLabel,
    required this.itemLabel,
    required this.onChanged,
  });

  final String label;
  final int? value;
  final List<T> items;
  final String allLabel;
  final String Function(T item) itemLabel;
  final ValueChanged<int?> onChanged;

  int _id(T item) => switch (item) {
    OptionItem option => option.id,
    TeacherRankingQuiz quiz => quiz.id,
    _ => 0,
  };

  @override
  Widget build(BuildContext context) {
    return DropdownButtonFormField<int>(
      key: ValueKey('$label:$value'),
      initialValue: value,
      isExpanded: true,
      decoration: InputDecoration(
        labelText: label,
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      ),
      items: [
        DropdownMenuItem(value: null, child: Text(allLabel)),
        ...items.map(
          (item) => DropdownMenuItem(
            value: _id(item),
            child: Text(itemLabel(item), overflow: TextOverflow.ellipsis),
          ),
        ),
      ],
      onChanged: onChanged,
    );
  }
}

class _RankingTile extends StatelessWidget {
  const _RankingTile({required this.row});

  final TeacherRankingRow row;

  @override
  Widget build(BuildContext context) {
    final medal = _medalColor(row.rank);
    final accuracyColor = row.accuracy >= 70
        ? LmsColors.brandStrong
        : row.accuracy >= 50
        ? const Color(0xFF9A6A11)
        : const Color(0xFFBD4B3A);
    final accuracyBackground = row.accuracy >= 70
        ? LmsColors.brandSoft
        : row.accuracy >= 50
        ? const Color(0xFFFFF0D9)
        : const Color(0xFFFFE8E3);

    return Container(
      margin: const EdgeInsets.only(bottom: 9),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: LmsColors.surface,
        border: Border.all(color: LmsColors.border),
        borderRadius: BorderRadius.circular(15),
      ),
      child: Row(
        children: [
          Container(
            width: 34,
            height: 34,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: medal ?? LmsColors.surfaceMuted,
              shape: BoxShape.circle,
            ),
            child: Text(
              '${row.rank}',
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w800,
                color: medal == null ? LmsColors.inkMuted : Colors.white,
              ),
            ),
          ),
          const SizedBox(width: 10),
          CircleAvatar(
            radius: 19,
            backgroundColor: const Color(0xFFE7EFFD),
            foregroundColor: const Color(0xFF2F639C),
            child: Text(
              row.initials.isEmpty ? '?' : row.initials,
              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  row.studentName,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 13.5,
                    fontWeight: FontWeight.w800,
                    color: LmsColors.ink,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  '${row.gradeName ?? 'Tiada tahun'} · ${row.quizzes} kuiz · ${row.correct}/${row.questions} betul',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 10.5,
                    color: LmsColors.inkMuted,
                  ),
                ),
                const SizedBox(height: 5),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 7,
                    vertical: 3,
                  ),
                  decoration: BoxDecoration(
                    color: accuracyBackground,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    '${row.accuracy.toStringAsFixed(row.accuracy == row.accuracy.roundToDouble() ? 0 : 1)}% tepat',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w800,
                      color: accuracyColor,
                    ),
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
                '${row.points}',
                style: const TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.w800,
                  color: LmsColors.brandStrong,
                ),
              ),
              const Text(
                'mata',
                style: TextStyle(fontSize: 10, color: LmsColors.inkMuted),
              ),
            ],
          ),
        ],
      ),
    );
  }

  static Color? _medalColor(int rank) => switch (rank) {
    1 => const Color(0xFFE0A422),
    2 => const Color(0xFF9AA0A6),
    3 => const Color(0xFFC0803F),
    _ => null,
  };
}
