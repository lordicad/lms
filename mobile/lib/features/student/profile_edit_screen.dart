import 'package:flutter/material.dart';

import '../../core/auth/auth_user.dart';
import '../../core/platform/native_file_picker.dart';
import '../../core/theme/lms_theme.dart';
import '../../core/widgets/lms_logo.dart';

class ProfileEditScreen extends StatefulWidget {
  const ProfileEditScreen({
    super.key,
    required this.user,
    required this.roleLabel,
    required this.onSave,
    required this.onSaveAvatar,
  });

  final AuthUser user;
  final String roleLabel;
  final Future<AuthUser> Function({
    required String name,
    required String username,
    String? email,
  })
  onSave;
  final Future<AuthUser> Function(NativeUploadFile file) onSaveAvatar;

  @override
  State<ProfileEditScreen> createState() => _ProfileEditScreenState();
}

class _ProfileEditScreenState extends State<ProfileEditScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _username;
  late final TextEditingController _email;
  var _saving = false;
  var _uploadingAvatar = false;
  late AuthUser _currentUser;

  bool get _emailRequired => widget.user.role == UserRole.teacher;

  @override
  void initState() {
    super.initState();
    _name = TextEditingController(text: widget.user.name);
    _username = TextEditingController(text: widget.user.username);
    _email = TextEditingController(text: widget.user.email ?? '');
    _currentUser = widget.user;
  }

  @override
  void dispose() {
    _name.dispose();
    _username.dispose();
    _email.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() => _saving = true);
    try {
      final updated = await widget.onSave(
        name: _name.text.trim(),
        username: _username.text.trim(),
        email: _email.text.trim().isEmpty ? null : _email.text.trim(),
      );
      if (mounted) {
        Navigator.of(context).pop(updated);
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('$error')));
      }
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  Future<void> _changeAvatar() async {
    try {
      final file = await NativeFilePicker.pickAvatar();
      if (file == null || !mounted) return;

      setState(() => _uploadingAvatar = true);
      final updated = await widget.onSaveAvatar(file);
      if (!mounted) return;
      setState(() => _currentUser = updated);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Gambar profil berjaya dikemas kini.')),
      );
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('$error')));
      }
    } finally {
      if (mounted) setState(() => _uploadingAvatar = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Sunting profil')),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
            children: [
              Center(
                child: Semantics(
                  button: true,
                  label: 'Tukar gambar profil',
                  child: InkWell(
                    onTap: _uploadingAvatar ? null : _changeAvatar,
                    borderRadius: BorderRadius.circular(52),
                    child: Stack(
                      clipBehavior: Clip.none,
                      children: [
                        _AvatarPreview(user: _currentUser),
                        Positioned(
                          right: -2,
                          bottom: -2,
                          child: Container(
                            width: 31,
                            height: 31,
                            decoration: BoxDecoration(
                              color: LmsColors.brand,
                              shape: BoxShape.circle,
                              border: Border.all(color: Colors.white, width: 2),
                            ),
                            child: _uploadingAvatar
                                ? const Padding(
                                    padding: EdgeInsets.all(7),
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                : const Icon(
                                    Icons.camera_alt_outlined,
                                    size: 16,
                                    color: Colors.white,
                                  ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 10),
              const Center(
                child: Text(
                  'Tekan gambar untuk tukar (JPG, PNG atau WEBP, maks. 2 MB)',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 12, color: LmsColors.inkMuted),
                ),
              ),
              const SizedBox(height: 22),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: LmsColors.brandSoft,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Row(
                  children: [
                    const Icon(
                      Icons.manage_accounts_outlined,
                      color: LmsColors.brandStrong,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'Kemaskini butiran akaun ${widget.roleLabel.toLowerCase()}.',
                        style: const TextStyle(
                          color: LmsColors.brandStrong,
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),
              TextFormField(
                controller: _name,
                textCapitalization: TextCapitalization.words,
                decoration: const InputDecoration(
                  labelText: 'Nama penuh',
                  prefixIcon: Icon(Icons.person_outline_rounded),
                ),
                validator: (value) {
                  if (value == null || value.trim().isEmpty) {
                    return 'Sila isi nama anda.';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _username,
                autocorrect: false,
                decoration: const InputDecoration(
                  labelText: 'Nama pengguna',
                  prefixIcon: Icon(Icons.alternate_email_rounded),
                ),
                validator: (value) {
                  final username = value?.trim() ?? '';
                  if (username.length < 3) {
                    return 'Nama pengguna mesti sekurang-kurangnya 3 aksara.';
                  }
                  if (!RegExp(r'^[a-zA-Z0-9._-]+$').hasMatch(username)) {
                    return 'Gunakan huruf, nombor, titik, garis bawah atau sengkang sahaja.';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _email,
                keyboardType: TextInputType.emailAddress,
                autocorrect: false,
                decoration: InputDecoration(
                  labelText: _emailRequired ? 'Emel' : 'Emel (pilihan)',
                  prefixIcon: const Icon(Icons.mail_outline_rounded),
                ),
                validator: (value) {
                  final email = value?.trim() ?? '';
                  if (_emailRequired && email.isEmpty) {
                    return 'Guru perlu memberikan alamat emel.';
                  }
                  if (email.isNotEmpty &&
                      !RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$').hasMatch(email)) {
                    return 'Sila masukkan emel yang sah.';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 28),
              FilledButton.icon(
                onPressed: _saving ? null : _save,
                icon: _saving
                    ? const SizedBox(
                        height: 18,
                        width: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Icon(Icons.check_rounded),
                label: Text(_saving ? 'Menyimpan...' : 'Simpan perubahan'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _AvatarPreview extends StatelessWidget {
  const _AvatarPreview({required this.user});
  final AuthUser user;

  @override
  Widget build(BuildContext context) {
    final url = user.avatarUrl;
    if (url == null || url.isEmpty) {
      return const LmsLogo(size: 88, radius: 28);
    }
    return ClipRRect(
      borderRadius: BorderRadius.circular(28),
      child: Image.network(
        url,
        width: 88,
        height: 88,
        fit: BoxFit.cover,
        errorBuilder: (_, _, _) => const LmsLogo(size: 88, radius: 28),
      ),
    );
  }
}
