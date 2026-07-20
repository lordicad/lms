import 'package:flutter/material.dart';

import '../../core/content/content_models.dart';
import '../../core/content/content_repository.dart';
import 'watch_screen.dart';
import 'widgets/content_widgets.dart';

/// The student's saved (favourited) lessons. Backed by GET /student/favourites.
class SavedTab extends StatefulWidget {
  const SavedTab({super.key, required this.repository});

  final ContentRepository repository;

  @override
  State<SavedTab> createState() => _SavedTabState();
}

class _SavedTabState extends State<SavedTab> {
  late Future<List<LessonCard>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.repository.favourites();
  }

  Future<void> _reload() async {
    setState(() {
      _future = widget.repository.favourites();
    });
    await _future.catchError((_) => throw Exception());
  }

  void _open(LessonCard lesson) {
    Navigator.of(context)
        .push(
          MaterialPageRoute(
            builder: (_) =>
                WatchScreen(repository: widget.repository, lessonId: lesson.id),
          ),
        )
        .then((_) => _reload());
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<LessonCard>>(
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

        final lessons = snapshot.data!;
        if (lessons.isEmpty) {
          return const StateMessage(
            icon: Icons.bookmark_border,
            title: 'Belum ada kegemaran',
            subtitle:
                'Tandakan video dengan ikon hati untuk menyimpannya di sini.',
          );
        }

        return RefreshIndicator(
          onRefresh: _reload,
          child: ListView.separated(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
            itemCount: lessons.length,
            separatorBuilder: (_, _) => const Divider(height: 1),
            itemBuilder: (context, i) =>
                LessonRow(lesson: lessons[i], onTap: () => _open(lessons[i])),
          ),
        );
      },
    );
  }
}
