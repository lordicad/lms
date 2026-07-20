import 'package:flutter/material.dart';

import '../../core/content/content_models.dart';
import '../../core/content/content_repository.dart';
import '../../core/theme/lms_theme.dart';
import 'quiz_intro_screen.dart';
import 'watch_screen.dart';
import 'widgets/content_widgets.dart';

/// Search the content a student can access in their own Tahun.
class SearchScreen extends StatefulWidget {
  const SearchScreen({super.key, required this.repository});

  final ContentRepository repository;

  @override
  State<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends State<SearchScreen> {
  final _queryCtrl = TextEditingController();
  Future<List<SearchResult>>? _future;
  String _query = '';

  @override
  void dispose() {
    _queryCtrl.dispose();
    super.dispose();
  }

  void _search([String? value]) {
    final query = (value ?? _queryCtrl.text).trim();
    if (query.length < 2) {
      setState(() {
        _query = query;
        _future = null;
      });
      return;
    }
    FocusScope.of(context).unfocus();
    setState(() {
      _query = query;
      _future = widget.repository.search(query);
    });
  }

  void _open(SearchResult result) {
    if (result.isLesson) {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) =>
              WatchScreen(repository: widget.repository, lessonId: result.id),
        ),
      );
      return;
    }
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => QuizIntroScreen(
          repository: widget.repository,
          quizId: result.id,
          title: result.title,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Cari kandungan')),
      body: Padding(
        padding: const EdgeInsets.fromLTRB(20, 10, 20, 24),
        child: Column(
          children: [
            TextField(
              controller: _queryCtrl,
              autofocus: true,
              textInputAction: TextInputAction.search,
              onSubmitted: _search,
              onChanged: (value) {
                if (value.trim().isEmpty && _future != null) _search(value);
              },
              decoration: InputDecoration(
                hintText: 'Cari video atau kuiz',
                prefixIcon: const Icon(Icons.search_rounded),
                suffixIcon: _queryCtrl.text.isEmpty
                    ? null
                    : IconButton(
                        tooltip: 'Kosongkan',
                        icon: const Icon(Icons.close),
                        onPressed: () {
                          _queryCtrl.clear();
                          _search('');
                        },
                      ),
              ),
            ),
            const SizedBox(height: 10),
            const Align(
              alignment: Alignment.centerLeft,
              child: Text(
                'Cari dalam kandungan untuk Tahun anda.',
                style: TextStyle(fontSize: 12, color: LmsColors.inkMuted),
              ),
            ),
            const SizedBox(height: 12),
            Expanded(child: _body()),
          ],
        ),
      ),
    );
  }

  Widget _body() {
    if (_future == null) {
      return const StateMessage(
        icon: Icons.manage_search_outlined,
        title: 'Apa yang anda mahu belajar?',
        subtitle: 'Masukkan sekurang-kurangnya 2 huruf untuk mencari.',
      );
    }
    return FutureBuilder<List<SearchResult>>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return StateMessage(
            icon: Icons.wifi_off_outlined,
            title: 'Carian tidak dapat dimuatkan',
            subtitle: '${snapshot.error}',
            onRetry: () => _search(_query),
          );
        }
        final results = snapshot.data!;
        if (results.isEmpty) {
          return StateMessage(
            icon: Icons.search_off_outlined,
            title: 'Tiada hasil untuk “$_query”',
            subtitle: 'Cuba perkataan lain atau semak ejaan.',
          );
        }
        return ListView.separated(
          padding: const EdgeInsets.only(top: 2),
          itemCount: results.length,
          separatorBuilder: (_, _) => const SizedBox(height: 10),
          itemBuilder: (_, index) => _ResultCard(
            result: results[index],
            onTap: () => _open(results[index]),
          ),
        );
      },
    );
  }
}

class _ResultCard extends StatelessWidget {
  const _ResultCard({required this.result, required this.onTap});

  final SearchResult result;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final isLesson = result.isLesson;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: LmsColors.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: LmsColors.border),
        ),
        child: Row(
          children: [
            Container(
              width: 48,
              height: 48,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: isLesson ? LmsColors.brandSoft : const Color(0xFFFFF5DB),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(
                isLesson
                    ? Icons.play_circle_outline_rounded
                    : (result.isFileQuiz
                          ? Icons.description_outlined
                          : Icons.quiz_outlined),
                color: isLesson ? LmsColors.brand : LmsColors.warning,
              ),
            ),
            const SizedBox(width: 13),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    result.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  const SizedBox(height: 3),
                  Text(
                    [
                      isLesson ? 'Video' : 'Kuiz',
                      if (result.subjectName != null) result.subjectName!,
                      if (result.chapterLabel != null) result.chapterLabel!,
                      if (!isLesson && result.questionCount != null)
                        '${result.questionCount} soalan',
                    ].join(' · '),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 12,
                      color: LmsColors.inkMuted,
                    ),
                  ),
                ],
              ),
            ),
            if (result.percent != null)
              Text(
                '${result.percent}%',
                style: TextStyle(
                  fontWeight: FontWeight.w800,
                  color: result.completed
                      ? LmsColors.success
                      : LmsColors.brandStrong,
                ),
              )
            else
              const Icon(
                Icons.chevron_right_rounded,
                color: LmsColors.inkFaint,
              ),
          ],
        ),
      ),
    );
  }
}
