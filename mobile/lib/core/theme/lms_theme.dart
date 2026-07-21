import 'package:flutter/material.dart';

/// WeLearn design tokens — a warm green identity layered over the LMS MOE product.
///
/// Token names are kept stable; only the values changed from the old teal set, so
/// every screen reskins to WeLearn at once. New tokens (forest, accent, …) were
/// added for the WeLearn hero surfaces.
abstract final class LmsColors {
  static const background = Color(0xFFF6F8F3); // off-white page
  static const surface = Color(0xFFFFFFFF); // cards, panels, dialogs
  static const surfaceMuted = Color(0xFFEDF2E8); // inputs, tracks, skeleton
  static const ink = Color(0xFF17251A); // headings, primary text
  static const inkMuted = Color(0xFF5F6F60); // supporting text
  static const inkFaint = Color(0xFF8A968B); // tertiary text, inactive icons
  static const brand = Color(0xFF4A7C3A); // primary CTA, active navigation
  static const brandStrong = Color(0xFF3E6B33); // links, pressed state
  static const brandSoft = Color(0xFFEAF2E3); // badges, icon containers
  static const forest = Color(0xFF1B3520); // dark hero / teacher header
  static const accent = Color(0xFF8FBF6F); // progress + CTA on dark surfaces
  static const onAccent = Color(0xFF12290F); // text/icon on [accent]
  static const border = Color(0x141A2B1E); // rgba(26,43,30,.08)
  static const success = Color(0xFF15803D);
  static const warning = Color(0xFFB45309);
  static const danger = Color(0xFFB91C1C);
}

ThemeData buildLmsTheme() {
  const scheme = ColorScheme.light(
    primary: LmsColors.brand,
    onPrimary: Colors.white,
    secondary: LmsColors.brandStrong,
    onSecondary: Colors.white,
    surface: LmsColors.surface,
    onSurface: LmsColors.ink,
    error: LmsColors.danger,
  );

  final outlinedBorder = OutlineInputBorder(
    borderRadius: BorderRadius.circular(12),
    borderSide: const BorderSide(color: LmsColors.border),
  );

  return ThemeData(
    useMaterial3: true,
    colorScheme: scheme,
    scaffoldBackgroundColor: LmsColors.background,
    fontFamily: 'Plus Jakarta Sans',
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
        borderRadius: BorderRadius.circular(16),
        side: const BorderSide(color: LmsColors.border),
      ),
    ),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: LmsColors.surface,
      indicatorColor: LmsColors.brandSoft,
      elevation: 0,
      height: 68,
      labelTextStyle: const WidgetStatePropertyAll(
        TextStyle(fontSize: 11, fontWeight: FontWeight.w700),
      ),
      iconTheme: WidgetStateProperty.resolveWith(
        (states) => IconThemeData(
          color: states.contains(WidgetState.selected)
              ? LmsColors.brand
              : LmsColors.inkFaint,
        ),
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
        borderSide: const BorderSide(color: LmsColors.danger),
      ),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: LmsColors.brand,
        foregroundColor: Colors.white,
        minimumSize: const Size.fromHeight(52),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        textStyle: const TextStyle(fontWeight: FontWeight.w800),
      ),
    ),
  );
}

/// System-level dark palette. Screens that use Material surfaces, dialogs,
/// navigation and form controls update immediately with this theme; older
/// content cards retain their readable WeLearn styling while they are migrated.
ThemeData buildLmsDarkTheme() {
  const surface = Color(0xFF172019);
  const surfaceMuted = Color(0xFF243027);
  const background = Color(0xFF101712);
  const text = Color(0xFFE8F0E5);
  const muted = Color(0xFFB5C4B5);
  const border = Color(0x335D8062);
  const scheme = ColorScheme.dark(
    primary: LmsColors.accent,
    onPrimary: LmsColors.onAccent,
    secondary: Color(0xFFB8D8A1),
    onSecondary: LmsColors.forest,
    surface: surface,
    onSurface: text,
    error: Color(0xFFFFB4AB),
  );

  final outlinedBorder = OutlineInputBorder(
    borderRadius: BorderRadius.circular(12),
    borderSide: const BorderSide(color: border),
  );

  return ThemeData(
    useMaterial3: true,
    brightness: Brightness.dark,
    colorScheme: scheme,
    scaffoldBackgroundColor: background,
    fontFamily: 'Plus Jakarta Sans',
    textTheme: const TextTheme(
      headlineLarge: TextStyle(fontWeight: FontWeight.w800, color: text),
      headlineMedium: TextStyle(fontWeight: FontWeight.w800, color: text),
      titleLarge: TextStyle(fontWeight: FontWeight.w800, color: text),
      titleMedium: TextStyle(fontWeight: FontWeight.w700, color: text),
      bodyLarge: TextStyle(color: text),
      bodyMedium: TextStyle(color: muted),
      labelLarge: TextStyle(fontWeight: FontWeight.w700),
    ),
    appBarTheme: const AppBarTheme(
      backgroundColor: surface,
      foregroundColor: text,
      elevation: 0,
      surfaceTintColor: Colors.transparent,
      centerTitle: false,
    ),
    cardTheme: CardThemeData(
      color: surface,
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: const BorderSide(color: border),
      ),
    ),
    navigationBarTheme: const NavigationBarThemeData(
      backgroundColor: surface,
      indicatorColor: Color(0xFF35513A),
      elevation: 0,
      height: 68,
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: surfaceMuted,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      border: outlinedBorder,
      enabledBorder: outlinedBorder,
      focusedBorder: outlinedBorder.copyWith(
        borderSide: const BorderSide(color: LmsColors.accent, width: 2),
      ),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: LmsColors.accent,
        foregroundColor: LmsColors.onAccent,
        minimumSize: const Size.fromHeight(52),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        textStyle: const TextStyle(fontWeight: FontWeight.w800),
      ),
    ),
  );
}
