import 'package:flutter/material.dart';

import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../student/widgets/content_widgets.dart';

/// Mobile-first builder for an interactive multiple-choice quiz.
///
/// File quizzes remain web-only because this mobile phase does not yet include a
/// document picker. Interactive quizzes are sent as one complete payload, which
/// lets the API save the quiz, questions and options atomically.
class QuizBuilderScreen extends StatefulWidget {
  const QuizBuilderScreen({super.key, required this.repository, this.quizId});

  final TeacherRepository repository;
  final int? quizId;

  @override
  State<QuizBuilderScreen> createState() => _QuizBuilderScreenState();
}

class _QuizBuilderScreenState extends State<QuizBuilderScreen> {
  static const _maxOptions = 6;

  final _titleCtrl = TextEditingController();
  final _descriptionCtrl = TextEditingController();
  final _durationCtrl = TextEditingController();
  final List<_QuestionDraft> _questions = [_QuestionDraft.withDefaults()];

  bool _published = true;
  bool _saving = false;
  TeacherOptions? _options;
  Object? _optionsError;
  OptionItem? _subject;
  OptionItem? _grade;
  List<TeacherChapter> _chapters = const [];
  TeacherChapter? _chapter;
  bool _loadingChapters = false;
  TeacherQuizDetail? _editingQuiz;

  bool get _isEditing => widget.quizId != null;

  @override
  void initState() {
    super.initState();
    _loadOptions();
  }

  @override
  void dispose() {
    _titleCtrl.dispose();
    _descriptionCtrl.dispose();
    _durationCtrl.dispose();
    for (final question in _questions) {
      question.dispose();
    }
    super.dispose();
  }

  Future<void> _loadOptions() async {
    setState(() => _optionsError = null);
    try {
      final options = await widget.repository.options();
      final detail = _isEditing
          ? await widget.repository.interactiveQuiz(widget.quizId!)
          : null;
      if (!mounted) return;
      setState(() {
        _options = options;
        _editingQuiz = detail;
        _subject = detail == null
            ? (options.subjects.isNotEmpty ? options.subjects.first : null)
            : _optionById(options.subjects, detail.subjectId);
        _grade = detail == null
            ? (options.grades.isNotEmpty ? options.grades.first : null)
            : _optionById(options.grades, detail.gradeId);
        if (detail != null) {
          _titleCtrl.text = detail.title;
          _descriptionCtrl.text = detail.description ?? '';
          _durationCtrl.text = detail.durationMinutes?.toString() ?? '';
          _published = detail.published;
          for (final question in _questions) {
            question.dispose();
          }
          _questions
            ..clear()
            ..addAll(
              detail.questions.isEmpty
                  ? [_QuestionDraft.withDefaults()]
                  : detail.questions
                        .map(_QuestionDraft.fromApiDraft)
                        .toList(growable: false),
            );
        }
      });
      await _loadChapters(preferredChapterId: detail?.chapterId);
    } catch (error) {
      if (mounted) setState(() => _optionsError = error);
    }
  }

