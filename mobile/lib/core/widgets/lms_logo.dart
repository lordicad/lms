import 'package:flutter/material.dart';

import '../theme/lms_theme.dart';

/// The LMS MOE mark shown on branded surfaces throughout the app.
class LmsLogo extends StatelessWidget {
  const LmsLogo({
    super.key,
    this.size = 48,
    this.radius = 15,
    this.backgroundColor = Colors.white,
  });

  final double size;
  final double radius;
  final Color backgroundColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(radius),
        border: Border.all(color: const Color(0x18FFFFFF)),
        boxShadow: const [
          BoxShadow(
            color: Color(0x180D2714),
            blurRadius: 12,
            offset: Offset(0, 5),
          ),
        ],
      ),
      child: Padding(
        padding: EdgeInsets.all(size * .08),
        child: Image.asset(
          'assets/images/lms_moe_logo.png',
          fit: BoxFit.contain,
          filterQuality: FilterQuality.high,
          errorBuilder: (_, _, _) => Icon(
            Icons.menu_book_rounded,
            color: LmsColors.brand,
            size: size * .52,
          ),
        ),
      ),
    );
  }
}
