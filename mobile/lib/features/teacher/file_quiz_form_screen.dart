import 'package:flutter/material.dart';

import '../../core/platform/native_file_picker.dart';
import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../../core/widgets/app_feedback.dart';
import '../student/widgets/content_widgets.dart';

/// Upload a printable "kuiz fail" (PDF/DOC/DOCX) that students download instead of answering
/// in-app — the file counterpart to the interactive quiz builder. Pops `true` on success.
class FileQuizFormScreen extends StatefulWidget {
  const FileQuizFormScreen({super.key, required this.repository});

  final TeacherRepository repository;

  @override
  State<FileQuizFormScreen> createState() => _FileQuizFormScreenState();
}

class _FileQuizFormScreenState extends State<FileQuizFormScreen> {
  final _titleCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  bool _published = true;
  bool _saving = false;

  TeacherOptions? _options;
  Object? _optionsError;
  OptionItem? _subject;
  OptionItem? _grade;

  List<TeacherChapter> _chapters = [];
  TeacherChapter? _chapter;
  bool _loadingChapters = false;

  NativeUploadFile? _file;

  @override
  void initState() {
    super.initState();
    _loadOptions();
  }

  @override
  void dispose() {
    _titleCtrl.dispose();
    _descCtrl.dispose();
    super.dispose();
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
      _loadChapters();
    } catch (e) {
      if (mounted) setState(() => _optionsError = e);
    }
  }

  Future<void> _loadChapters() async {
    if (_subject == null || _grade == null) return;
    setState(() {
      _loadingChapters = true;
      _chapters = [];
      _chapter = null;
    });
    try {
      final data = await widget.repository.chapters(_subject!.id, _grade!.id);
      if (!mounted) return;
      setState(() {
        _chapters = data.chapters;
        _chapter = data.chapters.isNotEmpty ? data.chapters.first : null;
        _loadingChapters = false;
      });
    } catch (e) {
      if (mounted) {
        setState(() => _loadingChapters = false);
        _snack('$e');
      }
    }
  }

  void _snack(String message) =>
      AppFeedback.error('Tidak dapat diteruskan', description: message);

  Future<void> _pickFile() async {
    try {
      final file = await NativeFilePicker.pickMaterial();
      if (file == null || !mounted) return;
      setState(() => _file = file);
    } catch (e) {
      if (mounted) _snack('$e');
    }
  }

  Future<void> _save() async {
    final title = _titleCtrl.text.trim();
    if (_chapter == null) return _snack('Sila pilih Bab.');
    if (title.isEmpty) return _snack('Sila isi tajuk kuiz.');
    if (_file == null) return _snack('Sila pilih fail kuiz.');

    setState(() => _saving = true);
    try {
      await widget.repository.createFileQuiz(
        chapterId: _chapter!.id,
        title: title,
        description: _descCtrl.text.trim(),
        file: _file!,
        isPublished: _published,
      );
      if (!mounted) return;
      AppFeedback.success(
        'Kuiz fail berjaya dimuat naik',
        description: 'Murid boleh memuat turunnya dari Kuiz.',
      );
      Navigator.of(context).pop(true);
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        _snack('$e');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Kuiz Fail')),
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
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: _optionDropdown('Subjek', _options!.subjects, _subject, (v) {
                            setState(() => _subject = v);
                            _loadChapters();
                          }),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _optionDropdown('Tahun', _options!.grades, _grade, (v) {
                            setState(() => _grade = v);
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
                      const Padding(
                        padding: EdgeInsets.symmetric(vertical: 8),
                        child: Text(
                          'Tiada bab untuk pasangan ini. Cipta bab dahulu di Kandungan › Bab.',
                          style: TextStyle(color: LmsColors.inkMuted),
                        ),
                      )
                    else
                      DropdownButtonFormField<TeacherChapter>(
                        initialValue: _chapter,
                        isExpanded: true,
                        decoration: const InputDecoration(labelText: 'Bab'),
                        items: _chapters
                            .map((c) => DropdownMenuItem(
                                  value: c,
                                  child: Text('Bab ${c.number}: ${c.title}',
                                      overflow: TextOverflow.ellipsis),
                                ))
                            .toList(),
                        onChanged: (v) => setState(() => _chapter = v),
                      ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: _titleCtrl,
                      decoration: const InputDecoration(labelText: 'Tajuk kuiz'),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _descCtrl,
                      maxLines: 3,
                      decoration: const InputDecoration(labelText: 'Penerangan (pilihan)'),
                    ),
                    const SizedBox(height: 12),
                    _FilePickerRow(
                      file: _file,
                      onPick: _pickFile,
                      onClear: () => setState(() => _file = null),
                    ),
                    const SizedBox(height: 4),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Terbitkan'),
                      subtitle: const Text('Murid boleh memuat turun sebaik disimpan.'),
                      value: _published,
                      onChanged: (v) => setState(() => _published = v),
                    ),
                  ],
                ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
          child: FilledButton.icon(
            onPressed: _saving ? null : _save,
            icon: _saving
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Icon(Icons.upload_file),
            label: const Text('Muat naik kuiz'),
          ),
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
      initialValue: value,
      isExpanded: true,
      decoration: InputDecoration(
        labelText: label,
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      ),
      items: items
          .map((o) => DropdownMenuItem(value: o, child: Text(o.name, overflow: TextOverflow.ellipsis)))
          .toList(),
      onChanged: onChanged,
    );
  }
}

class _FilePickerRow extends StatelessWidget {
  const _FilePickerRow({required this.file, required this.onPick, required this.onClear});

  final NativeUploadFile? file;
  final VoidCallback onPick;
  final VoidCallback onClear;

  @override
  Widget build(BuildContext context) {
    final chosen = file;

    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      padding: const EdgeInsets.all(12),
      child: chosen == null
          ? Row(
              children: [
                const Icon(Icons.description_outlined, color: LmsColors.brand),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Pilih fail kuiz (PDF, DOC atau DOCX)',
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                ),
                TextButton(onPressed: onPick, child: const Text('Pilih')),
              ],
            )
          : Row(
              children: [
                const Icon(Icons.description_outlined, color: LmsColors.brand),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        chosen.name,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      Text(_size(chosen.sizeBytes),
                          style: Theme.of(context).textTheme.bodyMedium),
                    ],
                  ),
                ),
                IconButton(
                  tooltip: 'Buang',
                  onPressed: onClear,
                  icon: const Icon(Icons.close),
                ),
              ],
            ),
    );
  }

  static String _size(int bytes) {
    if (bytes <= 0) return '';
    final mb = bytes / (1024 * 1024);
    return mb >= 1 ? '${mb.toStringAsFixed(1)} MB' : '${(bytes / 1024).round()} KB';
  }
}
