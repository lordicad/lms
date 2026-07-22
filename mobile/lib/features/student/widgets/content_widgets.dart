import 'package:flutter/material.dart';

import '../../../core/content/content_models.dart';
import '../../../core/theme/lms_theme.dart';

/// Small shared building blocks for the student learning screens.

class SectionTitle extends StatelessWidget {
  const SectionTitle(this.text, {super.key, this.trailing});

  final String text;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Text(text, style: Theme.of(context).textTheme.titleLarge),
        ),
        ?trailing,
      ],
    );
  }
}

/// A thumbnail with a play glyph, rounded corners, and an optional progress bar.
class LessonThumbnail extends StatelessWidget {
  const LessonThumbnail({
    super.key,
    required this.url,
    this.percent = 0,
    this.completed = false,
  });

  final String? url;
  final int percent;
  final bool completed;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(12),
      child: AspectRatio(
        aspectRatio: 16 / 9,
        child: Stack(
          fit: StackFit.expand,
          children: [
            if (url != null && url!.isNotEmpty)
              Image.network(
                url!,
                fit: BoxFit.cover,
                errorBuilder: (_, _, _) => const _ThumbFallback(),
                loadingBuilder: (context, child, progress) => progress == null
                    ? child
                    : const _ThumbFallback(loading: true),
              )
            else
              const _ThumbFallback(),
            const DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.center,
                  end: Alignment.bottomCenter,
                  colors: [Colors.transparent, Color(0x55000000)],
                ),
              ),
            ),
            const Center(
              child: CircleAvatar(
                radius: 20,
                backgroundColor: Color(0xCCFFFFFF),
                child: Icon(
                  Icons.play_arrow_rounded,
                  color: LmsColors.brand,
                  size: 26,
                ),
              ),
            ),
            if (completed)
              const Positioned(
                top: 8,
                right: 8,
                child: CircleAvatar(
                  radius: 12,
                  backgroundColor: LmsColors.success,
                  child: Icon(Icons.check, color: Colors.white, size: 15),
                ),
              ),
            if (percent > 0 && !completed)
              Positioned(
                left: 0,
                right: 0,
                bottom: 0,
                child: LinearProgressIndicator(
                  value: percent / 100,
                  minHeight: 4,
                  backgroundColor: const Color(0x33FFFFFF),
                  valueColor: const AlwaysStoppedAnimation(LmsColors.brand),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _ThumbFallback extends StatelessWidget {
  const _ThumbFallback({this.loading = false});
  final bool loading;

  @override
  Widget build(BuildContext context) {
    return ColoredBox(
      color: LmsColors.surfaceMuted,
      child: Center(
        child: loading
            ? const SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(strokeWidth: 2),
              )
            : const Icon(
                Icons.ondemand_video_outlined,
                color: LmsColors.inkMuted,
              ),
      ),
    );
  }
}

/// A fixed-width card used inside horizontal rails (continue watching, newest).
class LessonRailCard extends StatelessWidget {
  const LessonRailCard({super.key, required this.lesson, required this.onTap});

  final LessonCard lesson;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 220,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            LessonThumbnail(
              url: lesson.thumbnailUrl,
              percent: lesson.percent,
              completed: lesson.completed,
            ),
            const SizedBox(height: 8),
            Text(
              lesson.title,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 2),
            Text(
              [
                if (lesson.subjectName != null) lesson.subjectName!,
                if (lesson.durationLabel != null) lesson.durationLabel!,
              ].join(' · '),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: Theme.of(context).textTheme.bodyMedium,
            ),
          ],
        ),
      ),
    );
  }
}

/// A horizontal scrolling rail of lesson cards.
class LessonRail extends StatelessWidget {
  const LessonRail({
    super.key,
    required this.lessons,
    required this.onTapLesson,
  });

  final List<LessonCard> lessons;
  final void Function(LessonCard) onTapLesson;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 210,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 20),
        itemCount: lessons.length,
        separatorBuilder: (_, _) => const SizedBox(width: 14),
        itemBuilder: (context, i) => LessonRailCard(
          lesson: lessons[i],
          onTap: () => onTapLesson(lessons[i]),
        ),
      ),
    );
  }
}

/// A full-width row for a lesson inside a chapter list.
class LessonRow extends StatelessWidget {
  const LessonRow({super.key, required this.lesson, required this.onTap});

  final LessonCard lesson;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Row(
          children: [
            SizedBox(
              width: 130,
              child: LessonThumbnail(
                url: lesson.thumbnailUrl,
                percent: lesson.percent,
                completed: lesson.completed,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    lesson.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Icon(
                        lesson.watched
                            ? Icons.check_circle
                            : Icons.play_circle_outline,
                        size: 15,
                        color: lesson.watched
                            ? LmsColors.success
                            : LmsColors.inkMuted,
                      ),
                      const SizedBox(width: 4),
                      Text(
                        lesson.watched
                            ? 'Dah tonton'
                            : (lesson.durationLabel ?? 'Video'),
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Picks a Material icon for a subject from its name, so each subject reads at a
/// glance (matching the WeLearn concept). Falls back to a book for anything else.
IconData subjectIcon(SubjectCard subject) {
  final name = subject.name.toLowerCase();
  bool has(String s) => name.contains(s);

  if (has('matematik') || has('math')) return Icons.calculate_rounded;
  if (has('sains') || has('science')) return Icons.science_rounded;
  if (has('english') || has('inggeris')) return Icons.translate_rounded;
  if (has('islam') || has('agama')) return Icons.mosque_rounded;
  if (has('sejarah')) return Icons.history_edu_rounded;
  if (has('seni') || has('lukis')) return Icons.palette_rounded;
  if (has('jasmani') || has('kesihatan') || has('pj')) {
    return Icons.fitness_center_rounded;
  }
  if (has('reka bentuk') || has('teknologi') || has('rbt')) {
    return Icons.build_rounded;
  }
  if (has('muzik')) return Icons.music_note_rounded;
  if (has('moral')) return Icons.volunteer_activism_rounded;
  return Icons.menu_book_rounded; // Bahasa Melayu and default
}

class SubjectTile extends StatelessWidget {
  const SubjectTile({super.key, required this.subject, required this.onTap});

  final SubjectCard subject;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final color = subject.color;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        decoration: BoxDecoration(
          color: LmsColors.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: LmsColors.border),
        ),
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(11),
              ),
              padding: const EdgeInsets.all(10),
              child: Icon(subjectIcon(subject), color: color),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    subject.displayName,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${subject.lessonsCount} video',
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, color: LmsColors.inkMuted),
          ],
        ),
      ),
    );
  }
}

/// Loading / error / empty state placeholders shared across screens.
class StateMessage extends StatelessWidget {
  const StateMessage({
    super.key,
    required this.icon,
    required this.title,
    this.subtitle,
    this.onRetry,
  });

  final IconData icon;
  final String title;
  final String? subtitle;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 44, color: LmsColors.inkMuted),
            const SizedBox(height: 14),
            Text(
              title,
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.titleMedium,
            ),
            if (subtitle != null) ...[
              const SizedBox(height: 6),
              Text(subtitle!, textAlign: TextAlign.center),
            ],
            if (onRetry != null) ...[
              const SizedBox(height: 16),
              OutlinedButton.icon(
                onPressed: onRetry,
                icon: const Icon(Icons.refresh),
                label: const Text('Cuba lagi'),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
