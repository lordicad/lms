import 'package:flutter/material.dart';

import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../student/widgets/content_widgets.dart';

class TeacherNotificationsScreen extends StatefulWidget {
  const TeacherNotificationsScreen({super.key, required this.repository});

  final TeacherRepository repository;

  @override
  State<TeacherNotificationsScreen> createState() =>
      _TeacherNotificationsScreenState();
}

class _TeacherNotificationsScreenState
    extends State<TeacherNotificationsScreen> {
  late Future<TeacherNotificationsData> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<TeacherNotificationsData> _load() async {
    final data = await widget.repository.notifications();
    if (data.unreadCount > 0) {
      // Match the web: show the newly arrived cards once, then clear the bell state.
      widget.repository.markNotificationsRead().ignore();
    }
    return data;
  }

  void _reload() => setState(() => _future = _load());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Notifikasi')),
      body: FutureBuilder<TeacherNotificationsData>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return StateMessage(
              icon: Icons.wifi_off_outlined,
              title: 'Tidak dapat memuatkan notifikasi',
              subtitle: '${snapshot.error}',
              onRetry: _reload,
            );
          }
          final data = snapshot.data!;
          if (data.notifications.isEmpty) {
            return const StateMessage(
              icon: Icons.notifications_none_outlined,
              title: 'Tiada notifikasi lagi',
              subtitle:
                  'Aktiviti murid pada kandungan anda akan muncul di sini.',
            );
          }
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView.separated(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
              itemCount: data.notifications.length + 1,
              separatorBuilder: (_, _) => const SizedBox(height: 10),
              itemBuilder: (context, index) {
                if (index == 0) {
                  return Text(
                    data.unreadCount > 0
                        ? '${data.unreadCount} aktiviti baharu'
                        : 'Aktiviti kandungan anda',
                    style: Theme.of(context).textTheme.titleMedium,
                  );
                }
                return _NotificationCard(data.notifications[index - 1]);
              },
            ),
          );
        },
      ),
    );
  }
}

class _NotificationCard extends StatelessWidget {
  const _NotificationCard(this.item);
  final TeacherNotificationItem item;

  @override
  Widget build(BuildContext context) {
    final style = switch (item.type) {
      'quiz_attempt' => (Icons.quiz_outlined, const Color(0xFFFFF0D9)),
      'favourite' => (Icons.favorite_border_rounded, const Color(0xFFFBE4ED)),
      'download' => (Icons.file_download_outlined, const Color(0xFFE7EFFD)),
      _ => (Icons.notifications_outlined, LmsColors.brandSoft),
    };
    final actor = item.actorName?.trim().isNotEmpty == true
        ? item.actorName!
        : 'Seorang murid';
    final message = switch (item.type) {
      'quiz_attempt' => '$actor menjawab kuiz “${item.title}”',
      'favourite' => '$actor menggemari video “${item.title}”',
      'download' => '$actor memuat turun bahan “${item.title}”',
      _ => item.title,
    };

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: item.read ? LmsColors.surface : const Color(0xFFF3FAF0),
        borderRadius: BorderRadius.circular(15),
        border: Border.all(
          color: item.read ? LmsColors.border : LmsColors.brand,
        ),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 43,
            height: 43,
            decoration: BoxDecoration(
              color: style.$2,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(style.$1, color: LmsColors.brandStrong),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  message,
                  style: const TextStyle(fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 4),
                Text(
                  _date(item.createdAt),
                  style: const TextStyle(
                    fontSize: 12,
                    color: LmsColors.inkMuted,
                  ),
                ),
              ],
            ),
          ),
          if (!item.read)
            const Padding(
              padding: EdgeInsets.only(left: 8, top: 4),
              child: CircleAvatar(radius: 4, backgroundColor: LmsColors.brand),
            ),
        ],
      ),
    );
  }

  String _date(DateTime? date) {
    if (date == null) return 'Baru sahaja';
    final diff = DateTime.now().difference(date);
    if (diff.inMinutes < 1) return 'Baru sahaja';
    if (diff.inHours < 1) return '${diff.inMinutes} minit lalu';
    if (diff.inDays < 1) return '${diff.inHours} jam lalu';
    return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
  }
}
