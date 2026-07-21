import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

enum AppLanguage { bm, en }

extension AppLanguageLabel on AppLanguage {
  String get label => this == AppLanguage.bm ? 'Bahasa Melayu' : 'English';
  String get shortLabel => this == AppLanguage.bm ? 'BM' : 'EN';
}

/// Lightweight app-local translation scope. Content titles remain in the
/// language supplied by the teacher, while the application chrome switches
/// consistently between Bahasa Melayu and English.
class AppLanguageScope extends InheritedWidget {
  const AppLanguageScope({
    super.key,
    required this.language,
    required super.child,
  });

  final AppLanguage language;

  static AppLanguage of(BuildContext context) =>
      context
          .dependOnInheritedWidgetOfExactType<AppLanguageScope>()
          ?.language ??
      AppLanguage.bm;

  @override
  bool updateShouldNotify(AppLanguageScope oldWidget) =>
      language != oldWidget.language;
}

extension AppLanguageCopy on BuildContext {
  AppLanguage get appLanguage => AppLanguageScope.of(this);

  String copy({required String bm, required String en}) =>
      appLanguage == AppLanguage.en ? en : bm;
}

/// Keeps the two device-only display preferences outside the user account.
/// They therefore work before sign-in and do not alter the web profile.
class AppSettings {
  static const _themeKey = 'appearance.theme_mode';
  static const _languageKey = 'appearance.language';

  static Future<({ThemeMode themeMode, AppLanguage language})> load() async {
    final preferences = await SharedPreferences.getInstance();
    final isDark = preferences.getBool(_themeKey) ?? false;
    final language = preferences.getString(_languageKey) == 'en'
        ? AppLanguage.en
        : AppLanguage.bm;
    return (
      themeMode: isDark ? ThemeMode.dark : ThemeMode.light,
      language: language,
    );
  }

  static Future<void> saveTheme(ThemeMode value) async {
    final preferences = await SharedPreferences.getInstance();
    await preferences.setBool(_themeKey, value == ThemeMode.dark);
  }

  static Future<void> saveLanguage(AppLanguage value) async {
    final preferences = await SharedPreferences.getInstance();
    await preferences.setString(_languageKey, value.name);
  }
}
