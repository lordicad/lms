import 'package:flutter/material.dart';

import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../student/widgets/content_widgets.dart';

/// Add a YouTube video: pick Subject -> Tahun -> Bab, then title, description and the
/// YouTube link. Upload-from-device comes in a later phase. Pops `true` on success.
class VideoFormScreen extends StatefulWidget {
  const VideoFormScreen({super.key, required this.repository});

  final TeacherRepository repository;

  @override
  State<VideoFormScreen> createState() => _VideoFormScreenState();
}

class _VideoFormScreenState extends State<VideoFormScreen> {
  final _titleCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  final _urlCtrl = TextEditingController();
  bool _published = true;
  bool _saving = false;

  TeacherOptions? _options;
  Object? _optionsError;
  OptionItem? _subject;
  OptionItem? _grade;

  List<TeacherChapter> _chapters = [];
  TeacherChapter? _chapter;
  bool _loadingChapters = false;

  @override
  void initState() {
    super.initState();
    _loadOptions();
  }

  @override
  void dispose() {
    _titleCtrl.dispose();
    _descCtrl.dispose();
    _urlCtrl.dispose();
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
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('$e')));
      }
    }
  }

  void _snack(String message) => ScaffoldMessenger.of(
    context,
  ).showSnackBar(SnackBar(content: Text(message)));

  Future<void> _save() async {
    final title = _titleCtrl.text.trim();
    final url = _urlCtrl.text.trim();
    if (_chapter == null) return _snack('Sila pilih Bab.');
    if (title.isEmpty) return _snack('Sila isi tajuk video.');
    if (url.isEmpty) return _snack('Sila isi pautan YouTube.');

    setState(() => _saving = true);
    try {
      await widget.repository.createVideo(
        chapterId: _chapter!.id,
        title: title,
        description: _descCtrl.text.trim(),
        youtubeUrl: url,
        isPublished: _published,
      );
      if (!mounted) return;
      _snack('Video berjaya disimpan.');
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
      appBar: AppBar(title: const Text('Tambah Video')),
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
                        (v) {
                          setState(() => _subject = v);
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
                        (v) {
                          setState(() => _grade = v);
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
                          (c) => DropdownMenuItem(
                            value: c,
                            child: Text(
                              'Bab ${c.number}: ${c.title}',
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        )
                        .toList(),
                    onChanged: (v) => setState(() => _chapter = v),
                  ),
                const SizedBox(height: 16),
                TextField(
                  controller: _titleCtrl,
                  decoration: const InputDecoration(labelText: 'Tajuk video'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _descCtrl,
                  maxLines: 3,
                  decoration: const InputDecoration(
                    labelText: 'Penerangan (pilihan)',
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _urlCtrl,
                  keyboardType: TextInputType.url,
                  decoration: const InputDecoration(
                    labelText: 'Pautan YouTube',
                    hintText: 'https://youtu.be/…',
                  ),
                ),
                const SizedBox(height: 4),
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  title: const Text('Terbitkan'),
                  subtitle: const Text('Murid boleh menonton sebaik disimpan.'),
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
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white,
                    ),
                  )
                : const Icon(Icons.save_outlined),
            label: const Text('Simpan video'),
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