  Future<void> _loadChapters({int? preferredChapterId}) async {
    if (_subject == null || _grade == null) return;
    setState(() {
      _loadingChapters = true;
      _chapters = const [];
      _chapter = null;
    });
    try {
      final data = await widget.repository.chapters(_subject!.id, _grade!.id);
      if (!mounted) return;
      setState(() {
        _chapters = data.chapters;
        _chapter =
            _chapterById(data.chapters, preferredChapterId) ??
            (data.chapters.isEmpty ? null : data.chapters.first);
        _loadingChapters = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() => _loadingChapters = false);
      _snack('$error');
    }
  }

  OptionItem? _optionById(List<OptionItem> items, int? id) {
    if (id == null) return items.isNotEmpty ? items.first : null;
    for (final item in items) {
      if (item.id == id) return item;
    }
    return items.isNotEmpty ? items.first : null;
  }

  TeacherChapter? _chapterById(List<TeacherChapter> items, int? id) {
    if (id == null) return null;
    for (final item in items) {
      if (item.id == id) return item;
    }
    return null;
  }

  void _snack(String message) => ScaffoldMessenger.of(
    context,
  ).showSnackBar(SnackBar(content: Text(message)));

  void _addQuestion() {
    setState(() => _questions.add(_QuestionDraft.withDefaults()));
  }

  void _removeQuestion(int index) {
    if (_questions.length == 1) {
      _snack('Kuiz perlu mempunyai sekurang-kurangnya satu soalan.');
      return;
    }
    final question = _questions.removeAt(index);
    question.dispose();
    setState(() {});
  }

  bool _validateDraft() {
    if (_chapter == null) {
      _snack('Sila pilih Bab.');
      return false;
    }
    if (_titleCtrl.text.trim().isEmpty) {
      _snack('Sila isi tajuk kuiz.');
      return false;
    }

    final duration = _durationCtrl.text.trim();
    if (duration.isNotEmpty) {
      final minutes = int.tryParse(duration);
      if (minutes == null || minutes < 1 || minutes > 180) {
        _snack('Masa kuiz perlu antara 1 hingga 180 minit.');
        return false;
      }
    }

    for (var index = 0; index < _questions.length; index++) {
      final question = _questions[index];
      final number = index + 1;
      if (question.text.text.trim().isEmpty) {
        _snack('Sila isi teks untuk soalan $number.');
        return false;
      }
      final points = int.tryParse(question.points.text.trim());
      if (points == null || points < 1 || points > 100) {
        _snack('Markah soalan $number perlu antara 1 hingga 100.');
        return false;
      }
      if (question.options.any((option) => option.text.text.trim().isEmpty)) {
        _snack('Sila isi semua pilihan jawapan bagi soalan $number.');
        return false;
      }
      final correct = question.options.where((option) => option.correct).length;
      if (question.type == _QuestionType.single && correct != 1) {
        _snack('Soalan $number mesti mempunyai tepat satu jawapan betul.');
        return false;
      }
      if (question.type == _QuestionType.multiple && correct < 1) {
        _snack(
          'Soalan $number mesti mempunyai sekurang-kurangnya satu jawapan betul.',
        );
        return false;
      }
    }
    return true;
  }

  Future<void> _save() async {
    if (!_validateDraft()) return;

    if (_editingQuiz != null && _editingQuiz!.attempts > 0) {
      final confirmed = await _confirmQuestionReplacement();
      if (!confirmed) return;
    }

    final durationText = _durationCtrl.text.trim();
    setState(() => _saving = true);
    try {
      final questions = _questions
          .map((question) => question.toApiDraft())
          .toList(growable: false);
      if (_isEditing) {
        await widget.repository.updateInteractiveQuiz(
          widget.quizId!,
          chapterId: _chapter!.id,
          title: _titleCtrl.text.trim(),
          description: _descriptionCtrl.text.trim(),
          durationMinutes: durationText.isEmpty
              ? null
              : int.parse(durationText),
          isPublished: _published,
          questions: questions,
        );
      } else {
        await widget.repository.createInteractiveQuiz(
          chapterId: _chapter!.id,
          title: _titleCtrl.text.trim(),
          description: _descriptionCtrl.text.trim(),
          durationMinutes: durationText.isEmpty
              ? null
              : int.parse(durationText),
          isPublished: _published,
          questions: questions,
        );
      }
      if (!mounted) return;
      _snack(
        _isEditing ? 'Kuiz berjaya dikemas kini.' : 'Kuiz berjaya disimpan.',
      );
      Navigator.of(context).pop(true);
    } catch (error) {
      if (mounted) {
        setState(() => _saving = false);
        _snack('$error');
      }
    }
  }

  Future<bool> _confirmQuestionReplacement() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Gantikan soalan kuiz?'),
        content: const Text(
          'Kuiz ini sudah mempunyai percubaan murid. Soalan baharu akan menggantikan semua soalan lama dan jawapan lama tidak lagi boleh dipaparkan. Markah serta ranking murid kekal.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Teruskan'),
          ),
        ],
      ),
    );
    return confirmed ?? false;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          _isEditing ? 'Sunting Kuiz Interaktif' : 'Cipta Kuiz Interaktif',
        ),
      ),
      body: _optionsError != null
          ? StateMessage(
              icon: Icons.wifi_off_outlined,
              title: 'Tidak dapat memuatkan',
              subtitle: '$_optionsError',
              onRetry: _loadOptions,
            )
          : _options == null
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 112),
              children: [
                _metadataCard(),
                const SizedBox(height: 24),
                Row(
                  children: [
                    Text(
                      'Soalan',
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    const Spacer(),
                    Text(
                      '${_questions.length} soalan',
                      style: const TextStyle(color: LmsColors.inkMuted),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                for (var index = 0; index < _questions.length; index++) ...[
                  _QuestionCard(
                    number: index + 1,
                    draft: _questions[index],
                    canRemove: _questions.length > 1,
                    onChanged: () => setState(() {}),
                    onRemove: () => _removeQuestion(index),
                  ),
                  const SizedBox(height: 12),
                ],
                OutlinedButton.icon(
                  onPressed: _addQuestion,
                  icon: const Icon(Icons.add),
                  label: const Text('Tambah soalan'),
                ),
              ],
            ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
          child: FilledButton.icon(
            onPressed: _saving || _loadingChapters || _chapter == null
                ? null
                : _save,
            icon: _saving
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white,
                    ),
                  )
                : const Icon(Icons.save_outlined),
            label: Text(_isEditing ? 'Simpan perubahan' : 'Simpan kuiz'),
          ),
        ),
      ),
    );
  }

  Widget _metadataCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (_editingQuiz != null && _editingQuiz!.attempts > 0) ...[
              const _AttemptWarning(),
              const SizedBox(height: 16),
            ],
            Text(
              'Maklumat kuiz',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: _optionDropdown(
                    'Subjek',
                    _options!.subjects,
                    _subject,
                    (value) {
                      setState(() => _subject = value);
                      _loadChapters();
                    },
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _optionDropdown('Tahun', _options!.grades, _grade, (
                    value,
                  ) {
                    setState(() => _grade = value);
                    _loadChapters();
                  }),
                ),
              ],
            ),
            const SizedBox(height: 12),
            if (_loadingChapters)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 8),
                child: LinearProgressIndicator(),
              )
            else if (_chapters.isEmpty)
              const Text(
                'Tiada bab untuk pasangan ini. Cipta bab dahulu di Kandungan › Bab.',
                style: TextStyle(color: LmsColors.inkMuted),
              )
            else
              DropdownButtonFormField<TeacherChapter>(
                key: ValueKey('chapter-${_chapter?.id}'),
                initialValue: _chapter,
                isExpanded: true,
                decoration: const InputDecoration(labelText: 'Bab'),
                items: _chapters
                    .map(
                      (chapter) => DropdownMenuItem(
                        value: chapter,
                        child: Text(
                          'Bab ${chapter.number}: ${chapter.title}',
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    )
                    .toList(growable: false),
                onChanged: (value) => setState(() => _chapter = value),
              ),
            const SizedBox(height: 12),
            TextField(
              controller: _titleCtrl,
              textCapitalization: TextCapitalization.sentences,
              decoration: const InputDecoration(labelText: 'Tajuk kuiz'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _descriptionCtrl,
              maxLines: 3,
              textCapitalization: TextCapitalization.sentences,
              decoration: const InputDecoration(
                labelText: 'Penerangan (pilihan)',
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _durationCtrl,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Masa kuiz (minit, pilihan)',
                hintText: 'Contoh: 20',
              ),
            ),
            const SizedBox(height: 4),
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Terbitkan'),
              subtitle: const Text('Murid boleh menjawab sebaik disimpan.'),
              value: _published,
              onChanged: (value) => setState(() => _published = value),
            ),
          ],
        ),
      ),
    );
  }

  Widget _optionDropdown(
    String label,
    List<OptionItem> items,
    OptionItem? value,
    ValueChanged<OptionItem?> onChanged,
  ) {
    return DropdownButtonFormField<OptionItem>(
      key: ValueKey('$label-${value?.id}'),
      initialValue: value,
      isExpanded: true,
      decoration: InputDecoration(
        labelText: label,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 12,
          vertical: 10,
        ),
      ),
      items: items
          .map(
            (item) => DropdownMenuItem(
              value: item,
              child: Text(item.name, overflow: TextOverflow.ellipsis),
            ),
          )
          .toList(growable: false),
      onChanged: onChanged,
    );
  }
}

