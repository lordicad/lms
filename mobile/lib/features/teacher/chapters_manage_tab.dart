import 'package:flutter/material.dart';

import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../student/widgets/content_widgets.dart';

/// Teacher Bab management: pick a Subject + Tahun, then add, rename or remove a Bab.
/// The Bab number is server-assigned; only an empty Bab can be deleted.
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
        _subject = options.subjects.isNotEmpty ? options.subjects.first : null;
        _grade = options.grades.isNotEmpty ? options.grades.first : null;
      });
      _reloadChapters();
    } catch (e) {
      if (mounted) setState(() => _optionsError = e);
    }
  }

  void _reloadChapters() {
    if (_subject == null || _grade == null) return;
    setState(() {
      _chapters = widget.repository.chapters(_subject!.id, _grade!.id);
    });
  }

  Future<void> _showChapterDialog({TeacherChapter? existing}) async {
    final titleCtrl = TextEditingController(text: existing?.title ?? '');
    final descCtrl = TextEditingController();

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(
          existing == null
              ? 'Tambah Bab'
              : 'Namakan semula Bab ${existing.number}',
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: titleCtrl,
              autofocus: true,
              decoration: const InputDecoration(labelText: 'Tajuk Bab'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: descCtrl,
              maxLines: 2,
              decoration: const InputDecoration(
                labelText: 'Penerangan (pilihan)',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Simpan'),
          ),
        ],
      ),
    );

    if (ok != true) return;
    final title = titleCtrl.text.trim();
    if (title.isEmpty) return;

    try {
      if (existing == null) {
        await widget.repository.createChapter(
          subjectId: _subject!.id,
          gradeId: _grade!.id,
          title: title,
          description: descCtrl.text.trim(),
        );
      } else {
        await widget.repository.updateChapter(
          existing.id,
          title: title,
          description: descCtrl.text.trim(),
        );
      }
      _reloadChapters();
    } catch (e) {
      if (mounted)
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  Future<void> _deleteChapter(TeacherChapter chapter) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Padam Bab?'),
        content: Text('Padam Bab ${chapter.number}: ${chapter.title}?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Batal'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: LmsColors.danger),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Padam'),
          ),
        ],
      ),
    );
    if (ok != true) return;

    try {
      await widget.repository.deleteChapter(chapter.id);
      _reloadChapters();
    } catch (e) {
      if (mounted)
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('$e')));
    }
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
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 8),
          child: Row(
            children: [
              Expanded(
                child: _dropdown('Subjek', options.subjects, _subject, (v) {
                  setState(() => _subject = v);
                  _reloadChapters();
                }),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _dropdown('Tahun', options.grades, _grade, (v) {
                  setState(() => _grade = v);
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
                  builder: (context, snap) {
                    if (snap.connectionState == ConnectionState.waiting) {
                      return const Center(child: CircularProgressIndicator());
                    }
                    if (snap.hasError) {
                      return StateMessage(
                        icon: Icons.error_outline,
                        title: 'Tidak dapat memuatkan bab',
                        subtitle: '${snap.error}',
                        onRetry: _reloadChapters,
                      );
                    }

                    final data = snap.data!;
                    return Column(
                      children: [
                        Expanded(
                          child: data.chapters.isEmpty
                              ? StateMessage(
                                  icon: Icons.inbox_outlined,
                                  title: 'Belum ada bab',
                                  subtitle: data.isOffered
                                      ? 'Tambah bab pertama di bawah.'
                                      : 'Subjek ini tidak ditawarkan untuk Tahun ini.',
                                )
                              : ListView.separated(
                                  padding: const EdgeInsets.fromLTRB(
                                    20,
                                    4,
                                    20,
                                    12,
                                  ),
                                  itemCount: data.chapters.length,
                                  separatorBuilder: (_, _) =>
                                      const SizedBox(height: 8),
                                  itemBuilder: (context, i) => _ChapterTile(
                                    chapter: data.chapters[i],
                                    onRename: () => _showChapterDialog(
                                      existing: data.chapters[i],
                                    ),
                                    onDelete: () =>
                                        _deleteChapter(data.chapters[i]),
                                  ),
                                ),
                        ),
                        if (data.isOffered)
                          SafeArea(
                            top: false,
                            child: Padding(
                              padding: const EdgeInsets.fromLTRB(20, 4, 20, 12),
                              child: FilledButton.icon(
                                onPressed: () => _showChapterDialog(),
                                icon: const Icon(Icons.add),
                                label: const Text('Tambah Bab'),
                              ),
                            ),
                          ),
                      ],
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
  ) {
    return DropdownButtonFormField<OptionItem>(
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
            (o) => DropdownMenuItem(
              value: o,
              child: Text(o.name, overflow: TextOverflow.ellipsis),
            ),
          )
          .toList(),
      onChanged: onChanged,
    );
  }
}

class _ChapterTile extends StatelessWidget {
  const _ChapterTile({
    required this.chapter,
    required this.onRename,
    required this.onDelete,
  });

  final TeacherChapter chapter;
  final VoidCallback onRename;
  final VoidCallback onDelete;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: LmsColors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: LmsColors.border),
      ),
      padding: const EdgeInsets.all(12),
      child: Row(
        children: [
          CircleAvatar(
            radius: 17,
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
                const SizedBox(height: 2),
                Text(
                  '${chapter.lessonsCount} video · ${chapter.materialsCount} bahan · ${chapter.quizzesCount} kuiz',
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              ],
            ),
          ),
          PopupMenuButton<String>(
            icon: const Icon(Icons.more_vert, color: LmsColors.inkFaint),
            onSelected: (v) {
              if (v == 'rename') onRename();
              if (v == 'delete') onDelete();
            },
            itemBuilder: (_) => const [
              PopupMenuItem(
                value: 'rename',
                child: Row(
                  children: [
                    Icon(Icons.edit_outlined, size: 18),
                    SizedBox(width: 10),
                    Text('Namakan semula'),
                  ],
                ),
              ),
              PopupMenuItem(
                value: 'delete',
                child: Row(
                  children: [
                    Icon(
                      Icons.delete_outline,
                      size: 18,
                      color: LmsColors.danger,
                    ),
                    SizedBox(width: 10),
                    Text('Padam', style: TextStyle(color: LmsColors.danger)),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
