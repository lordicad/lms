import 'package:flutter/material.dart';

import '../../core/auth/auth_user.dart';
import '../../core/content/content_models.dart';
import '../../core/content/content_repository.dart';
import '../../core/theme/lms_theme.dart';
import 'subject_chapters_screen.dart';
import 'watch_screen.dart';
import 'widgets/content_widgets.dart';

/// The student home: greeting, points, continue watching, subjects, newest videos.
/// Mirrors the web belajar dashboard, trimmed to the essentials for this first slice.
class DashboardTab extends StatefulWidget {
  const DashboardTab({super.key, required this.repository, required this.user});

  final ContentRepository repository;
  final AuthUser user;

  @override
  State<DashboardTab> createState() => _DashboardTabState();
}

class _DashboardTabState extends State<DashboardTab> {
  late Future<DashboardData> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.repository.dashboard();
  }

  Future<void> _reload() async {
    setState(() => _future = widget.repository.dashboard());
    await _future.catchError((_) => throw Exception());
  }

  void _openLesson(LessonCard lesson) {
    Navigator.of(context).push(MaterialPageRoute(
      builder: (_) => WatchScreen(repository: widget.repository, lessonId: lesson.id),
    )).then((_) => _reload());
  }

  void _openSubject(SubjectCard subject) {
    Navigator.of(context).push(MaterialPageRoute(
      builder: (_) => SubjectChaptersScreen(
        repository: widget.repository,
        slug: subject.slug,
        title: subject.displayName,
      ),
    ));
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<DashboardData>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return StateMessage(
            icon: Icons.wifi_off_outlined,
            title: 'Tidak dapat memuatkan',
            subtitle: '${snapshot.error}',
            onRetry: _reload,
          );
        }

        final data = snapshot.data!;
        final gradeName = data.grade?.name ?? 'Tahun anda';

        return RefreshIndicator(
          onRefresh: _reload,
          child: ListView(
            padding: const EdgeInsets.only(bottom: 32),
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Hai, ${_firstName(widget.user.name)}!',
                        style: Theme.of(context).textTheme.headlineLarge),
                    const SizedBox(height: 4),
                    Text('Teruskan pembelajaran untuk $gradeName.'),
                    const SizedBox(height: 16),
                    _PointsCard(points: data.points, rank: data.rank),
                  ],
                ),
              ),
              if (data.continueWatching.isNotEmpty) ...[
                const SizedBox(height: 24),
                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 20),
                  child: SectionTitle('Sambung menonton'),
                ),
                const SizedBox(height: 12),
                LessonRail(lessons: data.continueWatching, onTapLesson: _openLesson),
              ],
              const SizedBox(height: 24),
              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 20),
                child: SectionTitle('Subjek'),
              ),
              const SizedBox(height: 12),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: _SubjectsGrid(subjects: data.subjects, onTap: _openSubject),
              ),
              if (data.newest.isNotEmpty) ...[
                const SizedBox(height: 24),
                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 20),
                  child: SectionTitle('Video terbaharu'),
                ),
                const SizedBox(height: 12),
                LessonRail(lessons: data.newest, onTapLesson: _openLesson),
              ],
            ],
          ),
        );
      },
    );
  }

  static String _firstName(String name) => name.trim().split(RegExp(r'\s+')).first;
}

class _PointsCard extends StatelessWidget {
  const _PointsCard({required this.points, required this.rank});
  final int points;
  final int? rank;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(color: LmsColors.brand, borderRadius: BorderRadius.circular(18)),
      padding: const EdgeInsets.all(18),
      child: Row(
        children: [
          const Icon(Icons.emoji_events_rounded, color: Colors.white, size: 30),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('$points mata',
                    style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800)),
                Text(
                  rank != null ? 'Kedudukan #$rank dalam Tahun anda' : 'Buat kuiz untuk kumpul mata',
                  style: const TextStyle(color: Colors.white70, fontWeight: FontWeight.w600),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _SubjectsGrid extends StatelessWidget {
  const _SubjectsGrid({required this.subjects, required this.onTap});
  final List<SubjectCard> subjects;
  final void Function(SubjectCard) onTap;

  @override
  Widget build(BuildContext context) {
    if (subjects.isEmpty) {
      return const StateMessage(icon: Icons.menu_book_outlined, title: 'Belum ada subjek');
    }
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: subjects.length,
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        mainAxisSpacing: 12,
        crossAxisSpacing: 12,
        childAspectRatio: 2.3,
      ),
      itemBuilder: (context, i) => SubjectTile(subject: subjects[i], onTap: () => onTap(subjects[i])),
    );
  }
}
