import 'package:flutter/material.dart';

import '../../core/api/api_client.dart';
import '../../core/auth/auth_repository.dart';
import '../../core/auth/auth_user.dart';
import '../../core/config/app_config.dart';
import '../../core/theme/lms_theme.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.auth, required this.onSignedIn});

  final AuthRepository auth;
  final ValueChanged<AuthUser> onSignedIn;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _loginController = TextEditingController();
  final _passwordController = TextEditingController();
  var _obscurePassword = true;
  var _submitting = false;
  String? _error;

  @override
  void dispose() {
    _loginController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) {
      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final user = await widget.auth.login(
        login: _loginController.text.trim(),
        password: _passwordController.text,
      );

      if (mounted) {
        widget.onSignedIn(user);
      }
    } on ApiException catch (error) {
      if (mounted) {
        setState(() => _error = error.message);
      }
    } catch (_) {
      if (mounted) {
        setState(
          () => _error = 'Tidak dapat menyambung ke pelayan. Sila cuba lagi.',
        );
      }
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
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
                        const _BrandMark(),
                        const SizedBox(height: 32),
                        Text(
                          'Log Masuk',
                          style: Theme.of(context).textTheme.headlineMedium,
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'Selamat kembali. Sila masukkan butiran akaun anda.',
                        ),
                        const SizedBox(height: 24),
                        if (usePreviewAuthentication) ...[
                          const _PreviewNotice(),
                          const SizedBox(height: 16),
                        ],
                        TextFormField(
                          controller: _loginController,
                          keyboardType: TextInputType.emailAddress,
                          textInputAction: TextInputAction.next,
                          autofillHints: const [AutofillHints.username],
                          decoration: const InputDecoration(
                            labelText: 'E-mel log masuk',
                            hintText: 'Contoh: nama@sekolah.edu.my',
                          ),
                          validator: (value) =>
                              value == null || value.trim().isEmpty
                              ? 'Sila isi e-mel log masuk.'
                              : null,
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _passwordController,
                          obscureText: _obscurePassword,
                          textInputAction: TextInputAction.done,
                          autofillHints: const [AutofillHints.password],
                          onFieldSubmitted: (_) => _submit(),
                          decoration: InputDecoration(
                            labelText: 'Kata laluan',
                            suffixIcon: IconButton(
                              tooltip: _obscurePassword
                                  ? 'Papar kata laluan'
                                  : 'Sembunyi kata laluan',
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
                          validator: (value) => value == null || value.isEmpty
                              ? 'Sila isi kata laluan.'
                              : null,
                        ),
                        if (_error != null) ...[
                          const SizedBox(height: 16),
                          _ErrorMessage(message: _error!),
                        ],
                        const SizedBox(height: 24),
                        FilledButton(
                          onPressed: _submitting ? null : _submit,
                          child: _submitting
                              ? const SizedBox(
                                  height: 22,
                                  width: 22,
                                  child: CircularProgressIndicator(
                                    color: Colors.white,
                                    strokeWidth: 2,
                                  ),
                                )
                              : const Text('Log Masuk'),
                        ),
                        const SizedBox(height: 16),
                        const Text(
                          'Gunakan e-mel yang didaftarkan oleh pentadbir. Akaun lama tanpa e-mel masih boleh menggunakan nama pengguna.',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 12,
                            color: LmsColors.inkMuted,
                          ),
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

class _BrandMark extends StatelessWidget {
  const _BrandMark();

  @override
  Widget build(BuildContext context) {
    return const Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        DecoratedBox(
          decoration: BoxDecoration(
            color: LmsColors.brand,
            borderRadius: BorderRadius.all(Radius.circular(10)),
          ),
          child: Padding(
            padding: EdgeInsets.symmetric(horizontal: 11, vertical: 7),
            child: Icon(Icons.school_rounded, color: Colors.white, size: 22),
          ),
        ),
        SizedBox(width: 10),
        Text(
          'WeLearn',
          style: TextStyle(
            color: LmsColors.ink,
            fontSize: 22,
            fontWeight: FontWeight.w800,
          ),
        ),
      ],
    );
  }
}

class _ErrorMessage extends StatelessWidget {
  const _ErrorMessage({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: const Color(0xFFFDE8E8),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            const Icon(Icons.error_outline, color: Color(0xFFB91C1C)),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                message,
                style: const TextStyle(color: Color(0xFF991B1B)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PreviewNotice extends StatelessWidget {
  const _PreviewNotice();

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: LmsColors.brandSoft,
        borderRadius: BorderRadius.circular(10),
      ),
      child: const Padding(
        padding: EdgeInsets.all(12),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(Icons.info_outline, color: LmsColors.brand),
            SizedBox(width: 8),
            Expanded(
              child: Text(
                'Mod pratonton: guna nama pengguna “murid” untuk paparan Murid atau “guru” untuk paparan Guru. Apa-apa kata laluan boleh digunakan.',
                style: TextStyle(color: LmsColors.brandStrong, fontSize: 12),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
