import 'package:flutter/material.dart';

import '../../core/content/content_models.dart';
import '../../core/content/content_repository.dart';
import 'subject_chapters_screen.dart';
import 'widgets/content_widgets.dart';

/// All subjects offered in the student's Tahun, grouped by Kurikulum 2027 category.
class SubjectsTab extends StatefulWidget {
  const SubjectsTab({super.key, required this.repository});

  final ContentRepository repository;

  @override
  State<SubjectsTab> createState() => _SubjectsTabState();
}

class _SubjectsTabState extends State<SubjectsTab> {
  late Future<SubjectsData> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.repository.subjects();
  }

  Future<void> _reload() async {
    setState(() => _future = widget.repository.subjects());
    await _future.catchError((_) => throw Exception());
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
    return FutureBuilder<SubjectsData>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return StateMessage(
            icon: Icons.wifi_off_outlined,
            title: 'Tidak dapat memuatkan subjek',
            subtitle: '${snapshot.error}',
            onRetry: _reload,
          );
        }

        final data = snapshot.data!;
        final groups = data.categories.where((g) => g.subjects.isNotEmpty).toList();
        if (groups.isEmpty) {
          return const StateMessage(
            icon: Icons.menu_book_outlined,
            title: 'Belum ada subjek',
            subtitle: 'Tiada subjek ditawarkan untuk Tahun anda.',
          );
        }

        return RefreshIndicator(
          onRefresh: _reload,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
            children: [
              for (final group in groups) ...[
                Padding(
                  padding: const EdgeInsets.only(top: 8, bottom: 10),
                  child: Text(group.label, style: Theme.of(context).textTheme.titleLarge),
                ),
                for (final subject in group.subjects)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: SubjectTile(subject: subject, onTap: () => _openSubject(subject)),
                  ),
              ],
            ],
          ),
        );
      },
    );
  }
}
