import 'package:flutter/material.dart';
import 'package:toastification/toastification.dart';

import '../theme/lms_theme.dart';

/// Short, non-blocking feedback for completed actions throughout the app.
///
/// The app root supplies [ToastificationWrapper], so these messages can remain
/// visible while a form closes or a route changes.
class AppFeedback {
  const AppFeedback._();

  static void success(String title, {String? description}) => _show(
    type: ToastificationType.success,
    title: title,
    description: description,
    color: LmsColors.brand,
    icon: Icons.check_circle_rounded,
  );

  static void info(String title, {String? description}) => _show(
    type: ToastificationType.info,
    title: title,
    description: description,
    color: LmsColors.brand,
    icon: Icons.info_rounded,
  );

  static void error(String title, {String? description}) => _show(
    type: ToastificationType.error,
    title: title,
    description: description,
    color: LmsColors.danger,
    icon: Icons.error_rounded,
  );

  static void _show({
    required ToastificationType type,
    required String title,
    required String? description,
    required Color color,
    required IconData icon,
  }) {
    toastification.show(
      type: type,
      style: ToastificationStyle.flat,
      autoCloseDuration: const Duration(seconds: 3),
      title: Text(title),
      description: description == null ? null : Text(description),
      alignment: Alignment.topCenter,
      animationDuration: const Duration(milliseconds: 320),
      icon: Icon(icon, color: color),
      showIcon: true,
      primaryColor: color,
      backgroundColor: LmsColors.surface,
      foregroundColor: LmsColors.ink,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
      margin: const EdgeInsets.fromLTRB(16, 18, 16, 0),
      borderRadius: BorderRadius.circular(16),
      boxShadow: const [
        BoxShadow(
          color: Color(0x1A1B3520),
          blurRadius: 18,
          offset: Offset(0, 8),
        ),
      ],
      showProgressBar: false,
    );
  }
}
