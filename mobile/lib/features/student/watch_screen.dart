import 'package:chewie/chewie.dart';
import 'package:flutter/material.dart';
import 'package:video_player/video_player.dart';
import 'package:youtube_player_flutter/youtube_player_flutter.dart';

import '../../core/content/content_models.dart';
import '../../core/content/content_repository.dart';
import '../../core/theme/lms_theme.dart';
import 'widgets/content_widgets.dart';

/// Watch a single lesson. Plays YouTube (via youtube_player_flutter) or an uploaded mp4
/// (via video_player + chewie), records the first-play view, and throttles progress saves
/// every ~10 seconds and on the way out. Mirrors the web tonton page.
class WatchScreen extends StatefulWidget {
  const WatchScreen({super.key, required this.repository, required this.lessonId});

  final ContentRepository repository;
  final int lessonId;

  @override
  State<WatchScreen> createState() => _WatchScreenState();
}

class _WatchScreenState extends State<WatchScreen> {
  LessonDetail? _lesson;
  Object? _error;

  YoutubePlayerController? _yt;
  VideoPlayerController? _video;
  ChewieController? _chewie;

  bool _viewMarked = false;
  int _lastSavedAt = 0; // seconds position last persisted
  DateTime _lastSaveTime = DateTime.fromMillisecondsSinceEpoch(0);

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final lesson = await widget.repository.lesson(widget.lessonId);
      if (!mounted) return;
      setState(() => _lesson = lesson);
      _setupPlayer(lesson);
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  void _setupPlayer(LessonDetail lesson) {
    final resumeAt = lesson.progress?.positionSeconds ?? 0;

    if (lesson.isYoutube && lesson.youtubeId != null) {
      _yt = YoutubePlayerController(
        initialVideoId: lesson.youtubeId!,
        flags: YoutubePlayerFlags(
          autoPlay: true,
          startAt: resumeAt,
          enableCaption: true,
        ),
      )..addListener(_onYoutubeTick);
    } else if (lesson.videoUrl != null) {
      final controller = VideoPlayerController.networkUrl(Uri.parse(lesson.videoUrl!));
      _video = controller;
      controller.initialize().then((_) {
        if (!mounted) return;
        if (resumeAt > 0) controller.seekTo(Duration(seconds: resumeAt));
        setState(() {
          _chewie = ChewieController(
            videoPlayerController: controller,
            autoPlay: true,
            aspectRatio: controller.value.aspectRatio == 0 ? 16 / 9 : controller.value.aspectRatio,
            materialProgressColors: ChewieProgressColors(playedColor: LmsColors.brand),
          );
        });
        controller.addListener(_onVideoTick);
        _markViewed();
      });
    }
  }

  void _onYoutubeTick() {
    final yt = _yt;
    if (yt == null || !yt.value.isReady) return;
    if (yt.value.isPlaying) _markViewed();
    _maybeSaveProgress(
      yt.value.position.inSeconds,
      yt.metadata.duration.inSeconds,
    );
  }

  void _onVideoTick() {
    final video = _video;
    if (video == null || !video.value.isInitialized) return;
    if (video.value.isPlaying) {
      _maybeSaveProgress(video.value.position.inSeconds, video.value.duration.inSeconds);
    }
  }

  Future<void> _markViewed() async {
    if (_viewMarked) return;
    _viewMarked = true;
    try {
      await widget.repository.markViewed(widget.lessonId);
    } catch (_) {
      // Non-critical; a missed view isn't worth interrupting playback.
    }
  }

  void _maybeSaveProgress(int position, int duration) {
    if (position <= 0) return;
    final now = DateTime.now();
    final movedEnough = (position - _lastSavedAt).abs() >= 10;
    final waitedEnough = now.difference(_lastSaveTime).inSeconds >= 10;
    if (!movedEnough || !waitedEnough) return;

    _lastSavedAt = position;
    _lastSaveTime = now;
    widget.repository.saveProgress(
      widget.lessonId,
      positionSeconds: position,
      durationSeconds: duration > 0 ? duration : null,
    ).catchError((_) {});
  }