class _AttemptWarning extends StatelessWidget {
  const _AttemptWarning();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF5DB),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE6C66B)),
      ),
      child: const Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.warning_amber_rounded, color: LmsColors.warning),
          SizedBox(width: 10),
          Expanded(
            child: Text(
              'Kuiz ini sudah ada percubaan. Menyimpan perubahan akan menggantikan set soalan lama.',
              style: TextStyle(fontSize: 12.5, color: LmsColors.ink),
            ),
          ),
        ],
      ),
    );
  }
}

enum _QuestionType { single, multiple }

class _QuestionDraft {
  _QuestionDraft({
    required this.text,
    required this.points,
    required this.type,
    required this.options,
  });

  factory _QuestionDraft.withDefaults() => _QuestionDraft(
    text: TextEditingController(),
    points: TextEditingController(text: '10'),
    type: _QuestionType.single,
    options: List.generate(4, (_) => _OptionDraft()),
  );

  factory _QuestionDraft.fromApiDraft(
    TeacherQuizQuestionDraft draft,
  ) => _QuestionDraft(
    text: TextEditingController(text: draft.questionText),
    points: TextEditingController(text: draft.points.toString()),
    type: draft.questionType == 'multiple'
        ? _QuestionType.multiple
        : _QuestionType.single,
    options: draft.options
        .map(
          (option) =>
              _OptionDraft(value: option.optionText, correct: option.isCorrect),
        )
        .toList(growable: false),
  );

