import 'package:flutter/material.dart';

import '../../core/platform/native_file_picker.dart';
import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../student/widgets/content_widgets.dart';

/// Upload or edit a teacher-owned learning material.
class MaterialFormScreen extends StatefulWidget {
  const MaterialFormScreen({
    super.key,
    required this.repository,
    this.material,
  });

  final TeacherRepository repository;
  final TeacherMaterial? material;

  @override
  State<MaterialFormScreen> createState() => _MaterialFormScreenState();
}

class _MaterialFormScreenState extends State<MaterialFormScreen> {
  final _titleCtrl = TextEditingController();
  TeacherOptions? _options;
  Object? _optionsError;
  OptionItem? _subject;
  OptionItem? _grade;
  List<TeacherChapter> _chapters = [];
  TeacherChapter? _chapter;
  NativeUploadFile? _file;
  int? _pendingChapterId;
  var _loadingChapters = false;
  var _saving = false;

  @override
  void initState() {
    super.initState();
    _titleCtrl.text = widget.material?.title ?? '';
    _pendingChapterId = widget.material?.chapterId;
    _loadOptions();
  }

  @override
  void dispose() {
    _titleCtrl.dispose();
    super.dispose();
  }

  void _snack(String message) => ScaffoldMessenger.of(
    context,
  ).showSnackBar(SnackBar(content: Text(message)));

  Future<void> _loadOptions() async {
    setState(() => _optionsError = null);
    try {
      final options = await widget.repository.options();
      if (!mounted) return;
      setState(() {
        _options = options;
        _subject =
            _optionById(options.subjects, widget.material?.subjectId) ??
            (options.subjects.isNotEmpty ? options.subjects.first : null);
        _grade =
            _optionById(options.grades, widget.material?.gradeId) ??
            (options.grades.isNotEmpty ? options.grades.first : null);
      });
      _loadChapters();
    } catch (error) {
      if (mounted) setState(() => _optionsError = error);
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
        _chapter =
            _chapterById(data.chapters, _pendingChapterId) ??
            (data.chapters.isNotEmpty ? data.chapters.first : null);
        _pendingChapterId = null;
        _loadingChapters = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() => _loadingChapters = false);
      _snack('$error');
    }
  }

  Future<void> _pickFile() async {
    try {
      final result = await NativeFilePicker.pickMaterial();
      if (result != null && mounted) setState(() => _file = result);
    } catch (error) {
      if (mounted) _snack('$error');
    }
  }

  Future<void> _save() async {
    final title = _titleCtrl.text.trim();
    final material = widget.material;
    if (_chapter == null) return _snack('Sila pilih Bab.');
    if (title.isEmpty) return _snack('Sila isi tajuk bahan.');
    if (material == null && _file == null) {
      return _snack('Sila pilih fail untuk dimuat naik.');
    }

    setState(() => _saving = true);
    try {
      if (material == null) {
        await widget.repository.createMaterial(
          chapterId: _chapter!.id,
          title: title,
          file: _file!,
        );
      } else {
        await widget.repository.updateMaterial(
          material.id,
          chapterId: _chapter!.id,
          title: title,
          file: _file,
        );
      }
      if (!mounted) return;
      _snack(
        material == null
            ? 'Bahan berjaya dimuat naik.'
            : 'Bahan berjaya dikemas kini.',
      );
      Navigator.of(context).pop(true);
    } catch (error) {
      if (mounted) {
        setState(() => _saving = false);
        _snack('$error');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final editing = widget.material != null;
    return Scaffold(
      appBar: AppBar(title: Text(editing ? 'Sunting Bahan' : 'Tambah Bahan')),
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
                      child: _optionDropdown(
                        'Tahun',
                        _options!.grades,
                        _grade,
                        (value) {
                          setState(() => _grade = value);
                          _loadChapters();
                        },
                      ),
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
                        .map(
                          (chapter) => DropdownMenuItem(
                            value: chapter,
                            child: Text(
                              'Bab ${chapter.number}: ${chapter.title}',
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        )
                        .toList(),
                    onChanged: (value) => setState(() => _chapter = value),
                  ),
                const SizedBox(height: 16),
                TextField(
                  controller: _titleCtrl,
                  decoration: const InputDecoration(labelText: 'Tajuk bahan'),
                ),
                const SizedBox(height: 16),
                OutlinedButton.icon(
                  onPressed: _pickFile,
                  icon: const Icon(Icons.attach_file_rounded),
                  label: Text(_file == null ? 'Pilih fail' : 'Tukar fail'),
                  style: OutlinedButton.styleFrom(
                    minimumSize: const Size.fromHeight(52),
                  ),
                ),
                const SizedBox(height: 10),
                _FileStatus(file: _file, existing: widget.material),
                const SizedBox(height: 10),
                const Text(
                  'Format: PDF, PowerPoint, Word, Excel atau imej. Had maksimum 30 MB.',
                  style: TextStyle(fontSize: 11.5, color: LmsColors.inkMuted),
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
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white,
                    ),
                  )
                : const Icon(Icons.cloud_upload_outlined),
            label: Text(
              _saving
                  ? 'Menyimpan...'
                  : editing
                  ? 'Simpan perubahan'
                  : 'Muat naik bahan',
            ),
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
          .toList(),
      onChanged: onChanged,
    );
  }

  OptionItem? _optionById(List<OptionItem> options, int? id) {
    if (id == null) return null;
    for (final option in options) {
      if (option.id == id) return option;
    }
    return null;
  }

  TeacherChapter? _chapterById(List<TeacherChapter> chapters, int? id) {
    if (id == null) return null;
    for (final chapter in chapters) {
      if (chapter.id == id) return chapter;
    }
    return null;
  }
}

class _FileStatus extends StatelessWidget {
  const _FileStatus({required this.file, required this.existing});

  final NativeUploadFile? file;
  final TeacherMaterial? existing;

  @override
  Widget build(BuildContext context) {
    final label =
        file?.name ??
        (existing == null
            ? 'Belum ada fail dipilih'
            : 'Fail semasa: ${existing!.extension.toUpperCase()} · ${existing!.humanSize}');
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: LmsColors.surfaceMuted,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          const Icon(Icons.description_outlined, color: LmsColors.brandStrong),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              label,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 12, color: LmsColors.inkMuted),
            ),
          ),
        ],
      ),
    );
  }
}
