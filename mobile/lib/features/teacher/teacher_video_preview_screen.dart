import 'package:chewie/chewie.dart';
import 'package:flutter/material.dart';
import 'package:video_player/video_player.dart';
import 'package:youtube_player_flutter/youtube_player_flutter.dart';

import '../../core/teacher/teacher_models.dart';
import '../../core/theme/lms_theme.dart';
import '../student/widgets/content_widgets.dart';

/// Teacher-only video preview. It does not mark a student view or save progress;
/// it simply lets teachers check the YouTube/reference/uploaded video they added.
class TeacherVideoPreviewScreen extends StatefulWidget {
  const TeacherVideoPreviewScreen({super.key, required this.video});

  final TeacherVideo video;

  @override
  State<TeacherVideoPreviewScreen> createState() =>
      _TeacherVideoPreviewScreenState();
}

class _TeacherVideoPreviewScreenState extends State<TeacherVideoPreviewScreen> {
  YoutubePlayerController? _youtube;
  VideoPlayerController? _video;
  ChewieController? _chewie;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _setup();
  }

  void _setup() {
    final youtubeId =
        widget.video.youtubeId ??
        (widget.video.youtubeUrl == null
            ? null
            : YoutubePlayer.convertUrlToId(widget.video.youtubeUrl!));

    if (youtubeId != null && youtubeId.isNotEmpty) {
      _youtube = YoutubePlayerController(
        initialVideoId: youtubeId,
        flags: const YoutubePlayerFlags(autoPlay: true, enableCaption: true),
      );
      return;
    }

    final url = widget.video.videoUrl;
    if (url == null || url.isEmpty) {
      _error = 'Video ini belum mempunyai pautan fail untuk ditonton.';
      return;
    }

    final controller = VideoPlayerController.networkUrl(Uri.parse(url));
    _video = controller;
    controller
        .initialize()
        .then((_) {
          if (!mounted) return;
          setState(() {
            _chewie = ChewieController(
              videoPlayerController: controller,
              autoPlay: true,
              aspectRatio: controller.value.aspectRatio == 0
                  ? 16 / 9
                  : controller.value.aspectRatio,
              materialProgressColors: ChewieProgressColors(
                playedColor: LmsColors.brand,
              ),
            );
          });
        })
        .catchError((error) {
          if (mounted) setState(() => _error = error);
        });
  }

  @override
  void dispose() {
    _youtube?.dispose();
    _chewie?.dispose();
    _video?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_youtube != null) {
      return YoutubePlayerBuilder(
        player: YoutubePlayer(
          controller: _youtube!,
          progressIndicatorColor: LmsColors.brand,
        ),
        builder: (context, player) => _scaffold(player),
      );
    }

    final player = _error != null
        ? AspectRatio(
            aspectRatio: 16 / 9,
            child: StateMessage(
              icon: Icons.error_outline,
              title: 'Video tidak dapat dibuka',
              subtitle: '$_error',
            ),
          )
        : _chewie != null
        ? AspectRatio(
            aspectRatio: _chewie!.aspectRatio ?? 16 / 9,
            child: Chewie(controller: _chewie!),
          )
        : Container(
            color: Colors.black,
            child: const AspectRatio(
              aspectRatio: 16 / 9,
              child: Center(child: CircularProgressIndicator()),
            ),
          );

    return _scaffold(player);
  }

  Widget _scaffold(Widget player) {
    final video = widget.video;
    return Scaffold(
      appBar: AppBar(title: Text(video.title, overflow: TextOverflow.ellipsis)),
      body: ListView(
        children: [
          player,
          Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  video.title,
                  style: Theme.of(context).textTheme.headlineMedium,
                ),
                const SizedBox(height: 8),
                Text(
                  [
                    if (video.subjectName != null) video.subjectName!,
                    if (video.gradeName != null) video.gradeName!,
                    if (video.chapterNumber != null)
                      'Bab ${video.chapterNumber}'
                    else if (video.chapterLabel != null)
                      video.chapterLabel!,
                    video.ownershipLabel,
                    video.published ? 'Diterbitkan' : 'Draf',
                  ].join(' · '),
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                if (video.description != null &&
                    video.description!.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  Text(video.description!),
                ],
                const SizedBox(height: 16),
                const _PreviewNote(),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _PreviewNote extends StatelessWidget {
  const _PreviewNote();

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(14),
    decoration: BoxDecoration(
      color: LmsPalette.soft(context),
      borderRadius: BorderRadius.circular(14),
      border: Border.all(color: LmsPalette.border(context)),
    ),
    child: const Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(Icons.visibility_outlined, color: LmsColors.brand, size: 20),
        SizedBox(width: 10),
        Expanded(
          child: Text(
            'Paparan cikgu sahaja. Tontonan ini tidak dikira sebagai tontonan murid.',
            style: TextStyle(fontWeight: FontWeight.w700),
          ),
        ),
      ],
    ),
  );
}
