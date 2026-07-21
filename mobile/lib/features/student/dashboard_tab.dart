import 'package:flutter/material.dart';

import '../../core/content/content_models.dart';
import '../../core/content/content_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../../core/widgets/loading_skeleton.dart';
import 'subject_chapters_screen.dart';
import 'watch_screen.dart';
import 'widgets/content_widgets.dart';

/// Mobile version of the live WeLearn student home: resume/trending hero followed
/// by the same content rails (continue, popular, new and personalised suggestions).
class DashboardTab extends StatefulWidget {
  const DashboardTab({
    super.key,
    required this.repository,
    this.grade,
    this.onSeeAllSubjects,
  });

  final ContentRepository repository;
  final int? grade;
  final VoidCallback? onSeeAllSubjects;

  @override
  State<DashboardTab> createState() => _DashboardTabState();
}

class _DashboardTabState extends State<DashboardTab> {
  late Future<DashboardData> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  @override
  void didUpdateWidget(covariant DashboardTab oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.grade != widget.grade) {
      _future = _load();
    }
  }

  Future<DashboardData> _load() =>
      widget.repository.dashboard(grade: widget.grade);

  Future<void> _reload() async {
    setState(() => _future = _load());
    await _future;
  }

  void _openLesson(LessonCard lesson) {
    Navigator.of(context)
        .push(
          MaterialPageRoute(
            builder: (_) =>
                WatchScreen(repository: widget.repository, lessonId: lesson.id),
          ),
        )
        .then((_) => _reload());
  }

  Future<void> _toggleFavourite(LessonCard lesson) async {
    try {
      await widget.repository.toggleFavourite(lesson.id);
      if (mounted) _reload();
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Tidak dapat mengemas kini kegemaran: $error')),
      );
    }
  }

  void _openSubject(SubjectCard subject, int? grade) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => SubjectChaptersScreen(
          repository: widget.repository,
          slug: subject.slug,
          title: subject.displayName,
          grade: grade,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<DashboardData>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const StudentDashboardSkeleton();
        }
        if (snapshot.hasError) {
          return StateMessage(
            icon: Icons.wifi_off_outlined,
            title: 'Tidak dapat memuatkan pembelajaran',
            subtitle: '${snapshot.error}',
            onRetry: _reload,
          );
        }

        final data = snapshot.data!;
        if (data.grade == null) {
          return const StateMessage(
            icon: Icons.school_outlined,
            title: 'Tahun anda belum ditetapkan',
            subtitle:
                'Sila kemas kini profil untuk melihat kandungan yang betul.',
          );
        }

        final noContent =
            data.hero == null &&
            data.continueWatching.isEmpty &&
            data.trending.isEmpty &&
            data.newest.isEmpty &&
            data.suggested.isEmpty;

        return RefreshIndicator(
          onRefresh: _reload,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.only(bottom: 32),
            children: [
              if (data.hero != null) ...[
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 12, 20, 0),
                  child: _LearningHero(
                    lesson: data.hero!,
                    resuming: data.heroResuming,
                    onWatch: () => _openLesson(data.hero!),
                    onFavourite: () => _toggleFavourite(data.hero!),
                  ),
                ),
                const SizedBox(height: 24),
              ],
              if (data.continueWatching.isNotEmpty) ...[
                _RailHeading(
                  title: 'Sambung menonton',
                  trailing: data.subjects.isEmpty
                      ? null
                      : GestureDetector(
                          onTap: widget.onSeeAllSubjects,
                          child: const Text(
                            'Lihat subjek',
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              color: LmsColors.brandStrong,
                            ),
                          ),
                        ),
                ),
                const SizedBox(height: 12),
                LessonRail(
                  lessons: data.continueWatching,
                  onTapLesson: _openLesson,
                ),
                const SizedBox(height: 24),
              ],
              if (data.trending.isNotEmpty) ...[
                _RailHeading(
                  title: data.trendingFallback
                      ? 'Baru ditambah'
                      : 'Paling popular',
                ),
                const SizedBox(height: 12),
                LessonRail(lessons: data.trending, onTapLesson: _openLesson),
                const SizedBox(height: 24),
              ],
              if (data.newest.isNotEmpty && !data.trendingFallback) ...[
                const _RailHeading(title: 'Baru ditambah'),
                const SizedBox(height: 12),
                LessonRail(lessons: data.newest, onTapLesson: _openLesson),
                const SizedBox(height: 24),
              ],
              if (data.suggested.isNotEmpty) ...[
                const _RailHeading(title: 'Anda mungkin suka'),
                const SizedBox(height: 12),
                LessonRail(lessons: data.suggested, onTapLesson: _openLesson),
                const SizedBox(height: 24),
              ],
              if (data.subjects.isNotEmpty) ...[
                Padding(
                  // Keeps the section baseline aligned with the WeLearn logo.
                  padding: const EdgeInsets.fromLTRB(20, 0, 20, 0),
                  child: SectionTitle(
                    'Subjek ${data.grade!.name}',
                    trailing: GestureDetector(
                      onTap: widget.onSeeAllSubjects,
                      child: const Text(
                        'Lihat semua',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                          color: LmsColors.brandStrong,
                        ),
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20),
                  child: _SubjectsGrid(
                    subjects: data.subjects.take(4).toList(),
                    onTap: (subject) =>
                        _openSubject(subject, data.grade!.level),
                  ),
                ),
              ],
              if (noContent)
                const Padding(
                  padding: EdgeInsets.fromLTRB(20, 72, 20, 0),
                  child: StateMessage(
                    icon: Icons.ondemand_video_outlined,
                    title: 'Belum ada video',
                    subtitle: 'Sila semak semula kemudian.',
                  ),
                ),
            ],
          ),
        );
      },
    );
  }
}

