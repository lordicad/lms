import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

enum AppLanguage { bm, en }

extension AppLanguageLabel on AppLanguage {
  String get label => this == AppLanguage.bm ? 'Bahasa Melayu' : 'English';
  String get shortLabel => this == AppLanguage.bm ? 'BM' : 'EN';
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
