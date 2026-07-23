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
      body: LayoutBuilder(
        builder: (context, constraints) {
          final wide = constraints.maxWidth >= 760;
          final panel = _LoginFormPanel(
            formKey: _formKey,
            loginController: _loginController,
            passwordController: _passwordController,
            obscurePassword: _obscurePassword,
            submitting: _submitting,
            error: _error,
            onTogglePassword: () =>
                setState(() => _obscurePassword = !_obscurePassword),
            onSubmit: _submit,
          );

          return SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: EdgeInsets.all(wide ? 28 : 18),
                child: ConstrainedBox(
                  constraints: BoxConstraints(
                    maxWidth: wide ? 980 : 460,
                    minHeight: wide ? 560 : 0,
                  ),
                  child: Card(
                    clipBehavior: Clip.antiAlias,
                    child: wide
                        ? IntrinsicHeight(
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              children: [
                                const Expanded(child: _ArtworkPanel()),
                                Expanded(child: panel),
                              ],
                            ),
                          )
                        : Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const _ArtworkPanel(compact: true),
                              panel,
                            ],
                          ),
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

class _LoginFormPanel extends StatelessWidget {
  const _LoginFormPanel({
    required this.formKey,
    required this.loginController,
    required this.passwordController,
    required this.obscurePassword,
    required this.submitting,
    required this.error,
    required this.onTogglePassword,
    required this.onSubmit,
  });

  final GlobalKey<FormState> formKey;
  final TextEditingController loginController;
  final TextEditingController passwordController;
  final bool obscurePassword;
  final bool submitting;
  final String? error;
  final VoidCallback onTogglePassword;
  final VoidCallback onSubmit;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.all(24),
    child: Form(
      key: formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        mainAxisSize: MainAxisSize.min,
        children: [
          const _BrandMark(),
          const SizedBox(height: 32),
          Text('Log Masuk', style: Theme.of(context).textTheme.headlineMedium),
          const SizedBox(height: 8),
          const Text('Selamat kembali. Sila masukkan butiran akaun anda.'),
          const SizedBox(height: 24),
          if (usePreviewAuthentication) ...[
            const _PreviewNotice(),
            const SizedBox(height: 16),
          ],
          TextFormField(
            controller: loginController,
            keyboardType: TextInputType.emailAddress,
            textInputAction: TextInputAction.next,
            autofillHints: const [AutofillHints.username],
            decoration: const InputDecoration(
              labelText: 'E-mel log masuk',
              hintText: 'Contoh: nama@sekolah.edu.my',
            ),
            validator: (value) => value == null || value.trim().isEmpty
                ? 'Sila isi e-mel log masuk.'
                : null,
          ),
          const SizedBox(height: 16),
          TextFormField(
            controller: passwordController,
            obscureText: obscurePassword,
            textInputAction: TextInputAction.done,
            autofillHints: const [AutofillHints.password],
            onFieldSubmitted: (_) => onSubmit(),
            decoration: InputDecoration(
              labelText: 'Kata laluan',
              suffixIcon: IconButton(
                tooltip: obscurePassword
                    ? 'Papar kata laluan'
                    : 'Sembunyi kata laluan',
                icon: Icon(
                  obscurePassword
                      ? Icons.visibility_outlined
                      : Icons.visibility_off_outlined,
                ),
                onPressed: onTogglePassword,
              ),
            ),
            validator: (value) =>
                value == null || value.isEmpty ? 'Sila isi kata laluan.' : null,
          ),
          if (error != null) ...[
            const SizedBox(height: 16),
            _ErrorMessage(message: error!),
          ],
          const SizedBox(height: 24),
          FilledButton(
            onPressed: submitting ? null : onSubmit,
            child: submitting
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
            style: TextStyle(fontSize: 12, color: LmsColors.inkMuted),
          ),
        ],
      ),
    ),
  );
}

class _ArtworkPanel extends StatelessWidget {
  const _ArtworkPanel({this.compact = false});

  final bool compact;

  @override
  Widget build(BuildContext context) => Container(
    height: compact ? 220 : null,
    constraints: compact ? null : const BoxConstraints(minHeight: 560),
    color: const Color(0xFFF5F8EF),
    child: Stack(
      fit: StackFit.expand,
      children: [
        Image.asset(
          'assets/images/auth_pic.png',
          fit: BoxFit.contain,
          alignment: Alignment.center,
        ),
        Positioned(
          left: 22,
          right: 22,
          bottom: 22,
          child: DecoratedBox(
            decoration: BoxDecoration(
              color: const Color(0xEAF5F8EF),
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: const Color(0x338A9A80)),
            ),
            child: const Padding(
              padding: EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    'Ruang belajar digital sekolah rendah.',
                    style: TextStyle(
                      color: Color(0xFF24402C),
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  SizedBox(height: 6),
                  Text(
                    'Video, bahan dan kuiz dalam satu portal.',
                    style: TextStyle(
                      color: Color(0xFF55684F),
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    ),
  );
}

class _BrandMark extends StatelessWidget {
  const _BrandMark();

  @override
  Widget build(BuildContext context) => const Row(
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