class _RailHeading extends StatelessWidget {
  const _RailHeading({required this.title, this.trailing});

  final String title;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(horizontal: 20),
    child: SectionTitle(title, trailing: trailing),
  );
}

class _LearningHero extends StatelessWidget {
  const _LearningHero({
    required this.lesson,
    required this.resuming,
    required this.onWatch,
    required this.onFavourite,
  });

  final LessonCard lesson;
  final bool resuming;
  final VoidCallback onWatch;
  final VoidCallback onFavourite;

  @override
  Widget build(BuildContext context) {
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        color: const Color(0xFFE3F0FA),
        borderRadius: BorderRadius.circular(22),
        boxShadow: const [
          BoxShadow(
            color: Color(0x294276AE),
            blurRadius: 22,
            offset: Offset(0, 8),
          ),
        ],
      ),
      child: LayoutBuilder(
        builder: (context, constraints) {
          final content = _HeroContent(
            lesson: lesson,
            resuming: resuming,
            onWatch: onWatch,
            onFavourite: onFavourite,
          );
          final visual = _HeroVisual(lesson: lesson, onTap: onWatch);

          if (constraints.maxWidth >= 560) {
            return Row(
              children: [
                Expanded(child: content),
                SizedBox(width: constraints.maxWidth * .38, child: visual),
              ],
            );
          }
          return Column(children: [visual, content]);
        },
      ),
    );
  }
}

class _HeroContent extends StatelessWidget {
  const _HeroContent({
    required this.lesson,
    required this.resuming,
    required this.onWatch,
    required this.onFavourite,
  });

