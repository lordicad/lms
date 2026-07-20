import 'package:flutter/material.dart';

import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../student/widgets/content_widgets.dart';
import 'teacher_chapter_detail_screen.dart';

/// Mirrors the web Bab page: chapters are shared curriculum taxonomy. A teacher
/// picks a year and subject, then opens a Bab to see only their own content.
class ChaptersManageTab extends StatefulWidget {
  const ChaptersManageTab({super.key, required this.repository});

  final TeacherRepository repository;

  @override
  State<ChaptersManageTab> createState() => _ChaptersManageTabState();
}

class _ChaptersManageTabState extends State<ChaptersManageTab> {
  TeacherOptions? _options;
  Object? _optionsError;
  OptionItem? _subject;
  OptionItem? _grade;
  Future<ChaptersData>? _chapters;

  @override
  void initState() {
    super.initState();
    _loadOptions();
  }

  Future<void> _loadOptions() async {
    setState(() => _optionsError = null);
    try {
      final options = await widget.repository.options();
      if (!mounted) return;
      setState(() {
        _options = options;
        _grade = options.grades.isNotEmpty ? options.grades.first : null;
        _subject = options.subjects.isNotEmpty ? options.subjects.first : null;
      });
      _reloadChapters();
    } catch (error) {
      if (mounted) setState(() => _optionsError = error);
    }
  }

  void _reloadChapters() {
    if (_subject == null || _grade == null) return;
    setState(() {
      _chapters = widget.repository.chapters(_subject!.id, _grade!.id);
    });
  }

  Future<void> _openChapter(TeacherChapter chapter) async {
    final subject = _subject;
    final grade = _grade;
    if (subject == null || grade == null) return;
    await Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) => TeacherChapterDetailScreen(
          repository: widget.repository,
          chapter: chapter,
          subjectName: subject.name,
          gradeName: grade.name,
        ),
      ),
    );
    _reloadChapters();
  }

  @override
  Widget build(BuildContext context) {
    if (_optionsError != null) {
      return StateMessage(
        icon: Icons.wifi_off_outlined,
        title: 'Tidak dapat memuatkan',
        subtitle: '$_optionsError',
        onRetry: _loadOptions,
      );
    }

    final options = _options;
    if (options == null)
      return const Center(child: CircularProgressIndicator());

    return Column(
      children: [
        const Padding(
          padding: EdgeInsets.fromLTRB(20, 10, 20, 0),
          child: Text(
            'Pilih Tahun dan Subjek, kemudian lihat kandungan anda dalam setiap Bab.',
            style: TextStyle(fontSize: 12, color: LmsColors.inkMuted),
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 8),
          child: Row(
            children: [
              Expanded(
                child: _dropdown('Tahun', options.grades, _grade, (value) {
                  setState(() => _grade = value);
                  _reloadChapters();
                }),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _dropdown('Subjek', options.subjects, _subject, (value) {
                  setState(() => _subject = value);
                  _reloadChapters();
                }),
              ),
            ],
          ),
        ),
        Expanded(
          child: _chapters == null
              ? const SizedBox()
              : FutureBuilder<ChaptersData>(
                  future: _chapters,
                  builder: (context, snapshot) {
                    if (snapshot.connectionState == ConnectionState.waiting) {
                      return const Center(child: CircularProgressIndicator());
                    }
                    if (snapshot.hasError) {
                      return StateMessage(
                        icon: Icons.error_outline,
                        title: 'Tidak dapat memuatkan Bab',
                        subtitle: '${snapshot.error}',
                        onRetry: _reloadChapters,
                      );
                    }

                    final chapters = snapshot.data!.chapters;
                    if (chapters.isEmpty) {
                      return const StateMessage(
                        icon: Icons.inbox_outlined,
                        title: 'Tiada Bab',
                        subtitle:
                            'Bab bagi Tahun dan Subjek ini belum tersedia.',
                      );
                    }
                    return ListView.separated(
                      padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
                      itemCount: chapters.length,
                      separatorBuilder: (_, _) => const SizedBox(height: 8),
                      itemBuilder: (context, index) => _ChapterTile(
                        chapter: chapters[index],
                        onTap: () => _openChapter(chapters[index]),
                      ),
                    );
                  },
                ),
        ),
      ],
    );
  }

  Widget _dropdown(
    String label,
    List<OptionItem> items,
    OptionItem? value,
    ValueChanged<OptionItem?> onChanged,
  ) => DropdownButtonFormField<OptionItem>(
    key: ValueKey('$label-${value?.id}'),
    initialValue: value,
    isExpanded: true,
    decoration: InputDecoration(
      labelText: label,
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
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

class _ChapterTile extends StatelessWidget {
  const _ChapterTile({required this.chapter, required this.onTap});

  final TeacherChapter chapter;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Material(
    color: Colors.transparent,
    child: InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Ink(
        padding: const EdgeInsets.all(13),
        decoration: BoxDecoration(
          color: LmsColors.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: LmsColors.border),
        ),
        child: Row(
          children: [
            CircleAvatar(
              radius: 18,
              backgroundColor: LmsColors.brandSoft,
              foregroundColor: LmsColors.brand,
              child: Text(
                '${chapter.number}',
                style: const TextStyle(fontWeight: FontWeight.w800),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    chapter.title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  const SizedBox(height: 3),
                  Text(
                    '${chapter.lessonsCount} video · ${chapter.materialsCount} bahan · ${chapter.quizzesCount} kuiz',
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right_rounded, color: LmsColors.inkFaint),
          ],
        ),
      ),
    ),
  );
}
