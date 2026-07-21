import 'package:flutter/material.dart';

import '../../core/content/content_models.dart';
import '../../core/content/content_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../../core/widgets/loading_skeleton.dart';
import 'widgets/content_widgets.dart';

/// Flutter equivalent of the web Simpanan Offline page. Downloads are delegated to
/// Android's Downloads app, so files remain available outside the LMS too.
class OfflineTab extends StatefulWidget {
  const OfflineTab({super.key, required this.repository, this.grade});

  final ContentRepository repository;
  final int? grade;

  @override
  State<OfflineTab> createState() => _OfflineTabState();
}

class _OfflineTabState extends State<OfflineTab> {
  late Future<OfflineData> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  @override
  void didUpdateWidget(covariant OfflineTab oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.grade != widget.grade) _future = _load();
  }

  Future<OfflineData> _load() => widget.repository.offline(grade: widget.grade);

  Future<void> _reload() async {
    setState(() => _future = _load());
    await _future;
  }

  Future<void> _download(String? url, String? fileName) async {
    if (url == null || url.isEmpty || fileName == null || fileName.isEmpty) {
      return;
    }
    try {
      await widget.repository.downloadOfflineFile(url: url, fileName: fileName);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Muat turun $fileName telah dimulakan.')),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Muat turun gagal: $error')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<OfflineData>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const ContentListSkeleton(count: 4);
        }
        if (snapshot.hasError) {
          return StateMessage(
            icon: Icons.wifi_off_outlined,
            title: 'Tidak dapat memuatkan simpanan offline',
            subtitle: '${snapshot.error}',
            onRetry: _reload,
          );
        }

        final data = snapshot.data!;
        return RefreshIndicator(
          onRefresh: _reload,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(20, 14, 20, 32),
            children: [
              const Text(
                'Simpanan Offline',
                style: TextStyle(
                  fontSize: 21,
                  fontWeight: FontWeight.w800,
                  color: LmsColors.ink,
                ),
              ),
              const SizedBox(height: 5),
              Text(
                'Muat turun video yang dimuat naik dan bahan sokongan untuk ${data.grade?.name ?? 'Tahun anda'}. Video YouTube hanya boleh ditonton dalam talian.',
                style: const TextStyle(
                  fontSize: 12.5,
                  color: LmsColors.inkMuted,
                ),
              ),
              const SizedBox(height: 24),
              const SectionTitle('Video Pelajaran'),
              const SizedBox(height: 10),
              if (data.lessons.isEmpty)
                const StateMessage(
                  icon: Icons.video_library_outlined,
                  title: 'Tiada video untuk Tahun ini',
                )
              else
                ...data.lessons.map(
                  (lesson) => _OfflineLessonTile(
                    lesson: lesson,
                    onDownload: () =>
                        _download(lesson.downloadUrl, lesson.fileName),
                  ),
                ),
              const SizedBox(height: 24),
              const SectionTitle('Bahan Sokongan'),
              const SizedBox(height: 10),
              if (data.materials.isEmpty)
                const StateMessage(
                  icon: Icons.description_outlined,
                  title: 'Tiada bahan sokongan',
                )
              else
                ...data.materials.map(
                  (material) => _OfflineMaterialTile(
                    material: material,
                    onDownload: () =>
                        _download(material.downloadUrl, material.fileName),
                  ),
                ),
            ],
          ),
        );
      },
    );
  }
}

class _OfflineLessonTile extends StatelessWidget {
  const _OfflineLessonTile({required this.lesson, required this.onDownload});

  final OfflineLesson lesson;
  final VoidCallback onDownload;

  @override
  Widget build(BuildContext context) => Container(
    margin: const EdgeInsets.only(bottom: 9),
    padding: const EdgeInsets.all(12),
    decoration: BoxDecoration(
      color: LmsColors.surface,
      borderRadius: BorderRadius.circular(15),
      border: Border.all(color: LmsColors.border),
    ),
    child: Row(
      children: [
        Container(
          width: 44,
          height: 44,
          decoration: BoxDecoration(
            color: lesson.downloadable
                ? const Color(0xFFE4EEF9)
                : LmsColors.surfaceMuted,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(
            lesson.downloadable
                ? Icons.download_for_offline_outlined
                : Icons.smart_display_outlined,
            color: lesson.downloadable
                ? const Color(0xFF2E6CA8)
                : LmsColors.inkMuted,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                lesson.title,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontSize: 13.5,
                  fontWeight: FontWeight.w800,
                  color: LmsColors.ink,
                ),
              ),
              const SizedBox(height: 3),
              Text(
                [
                  if (lesson.subjectName != null) lesson.subjectName!,
                  if (lesson.chapterLabel != null) lesson.chapterLabel!,
                ].join(' · '),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontSize: 11, color: LmsColors.inkMuted),
              ),
            ],
          ),
        ),
        const SizedBox(width: 9),
        lesson.downloadable
            ? IconButton(
                tooltip: 'Muat turun',
                onPressed: onDownload,
                icon: const Icon(
                  Icons.download_rounded,
                  color: LmsColors.brandStrong,
                ),
              )
            : const Tooltip(
                message: 'Video YouTube hanya boleh ditonton dalam talian.',
                child: Icon(Icons.wifi_rounded, color: LmsColors.inkFaint),
              ),
      ],
    ),
  );
}

class _OfflineMaterialTile extends StatelessWidget {
  const _OfflineMaterialTile({
    required this.material,
    required this.onDownload,
  });

  final OfflineMaterial material;
  final VoidCallback onDownload;

  @override
  Widget build(BuildContext context) {
    final icon = switch (material.extension.toLowerCase()) {
      'pdf' => Icons.picture_as_pdf_rounded,
      'ppt' || 'pptx' => Icons.slideshow_rounded,
      'doc' || 'docx' => Icons.description_rounded,
      'xls' || 'xlsx' => Icons.grid_on_rounded,
      'jpg' || 'jpeg' || 'png' => Icons.image_rounded,
      _ => Icons.insert_drive_file_outlined,
    };

    return Container(
      margin: const EdgeInsets.only(bottom: 9),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: LmsColors.surface,
        borderRadius: BorderRadius.circular(15),
        border: Border.all(color: LmsColors.border),
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: const Color(0xFFE5F3E0),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: LmsColors.brandStrong),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  material.title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 13.5,
                    fontWeight: FontWeight.w800,
                    color: LmsColors.ink,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  [
                    material.extension.toUpperCase(),
                    material.humanSize,
                    if (material.subjectName != null) material.subjectName!,
                  ].where((part) => part.isNotEmpty).join(' · '),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 11,
                    color: LmsColors.inkMuted,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 9),
          IconButton(
            tooltip: 'Muat turun',
            onPressed: onDownload,
            icon: const Icon(
              Icons.download_rounded,
              color: LmsColors.brandStrong,
            ),
          ),
        ],
      ),
    );
  }
}
