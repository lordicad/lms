import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/teacher/teacher_models.dart';
import '../../core/teacher/teacher_repository.dart';
import '../../core/theme/lms_theme.dart';
import '../../core/widgets/app_feedback.dart';
import '../student/widgets/content_widgets.dart';

/// Connect / disconnect the teacher's YouTube channels, so their own YouTube videos count
/// toward the talent signal.
///
/// Connecting deliberately opens the existing **web** OAuth flow in a browser rather than
/// re-implementing consent on device: the web callback reads the channel list, stores it and
/// re-attributes the teacher's videos, so mobile and web behave identically.
class YoutubeConnectScreen extends StatefulWidget {
  const YoutubeConnectScreen({super.key, required this.repository});

  final TeacherRepository repository;

  @override
  State<YoutubeConnectScreen> createState() => _YoutubeConnectScreenState();
}

class _YoutubeConnectScreenState extends State<YoutubeConnectScreen> {
  late Future<YoutubeChannelsData> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.repository.youtubeChannels();
  }

  void _reload() => setState(() => _future = widget.repository.youtubeChannels());

  Future<void> _connect(String url) async {
    final uri = Uri.tryParse(url);
    if (uri == null) return;

    await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!mounted) return;

    AppFeedback.info(
      'Dibuka dalam pelayar',
      description: 'Log masuk dan benarkan akses, kemudian kembali ke sini dan tekan Muat semula.',
    );
  }

  Future<void> _disconnect(YoutubeChannelItem channel) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Putuskan sambungan?'),
        content: Text(
          'Video daripada "${channel.title}" tidak lagi dikira untuk Skor Bakat anda. '
          'Ia masih boleh ditonton murid.',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: LmsColors.danger),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Putuskan'),
          ),
        ],
      ),
    );
    if (ok != true) return;

    try {
      await widget.repository.disconnectYoutube(channel.id);
      if (!mounted) return;
      AppFeedback.success(
        'Channel diputuskan',
        description: '${channel.title} tidak lagi disambungkan.',
      );
      _reload();
    } catch (e) {
      if (mounted) AppFeedback.error('Tidak berjaya', description: '$e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Sambung YouTube'),
        actions: [
          IconButton(
            tooltip: 'Muat semula',
            onPressed: _reload,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: FutureBuilder<YoutubeChannelsData>(
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

          if (!data.configured) {
            return const StateMessage(
              icon: Icons.link_off,
              title: 'Sambungan YouTube belum tersedia',
              subtitle: 'Sila hubungi pentadbir MOE untuk mengaktifkannya.',
            );
          }

          return Column(
            children: [
              Expanded(
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
                  children: [
                    const _PrivacyNote(),
                    const SizedBox(height: 16),
                    Text('Channel disambungkan', style: Theme.of(context).textTheme.titleLarge),
                    const SizedBox(height: 8),
                    if (data.channels.isEmpty)
                      const _EmptyChannels()
                    else
                      ...data.channels.map(
                        (c) => _ChannelTile(channel: c, onDisconnect: () => _disconnect(c)),
                      ),
                  ],
                ),
              ),
              SafeArea(
                top: false,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 4, 20, 16),
                  child: FilledButton.icon(
                    onPressed: () => _connect(data.connectUrl),
                    icon: const Icon(Icons.link),
                    label: Text(
                      data.channels.isEmpty ? 'Sambung akaun YouTube' : 'Sambung channel lain',
                    ),
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _PrivacyNote extends StatelessWidget {
  const _PrivacyNote();

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: LmsColors.brandSoft,
        borderRadius: BorderRadius.circular(14),
      ),
      padding: const EdgeInsets.all(14),
      child: const Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.privacy_tip_outlined, size: 18, color: LmsColors.brandStrong),
          SizedBox(width: 10),
          Expanded(
            child: Text(
              'Sistem hanya membaca senarai channel anda untuk pengesahan pemilikan. '
              'Tiada token OAuth disimpan — untuk sahkan semula, sambung sekali lagi.',
              style: TextStyle(fontSize: 12, color: LmsColors.brandStrong),
            ),
          ),
        ],
      ),
    );
  }
}

class _EmptyChannels extends StatelessWidget {
  const _EmptyChannels();

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      padding: const EdgeInsets.all(16),
      child: Text(
        'Belum ada channel disambungkan. Video YouTube yang anda tambah kini dikira '
        'sebagai "Rujukan" dan tidak menyumbang kepada Skor Bakat.',
        style: Theme.of(context).textTheme.bodyMedium,
      ),
    );
  }
}

class _ChannelTile extends StatelessWidget {
  const _ChannelTile({required this.channel, required this.onDisconnect});

  final YoutubeChannelItem channel;
  final VoidCallback onDisconnect;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      padding: const EdgeInsets.all(12),
      child: Row(
        children: [
          ClipOval(
            child: SizedBox(
              width: 42,
              height: 42,
              child: (channel.thumbnailUrl != null && channel.thumbnailUrl!.isNotEmpty)
                  ? Image.network(
                      channel.thumbnailUrl!,
                      fit: BoxFit.cover,
                      errorBuilder: (_, _, _) => const _ChannelFallback(),
                    )
                  : const _ChannelFallback(),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  channel.title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 2),
                Text(
                  channel.verifiedAt == null
                      ? 'Disahkan'
                      : 'Disahkan ${channel.verifiedAt!.split(' ').first}',
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              ],
            ),
          ),
          TextButton(
            onPressed: onDisconnect,
            style: TextButton.styleFrom(foregroundColor: LmsColors.danger),
            child: const Text('Putuskan'),
          ),
        ],
      ),
    );
  }
}

class _ChannelFallback extends StatelessWidget {
  const _ChannelFallback();

  @override
  Widget build(BuildContext context) => const ColoredBox(
    color: LmsColors.brandSoft,
    child: Icon(Icons.smart_display_outlined, size: 20, color: LmsColors.brandStrong),
  );
}
