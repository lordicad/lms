import 'package:flutter/material.dart';

import '../../core/api/api_client.dart';
import '../../core/auth/auth_user.dart';
import '../../core/settings/app_settings.dart';
import '../../core/theme/lms_theme.dart';

/// Mobile equivalent of the web's first-password page. An administrator may
/// know the issued password, so a new student/teacher must replace it before
/// entering the learning or teacher surfaces.
class FirstPasswordScreen extends StatefulWidget {
  const FirstPasswordScreen({
    super.key,
    required this.user,
    required this.onSave,
    required this.onSignOut,
  });

  final AuthUser user;
  final Future<AuthUser> Function(String password, String confirmation) onSave;
  final Future<void> Function() onSignOut;

  @override
  State<FirstPasswordScreen> createState() => _FirstPasswordScreenState();
}

class _FirstPasswordScreenState extends State<FirstPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _password = TextEditingController();
  final _confirmation = TextEditingController();
  var _saving = false;
  var _obscurePassword = true;
  var _obscureConfirmation = true;
  String? _error;

  @override
  void dispose() {
    _password.dispose();
    _confirmation.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      await widget.onSave(_password.text, _confirmation.text);
    } on ApiException catch (error) {
      if (mounted) setState(() => _error = error.message);
    } catch (_) {
      if (mounted) {
        setState(
          () => _error = context.copy(
            bm: 'Tidak dapat menetapkan kata laluan. Sila cuba lagi.',
            en: 'Unable to set your password. Please try again.',
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final english = context.appLanguage == AppLanguage.en;
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 460),
              child: Card(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const Icon(
                          Icons.lock_reset_rounded,
                          size: 44,
                          color: LmsColors.brand,
                        ),
                        const SizedBox(height: 18),
                        Text(
                          english
                              ? 'Set your own password'
                              : 'Tetapkan kata laluan anda',
                          style: Theme.of(context).textTheme.headlineMedium,
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 9),
                        Text(
                          english
                              ? 'For your security, replace the password given by the administrator before continuing.'
                              : 'Untuk keselamatan, gantikan kata laluan yang diberi oleh pentadbir sebelum meneruskan.',
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: LmsColors.inkMuted),
                        ),
                        const SizedBox(height: 24),
                        TextFormField(
                          controller: _password,
                          obscureText: _obscurePassword,
                          textInputAction: TextInputAction.next,
                          decoration: InputDecoration(
                            labelText: english
                                ? 'New password'
                                : 'Kata laluan baharu',
                            prefixIcon: const Icon(Icons.lock_outline_rounded),
                            suffixIcon: IconButton(
                              icon: Icon(
                                _obscurePassword
                                    ? Icons.visibility_outlined
                                    : Icons.visibility_off_outlined,
                              ),
                              onPressed: () => setState(
                                () => _obscurePassword = !_obscurePassword,
                              ),
                            ),
                          ),
                          validator: (value) => (value?.length ?? 0) < 6
                              ? (english
                                    ? 'Use at least 6 characters.'
                                    : 'Gunakan sekurang-kurangnya 6 aksara.')
                              : null,
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _confirmation,
                          obscureText: _obscureConfirmation,
                          textInputAction: TextInputAction.done,
                          onFieldSubmitted: (_) => _save(),
                          decoration: InputDecoration(
                            labelText: english
                                ? 'Confirm new password'
                                : 'Ulang kata laluan baharu',
                            prefixIcon: const Icon(Icons.lock_outline_rounded),
                            suffixIcon: IconButton(
                              icon: Icon(
                                _obscureConfirmation
                                    ? Icons.visibility_outlined
                                    : Icons.visibility_off_outlined,
                              ),
                              onPressed: () => setState(
                                () => _obscureConfirmation =
                                    !_obscureConfirmation,
                              ),
                            ),
                          ),
                          validator: (value) => value != _password.text
                              ? (english
                                    ? 'Passwords do not match.'
                                    : 'Kata laluan tidak sama.')
                              : null,
                        ),
                        if (_error != null) ...[
                          const SizedBox(height: 16),
                          Text(
                            _error!,
                            style: const TextStyle(color: LmsColors.danger),
                          ),
                        ],
                        const SizedBox(height: 24),
                        FilledButton(
                          onPressed: _saving ? null : _save,
                          child: _saving
                              ? const SizedBox(
                                  height: 22,
                                  width: 22,
                                  child: CircularProgressIndicator(
                                    color: Colors.white,
                                    strokeWidth: 2,
                                  ),
                                )
                              : Text(english ? 'Continue' : 'Teruskan'),
                        ),
                        TextButton(
                          onPressed: _saving ? null : widget.onSignOut,
                          child: Text(english ? 'Sign out' : 'Log keluar'),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