  final TextEditingController text;
  final TextEditingController points;
  _QuestionType type;
  final List<_OptionDraft> options;

  TeacherQuizQuestionDraft toApiDraft() => TeacherQuizQuestionDraft(
    questionText: text.text.trim(),
    questionType: type == _QuestionType.single ? 'single' : 'multiple',
    points: int.parse(points.text.trim()),
    options: options
        .map(
          (option) => TeacherQuizOptionDraft(
            optionText: option.text.text.trim(),
            isCorrect: option.correct,
          ),
        )
        .toList(growable: false),
  );

  void dispose() {
    text.dispose();
    points.dispose();
    for (final option in options) {
      option.dispose();
    }
  }
}

class _OptionDraft {
  _OptionDraft({String? value, this.correct = false})
    : text = TextEditingController(text: value);

  final TextEditingController text;
  bool correct;

  void dispose() => text.dispose();
}

class _QuestionCard extends StatelessWidget {
  const _QuestionCard({
    required this.number,
    required this.draft,
    required this.canRemove,
    required this.onChanged,
    required this.onRemove,
  });

  final int number;
  final _QuestionDraft draft;
  final bool canRemove;
  final VoidCallback onChanged;
  final VoidCallback onRemove;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 30,
                  height: 30,
                  alignment: Alignment.center,
                  decoration: const BoxDecoration(
                    color: LmsColors.brandSoft,
                    shape: BoxShape.circle,
                  ),
                  child: Text(
                    '$number',
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                ),
                const SizedBox(width: 10),
                Text(
                  'Soalan $number',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const Spacer(),
                if (canRemove)
                  IconButton(
                    tooltip: 'Buang soalan',
                    onPressed: onRemove,
                    icon: const Icon(
                      Icons.delete_outline,
                      color: LmsColors.danger,
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 14),
            TextField(
              controller: draft.text,
              minLines: 2,
              maxLines: 4,
              textCapitalization: TextCapitalization.sentences,
              decoration: const InputDecoration(labelText: 'Teks soalan'),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  flex: 3,
                  child: SegmentedButton<_QuestionType>(
                    segments: const [
                      ButtonSegment(
                        value: _QuestionType.single,
                        icon: Icon(Icons.radio_button_checked_outlined),
                        label: Text('Satu'),
                      ),
                      ButtonSegment(
                        value: _QuestionType.multiple,
                        icon: Icon(Icons.check_box_outlined),
                        label: Text('Pelbagai'),
                      ),
                    ],
                    selected: {draft.type},
                    showSelectedIcon: false,
                    onSelectionChanged: (selection) {
                      draft.type = selection.first;
                      if (draft.type == _QuestionType.single) {
                        var foundCorrect = false;
                        for (final option in draft.options) {
                          if (option.correct && !foundCorrect) {
                            foundCorrect = true;
                          } else {
                            option.correct = false;
                          }
                        }
                      }
                      onChanged();
                    },
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  flex: 2,
                  child: TextField(
                    controller: draft.points,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Markah'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            const Text(
              'Pilihan jawapan',
              style: TextStyle(fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 4),
            Text(
              draft.type == _QuestionType.single
                  ? 'Pilih satu jawapan betul.'
                  : 'Tandakan semua jawapan yang betul.',
              style: const TextStyle(fontSize: 12, color: LmsColors.inkMuted),
            ),
            const SizedBox(height: 8),
            if (draft.type == _QuestionType.single)
              RadioGroup<int>(
                groupValue: draft.options.indexWhere(
                  (option) => option.correct,
                ),
                onChanged: (index) {
                  if (index == null) return;
                  for (
                    var optionIndex = 0;
                    optionIndex < draft.options.length;
                    optionIndex++
                  ) {
                    draft.options[optionIndex].correct = optionIndex == index;
                  }
                  onChanged();
                },
                child: Column(
                  children: [
                    for (var index = 0; index < draft.options.length; index++)
                      _OptionRow(
                        index: index,
                        draft: draft,
                        canRemove: draft.options.length > 2,
                        onChanged: onChanged,
                      ),
                  ],
                ),
              )
            else
              Column(
                children: [
                  for (var index = 0; index < draft.options.length; index++)
                    _OptionRow(
                      index: index,
                      draft: draft,
                      canRemove: draft.options.length > 2,
                      onChanged: onChanged,
                    ),
                ],
              ),
            if (draft.options.length < _QuizBuilderScreenState._maxOptions)
              Align(
                alignment: Alignment.centerLeft,
                child: TextButton.icon(
                  onPressed: () {
                    draft.options.add(_OptionDraft());
                    onChanged();
                  },
                  icon: const Icon(Icons.add, size: 18),
                  label: const Text('Tambah pilihan'),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _OptionRow extends StatelessWidget {
  const _OptionRow({
    required this.index,
    required this.draft,
    required this.canRemove,
    required this.onChanged,
  });

  final int index;
  final _QuestionDraft draft;
  final bool canRemove;
  final VoidCallback onChanged;

  @override
  Widget build(BuildContext context) {
    final option = draft.options[index];
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          if (draft.type == _QuestionType.single)
            Radio<int>(value: index)
          else
            Checkbox(
              value: option.correct,
              onChanged: (value) {
                option.correct = value ?? false;
                onChanged();
              },
            ),
          Expanded(
            child: TextField(
              controller: option.text,
              decoration: InputDecoration(
                labelText: 'Pilihan ${String.fromCharCode(65 + index)}',
              ),
            ),
          ),
          if (canRemove)
            IconButton(
              tooltip: 'Buang pilihan',
              onPressed: () {
                final removed = draft.options.removeAt(index);
                removed.dispose();
                onChanged();
              },
              icon: const Icon(Icons.close, size: 20),
            )
          else
            const SizedBox(width: 48),
        ],
      ),
    );
  }
}
