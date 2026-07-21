import 'package:flutter/material.dart';

import '../theme/lms_theme.dart';

/// A light-weight shimmer shown while a remote screen is still loading.
class SkeletonBlock extends StatefulWidget {
  const SkeletonBlock({
    super.key,
    required this.height,
    this.width,
    this.radius = 12,
  });

  final double height;
  final double? width;
  final double radius;

  @override
  State<SkeletonBlock> createState() => _SkeletonBlockState();
}

class _SkeletonBlockState extends State<SkeletonBlock>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 1200),
  )..repeat();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => AnimatedBuilder(
    animation: _controller,
    builder: (context, child) {
      final position = (_controller.value * 2) - 1;
      return ShaderMask(
        blendMode: BlendMode.srcATop,
        shaderCallback: (bounds) => LinearGradient(
          begin: Alignment(-1.4 + position, 0),
          end: Alignment(-0.2 + position, 0),
          colors: const [
            Color(0xFFE8EEE5),
            Color(0xFFF8FAF6),
            Color(0xFFE8EEE5),
          ],
          stops: const [0, .48, 1],
        ).createShader(bounds),
        child: child,
      );
    },
    child: Container(
      width: widget.width,
      height: widget.height,
      decoration: BoxDecoration(
        color: LmsColors.surfaceMuted,
        borderRadius: BorderRadius.circular(widget.radius),
      ),
    ),
  );
}

class StudentDashboardSkeleton extends StatelessWidget {
  const StudentDashboardSkeleton({super.key});

  @override
  Widget build(BuildContext context) => ListView(
    padding: const EdgeInsets.fromLTRB(20, 14, 20, 32),
    children: const [
      SkeletonBlock(height: 210, radius: 22),
      SizedBox(height: 28),
      _SkeletonHeading(),
      SizedBox(height: 12),
      _SkeletonRail(),
      SizedBox(height: 26),
      _SkeletonHeading(width: 150),
      SizedBox(height: 12),
      _SkeletonSubjectGrid(),
    ],
  );
}

class SubjectListSkeleton extends StatelessWidget {
  const SubjectListSkeleton({super.key});

  @override
  Widget build(BuildContext context) => ListView(
    padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
    children: const [
      _SkeletonHeading(width: 180),
      SizedBox(height: 12),
      _SkeletonSubjectGrid(),
      SizedBox(height: 24),
      _SkeletonHeading(width: 140),
      SizedBox(height: 12),
      SkeletonBlock(height: 82, radius: 16),
      SizedBox(height: 10),
      SkeletonBlock(height: 82, radius: 16),
    ],
  );
}

class ContentListSkeleton extends StatelessWidget {
  const ContentListSkeleton({super.key, this.count = 5});

  final int count;

  @override
  Widget build(BuildContext context) => ListView.separated(
    padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
    itemCount: count,
    separatorBuilder: (_, _) => const SizedBox(height: 12),
    itemBuilder: (_, _) => const _SkeletonContentRow(),
  );
}

class TeacherDashboardSkeleton extends StatelessWidget {
  const TeacherDashboardSkeleton({super.key});

  @override
  Widget build(BuildContext context) => ListView(
    padding: EdgeInsets.zero,
    children: const [
      _TeacherHeaderPlaceholder(),
      Padding(
        padding: EdgeInsets.fromLTRB(20, 76, 20, 32),
        child: Column(
          children: [
            _SkeletonStatsGrid(),
            SizedBox(height: 26),
            _SkeletonHeading(),
            SizedBox(height: 12),
            _SkeletonRail(),
            SizedBox(height: 26),
            _SkeletonContentRow(),
          ],
        ),
      ),
    ],
  );
}

class _TeacherHeaderPlaceholder extends StatelessWidget {
  const _TeacherHeaderPlaceholder();

  @override
  Widget build(BuildContext context) => Container(
    height: 184,
    color: LmsColors.forest,
    padding: const EdgeInsets.fromLTRB(20, 62, 20, 30),
    child: const Row(
      children: [
        SkeletonBlock(height: 48, width: 48, radius: 16),
        SizedBox(width: 12),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SkeletonBlock(height: 12, width: 80, radius: 6),
            SizedBox(height: 8),
            SkeletonBlock(height: 18, width: 150, radius: 7),
          ],
        ),
      ],
    ),
  );
}

class _SkeletonHeading extends StatelessWidget {
  const _SkeletonHeading({this.width = 210});
  final double width;

  @override
  Widget build(BuildContext context) => Row(
    children: [
      SkeletonBlock(height: 18, width: width, radius: 6),
      const Spacer(),
      const SkeletonBlock(height: 13, width: 62, radius: 5),
    ],
  );
}

class _SkeletonRail extends StatelessWidget {
  const _SkeletonRail();

  @override
  Widget build(BuildContext context) => const SizedBox(
    height: 128,
    child: Row(
      children: [
        Expanded(child: SkeletonBlock(height: 128, radius: 16)),
        SizedBox(width: 12),
        Expanded(child: SkeletonBlock(height: 128, radius: 16)),
      ],
    ),
  );
}

class _SkeletonSubjectGrid extends StatelessWidget {
  const _SkeletonSubjectGrid();

  @override
  Widget build(BuildContext context) => const Column(
    children: [
      Row(
        children: [
          Expanded(child: SkeletonBlock(height: 116, radius: 16)),
          SizedBox(width: 12),
          Expanded(child: SkeletonBlock(height: 116, radius: 16)),
        ],
      ),
      SizedBox(height: 12),
      Row(
        children: [
          Expanded(child: SkeletonBlock(height: 116, radius: 16)),
          SizedBox(width: 12),
          Expanded(child: SkeletonBlock(height: 116, radius: 16)),
        ],
      ),
    ],
  );
}

class _SkeletonStatsGrid extends StatelessWidget {
  const _SkeletonStatsGrid();

  @override
  Widget build(BuildContext context) => const Column(
    children: [
      Row(
        children: [
          Expanded(child: SkeletonBlock(height: 112, radius: 16)),
          SizedBox(width: 12),
          Expanded(child: SkeletonBlock(height: 112, radius: 16)),
        ],
      ),
      SizedBox(height: 12),
      Row(
        children: [
          Expanded(child: SkeletonBlock(height: 112, radius: 16)),
          SizedBox(width: 12),
          Expanded(child: SkeletonBlock(height: 112, radius: 16)),
        ],
      ),
    ],
  );
}

class _SkeletonContentRow extends StatelessWidget {
  const _SkeletonContentRow();

  @override
  Widget build(BuildContext context) => const Row(
    children: [
      SkeletonBlock(height: 58, width: 58, radius: 14),
      SizedBox(width: 13),
      Expanded(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SkeletonBlock(height: 15, width: 180, radius: 5),
            SizedBox(height: 8),
            SkeletonBlock(height: 12, width: 125, radius: 5),
          ],
        ),
      ),
    ],
  );
}