  final LessonCard lesson;
  final bool resuming;
  final VoidCallback onWatch;
  final VoidCallback onFavourite;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.all(20),
    child: Column(
      mainAxisAlignment: MainAxisAlignment.center,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Wrap(
          spacing: 7,
          runSpacing: 7,
          children: [
            if ((lesson.subjectName ?? '').isNotEmpty)
              _HeroChip(
                text: lesson.subjectName!,
                color: const Color(0xFF2E6CA8),
              ),
            if ((lesson.chapterLabel ?? '').isNotEmpty)
              _HeroChip(
                text: lesson.chapterLabel!,
                color: const Color(0xFF4A5A6B),
              ),
            if (!resuming)
              const _HeroChip(
                text: 'TRENDING',
                color: LmsColors.brandStrong,
                dark: true,
              ),
          ],
        ),
        const SizedBox(height: 13),
        Text(
          lesson.title,
          maxLines: 3,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(
            fontSize: 22,
            height: 1.18,
            fontWeight: FontWeight.w800,
            color: Color(0xFF1A2433),
          ),
        ),
        const SizedBox(height: 16),
        Wrap(
          spacing: 9,
          runSpacing: 8,
          children: [
            FilledButton.icon(
              onPressed: onWatch,
              icon: const Icon(Icons.play_arrow_rounded, size: 18),
              label: Text(resuming ? 'Sambung menonton' : 'Tonton'),
              style: FilledButton.styleFrom(
                backgroundColor: LmsColors.brand,
                foregroundColor: Colors.white,
                minimumSize: const Size(0, 44),
              ),
            ),
            OutlinedButton.icon(
              onPressed: onFavourite,
              icon: Icon(
                lesson.favourited
                    ? Icons.favorite_rounded
                    : Icons.favorite_border_rounded,
                size: 18,
                color: lesson.favourited ? LmsColors.danger : null,
              ),
              label: const Text('Kegemaran'),
              style: OutlinedButton.styleFrom(
                foregroundColor: const Color(0xFF293743),
                minimumSize: const Size(0, 44),
                side: const BorderSide(color: Color(0x332E4454)),
              ),
            ),
          ],
        ),
      ],
    ),
  );
}

class _HeroChip extends StatelessWidget {
  const _HeroChip({required this.text, required this.color, this.dark = false});

  final String text;
  final Color color;
  final bool dark;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
    decoration: BoxDecoration(
      color: dark ? color : Colors.white,
      borderRadius: BorderRadius.circular(999),
    ),
    child: Text(
      text,
      maxLines: 1,
      overflow: TextOverflow.ellipsis,
      style: TextStyle(
        color: dark ? Colors.white : color,
        fontSize: 10.5,
        fontWeight: FontWeight.w800,
        letterSpacing: dark ? .5 : 0,
      ),
    ),
  );
}

class _HeroVisual extends StatelessWidget {
  const _HeroVisual({required this.lesson, required this.onTap});

  final LessonCard lesson;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => InkWell(
    onTap: onTap,
    child: AspectRatio(
      aspectRatio: 1.35,
      child: Stack(
        fit: StackFit.expand,
        children: [
          if (lesson.thumbnailUrl != null && lesson.thumbnailUrl!.isNotEmpty)
            Image.network(
              lesson.thumbnailUrl!,
              fit: BoxFit.cover,
              errorBuilder: (_, _, _) => const _HeroFallback(),
            )
          else
            const _HeroFallback(),
          const ColoredBox(color: Color(0x172E6CA8)),
          const Center(
            child: CircleAvatar(
              radius: 25,
              backgroundColor: Color(0xEFFFFFFF),
              foregroundColor: Color(0xFF2E6CA8),
              child: Icon(Icons.play_arrow_rounded, size: 29),
            ),
          ),
          if ((lesson.durationLabel ?? '').isNotEmpty)
            Positioned(
              right: 10,
              bottom: 10,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xD92E6CA8),
                  borderRadius: BorderRadius.circular(99),
                ),
                child: Text(
                  lesson.durationLabel!,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ),
        ],
      ),
    ),
  );
}

class _HeroFallback extends StatelessWidget {
  const _HeroFallback();

  @override
  Widget build(BuildContext context) =>
      const ColoredBox(color: Color(0xFFA5C9EA));
}

class _SubjectsGrid extends StatelessWidget {
  const _SubjectsGrid({required this.subjects, required this.onTap});

  final List<SubjectCard> subjects;
  final ValueChanged<SubjectCard> onTap;

  @override
  Widget build(BuildContext context) => LayoutBuilder(
    builder: (context, constraints) => GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: subjects.length,
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        mainAxisSpacing: 12,
        crossAxisSpacing: 12,
        // Two-line subject names need more room than a wide tablet card.
        // Fixed extents avoid RenderFlex overflows on every phone width.
        mainAxisExtent: constraints.maxWidth < 600 ? 110 : 120,
      ),
      itemBuilder: (context, index) => SubjectTile(
        subject: subjects[index],
        onTap: () => onTap(subjects[index]),
      ),
    ),
  );
}