  Future<void> _flushProgress() async {
    final position = _yt?.value.position.inSeconds ?? _video?.value.position.inSeconds ?? 0;
    final duration = _yt?.metadata.duration.inSeconds ?? _video?.value.duration.inSeconds ?? 0;
    if (position <= 0) return;
    try {
      await widget.repository.saveProgress(
        widget.lessonId,
        positionSeconds: position,
        durationSeconds: duration > 0 ? duration : null,
      );
    } catch (_) {}
  }

  void _openLesson(int lessonId) {
    _flushProgress();
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(
        builder: (_) => WatchScreen(repository: widget.repository, lessonId: lessonId),
      ),
    );
  }

  @override
  void dispose() {
    _flushProgress();
    _yt?.removeListener(_onYoutubeTick);
    _yt?.dispose();
    _video?.removeListener(_onVideoTick);
    _chewie?.dispose();
    _video?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_error != null) {
      return Scaffold(
        appBar: AppBar(),
        body: StateMessage(
          icon: Icons.error_outline,
          title: 'Tidak dapat memuatkan video',
          subtitle: '$_error',
          onRetry: () {
            setState(() => _error = null);
            _load();
          },
        ),
      );
    }

    final lesson = _lesson;
    if (lesson == null) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    if (lesson.isYoutube && _yt != null) {
      return YoutubePlayerBuilder(
        player: YoutubePlayer(controller: _yt!, progressIndicatorColor: LmsColors.brand),
        builder: (context, player) => _scaffold(lesson, player),
      );
    }

    final playerWidget = _chewie != null
        ? AspectRatio(aspectRatio: _chewie!.aspectRatio ?? 16 / 9, child: Chewie(controller: _chewie!))
        : Container(
            color: Colors.black,
            child: const AspectRatio(
              aspectRatio: 16 / 9,
              child: Center(child: CircularProgressIndicator()),
            ),
          );

    return _scaffold(lesson, playerWidget);
  }

  Widget _scaffold(LessonDetail lesson, Widget player) {
    return Scaffold(
      appBar: AppBar(title: Text(lesson.subject.displayName, overflow: TextOverflow.ellipsis)),
      body: ListView(
        children: [
          player,
          Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(lesson.title, style: Theme.of(context).textTheme.headlineMedium),
                const SizedBox(height: 6),
                Text(
                  [lesson.chapterLabel, if (lesson.teacherName != null) lesson.teacherName!].join(' · '),
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                if (lesson.description != null && lesson.description!.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  Text(lesson.description!, style: Theme.of(context).textTheme.bodyLarge),
                ],
                if (lesson.materials.isNotEmpty) ...[
                  const SizedBox(height: 24),
                  const SectionTitle('Bahan sokongan'),
                  const SizedBox(height: 8),
                  ...lesson.materials.map((m) => _MaterialRow(material: m)),
                ],
                const SizedBox(height: 24),
                Row(
                  children: [
                    if (lesson.previous != null)
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => _openLesson(lesson.previous!.id),
                          icon: const Icon(Icons.skip_previous),
                          label: const Text('Sebelum'),
                        ),
                      ),
                    if (lesson.previous != null && lesson.next != null) const SizedBox(width: 12),
                    if (lesson.next != null)
                      Expanded(
                        child: FilledButton.icon(
                          onPressed: () => _openLesson(lesson.next!.id),
                          icon: const Icon(Icons.skip_next),
                          label: const Text('Seterusnya'),
                        ),
                      ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _MaterialRow extends StatelessWidget {
  const _MaterialRow({required this.material});
  final MaterialItem material;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          const Icon(Icons.description_outlined, color: LmsColors.brand),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(material.title, style: Theme.of(context).textTheme.titleMedium),
                Text(
                  '${material.extension.toUpperCase()} · ${material.humanSize}',
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
