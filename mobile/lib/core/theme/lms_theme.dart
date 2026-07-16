import 'package:flutter/material.dart';

/// Mobile equivalents of the live LMS colour and radius tokens.
abstract final class LmsColors {
  static const background = Color(0xFFF7F8FA);
  static const surface = Color(0xFFFFFFFF);
  static const surfaceMuted = Color(0xFFF1F3F6);
  static const ink = Color(0xFF0F172A);
  static const inkMuted = Color(0xFF5B6675);
  static const brand = Color(0xFF0F766E);
  static const brandStrong = Color(0xFF115E56);
  static const brandSoft = Color(0xFFE6F5F2);
  static const border = Color(0x140F172A);
  static const success = Color(0xFF15803D);
  static const warning = Color(0xFFB45309);
}

ThemeData buildLmsTheme() {
  const scheme = ColorScheme.light(
    primary: LmsColors.brand,
    onPrimary: Colors.white,
    secondary: LmsColors.brand,
    onSecondary: Colors.white,
    surface: LmsColors.surface,
    onSurface: LmsColors.ink,
    error: Color(0xFFB91C1C),
  );

  final outlinedBorder = OutlineInputBorder(
    borderRadius: BorderRadius.circular(10),
    borderSide: const BorderSide(color: LmsColors.border),
  );

  return ThemeData(
    useMaterial3: true,
    colorScheme: scheme,
    scaffoldBackgroundColor: LmsColors.background,
    fontFamily: 'sans-serif',
    textTheme: const TextTheme(
      headlineLarge: TextStyle(
        fontWeight: FontWeight.w800,
        color: LmsColors.ink,
      ),
      headlineMedium: TextStyle(
        fontWeight: FontWeight.w800,
        color: LmsColors.ink,
      ),
      titleLarge: TextStyle(fontWeight: FontWeight.w800, color: LmsColors.ink),
      titleMedium: TextStyle(fontWeight: FontWeight.w700, color: LmsColors.ink),
      bodyLarge: TextStyle(color: LmsColors.ink),
      bodyMedium: TextStyle(color: LmsColors.inkMuted),
      labelLarge: TextStyle(fontWeight: FontWeight.w700),
    ),
    appBarTheme: const AppBarTheme(
      backgroundColor: LmsColors.surface,
      foregroundColor: LmsColors.ink,
      elevation: 0,
      surfaceTintColor: Colors.transparent,
      centerTitle: false,
    ),
    cardTheme: CardThemeData(
      color: LmsColors.surface,
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(14),
        side: const BorderSide(color: LmsColors.border),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: LmsColors.surface,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      border: outlinedBorder,
      enabledBorder: outlinedBorder,
      focusedBorder: outlinedBorder.copyWith(
        borderSide: const BorderSide(color: LmsColors.brand, width: 2),
      ),
      errorBorder: outlinedBorder.copyWith(
        borderSide: const BorderSide(color: Color(0xFFB91C1C)),
      ),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: LmsColors.brand,
        foregroundColor: Colors.white,
        minimumSize: const Size.fromHeight(52),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        textStyle: const TextStyle(fontWeight: FontWeight.w800),
      ),
    ),
  );
}
