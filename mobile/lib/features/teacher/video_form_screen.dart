import 'package:flutter/material.dart';

import '../../core/platform/native_file_picker.dart';
import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../../core/widgets/app_feedback.dart';
import '../student/widgets/content_widgets.dart';

/// Where a new video comes from. Editing keeps whatever source the lesson already has.
enum _VideoSource { youtube, upload }

/// Add or edit a YouTube video: pick Subject -> Tahun -> Bab, then title, description and
/// the YouTube link. Upload-from-device comes in a later phase. Pops `true` on success.
class VideoFormScreen extends StatefulWidget {
  const VideoFormScreen({super.key, required this.repository, this.video});

  final TeacherRepository repository;
  final TeacherVideo? video;

  @override
  State<VideoFormScreen> createState() => _VideoFormScreenState();
}

class _VideoFormScreenState extends State<VideoFormScreen> {
  final _titleCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  final _urlCtrl = TextEditingController();
  bool _published = true;
  bool _saving = false;

  _VideoSource _source = _VideoSource.youtube;
  NativeUploadFile? _videoFile;

  TeacherOptions? _options;
  Object? _optionsError;
  OptionItem? _subject;
  OptionItem? _grade;

  List<TeacherChapter> _chapters = [];
  TeacherChapter? _chapter;
  bool _loadingChapters = false;
  int? _pendingChapterId;

  @override
  void initState() {
    super.initState();
    final video = widget.video;
    _titleCtrl.text = video?.title ?? '';
    _descCtrl.text = video?.description ?? '';
    _urlCtrl.text = video?.youtubeUrl ?? '';
    _published = video?.published ?? true;
    _pendingChapterId = video?.chapterId;
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
        _subject =
            _optionById(options.subjects, widget.video?.subjectId) ??
            (options.subjects.isNotEmpty ? options.subjects.first : null);
        _grade =
            _optionById(options.grades, widget.video?.gradeId) ??
            (options.grades.isNotEmpty ? options.grades.first : null);
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
        _chapter =
            _chapterById(data.chapters, _pendingChapterId) ??
            (data.chapters.isNotEmpty ? data.chapters.first : null);
        _pendingChapterId = null;
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

  void _snack(String message) =>
      AppFeedback.error('Tidak dapat diteruskan', description: message);

  Future<void> _pickVideoFile() async {
    try {
      final file = await NativeFilePicker.pickVideo();
      if (file == null || !mounted) return;
      setState(() => _videoFile = file);
    } catch (e) {
      if (mounted) _snack('$e');
    }
  }

  Future<void> _save() async {
    final title = _titleCtrl.text.trim();
    final url = _urlCtrl.text.trim();
    if (_chapter == null) return _snack('Sila pilih Bab.');
    if (title.isEmpty) return _snack('Sila isi tajuk video.');

    final uploading = widget.video == null && _source == _VideoSource.upload;
    if (uploading && _videoFile == null) {
      return _snack('Sila pilih fail video untuk dimuat naik.');
    }
    if (!uploading && url.isEmpty) return _snack('Sila isi pautan YouTube.');

    setState(() => _saving = true);
    try {
      final video = widget.video;
      if (video == null && uploading) {
        await widget.repository.createVideoUpload(
          chapterId: _chapter!.id,
          title: title,
          description: _descCtrl.text.trim(),
          file: _videoFile!,
          isPublished: _published,
        );
      } else if (video == null) {
        await widget.repository.createVideo(
          chapterId: _chapter!.id,
          title: title,
          description: _descCtrl.text.trim(),
          youtubeUrl: url,
          isPublished: _published,
        );
      } else {
        await widget.repository.updateVideo(
          video.id,
          chapterId: _chapter!.id,
          title: title,
          description: _descCtrl.text.trim(),
          youtubeUrl: url,
          isPublished: _published,
        );
      }
      if (!mounted) return;
      AppFeedback.success(
        video == null ? 'Video berjaya disimpan' : 'Video berjaya dikemas kini',
        description: video == null
            ? 'Video kini tersedia dalam Kandungan.'
            : 'Perubahan video telah diterapkan.',
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
    final editing = widget.video != null;
    return Scaffold(
      appBar: AppBar(title: Text(editing ? 'Sunting Video' : 'Tambah Video')),
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
                // Source is only choosable when creating; editing keeps the lesson's source.
                if (!editing) ...[
                  SegmentedButton<_VideoSource>(
                    segments: const [
                      ButtonSegment(
                        value: _VideoSource.youtube,
                        icon: Icon(Icons.link),
                        label: Text('YouTube'),
                      ),
                      ButtonSegment(
                        value: _VideoSource.upload,
                        icon: Icon(Icons.upload_file),
                        label: Text('Muat naik'),
                      ),
                    ],
                    selected: {_source},
                    onSelectionChanged: (s) => setState(() => _source = s.first),
                    showSelectedIcon: false,
                  ),
                  const SizedBox(height: 12),
                ],
                if (editing || _source == _VideoSource.youtube)
                  TextField(
                    controller: _urlCtrl,
                    keyboardType: TextInputType.url,
                    decoration: const InputDecoration(
                      labelText: 'Pautan YouTube',
                      hintText: 'https://youtu.be/…',
                    ),
                  )
                else
                  _VideoFilePicker(
                    file: _videoFile,
                    onPick: _pickVideoFile,
                    onClear: () => setState(() => _videoFile = null),
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
            label: Text(editing ? 'Simpan perubahan' : 'Simpan video'),
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

/// Picks an MP4/WEBM from the device and shows what was chosen.
class _VideoFilePicker extends StatelessWidget {
  const _VideoFilePicker({
    required this.file,
    required this.onPick,
    required this.onClear,
  });

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
                const Icon(Icons.movie_outlined, color: LmsColors.brand),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Pilih fail video (MP4 atau WEBM)',
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                ),
                TextButton(onPressed: onPick, child: const Text('Pilih')),
              ],
            )
          : Row(
              children: [
                const Icon(Icons.movie_outlined, color: LmsColors.brand),
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
                      Text(
                        _size(chosen.sizeBytes),
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
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
