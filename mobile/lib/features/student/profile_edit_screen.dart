import 'dart:async';

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
    required this.loadOptions,
    required this.onSave,
    required this.onSaveAvatar,
  });

  final AuthUser user;
  final String roleLabel;
  final Future<ProfileOptions> Function() loadOptions;
  final Future<AuthUser> Function(ProfileUpdate update) onSave;
  final Future<AuthUser> Function(NativeUploadFile file) onSaveAvatar;

  @override
  State<ProfileEditScreen> createState() => _ProfileEditScreenState();
}

class _ProfileEditScreenState extends State<ProfileEditScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _username;
  late final TextEditingController _email;
  late final TextEditingController _guardianName;
  late final TextEditingController _guardianPhone;
  late final TextEditingController _guardianEmail;
  late final TextEditingController _phone;
  late final TextEditingController _position;

  var _saving = false;
  var _uploadingAvatar = false;
  late AuthUser _currentUser;

  bool get _isAdmin => widget.user.role == UserRole.admin;
  bool get _isStudent => widget.user.role == UserRole.student;
  bool get _isTeacher => widget.user.role == UserRole.teacher;

  @override
  void initState() {
    super.initState();
    _name = TextEditingController(text: widget.user.name);
    _username = TextEditingController(text: widget.user.username);
    _email = TextEditingController(text: widget.user.email ?? '');
    _guardianName = TextEditingController(text: widget.user.guardianName ?? '');
    _guardianPhone = TextEditingController(
      text: widget.user.guardianPhone ?? '',
    );
    _guardianEmail = TextEditingController(
      text: widget.user.guardianEmail ?? '',
    );
    _phone = TextEditingController(text: widget.user.phone ?? '');
    _position = TextEditingController(text: widget.user.position ?? '');
    _currentUser = widget.user;
  }

  @override
  void dispose() {
    _name.dispose();
    _username.dispose();
    _email.dispose();
    _guardianName.dispose();
    _guardianPhone.dispose();
    _guardianEmail.dispose();
    _phone.dispose();
    _position.dispose();
    super.dispose();
  }

  String? _clean(TextEditingController controller) {
    final value = controller.text.trim();
    return value.isEmpty ? null : value;
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _saving = true);
    try {
      final updated = await widget.onSave(
        ProfileUpdate(
          name: _name.text.trim(),
          username: _username.text.trim(),
          email: _clean(_email),
          phone: _clean(_phone),
        ),
      );
      if (mounted) Navigator.of(context).pop(updated);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('$error')));
      }
    } finally {
      if (mounted) setState(() => _saving = false);
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
              _notice(),
              const SizedBox(height: 24),
              _accountFields(),
              const SizedBox(height: 20),
              _profileFields(),
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

  Widget _notice() => Container(
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
  );

  Widget _accountFields() => Column(
    children: [
      TextFormField(
        controller: _name,
        textCapitalization: TextCapitalization.words,
        readOnly: !_isAdmin,
        decoration: InputDecoration(
          labelText: _isAdmin ? 'Nama penuh' : 'Nama rasmi',
          prefixIcon: Icon(Icons.person_outline_rounded),
          helperText: _isAdmin
              ? null
              : 'Nama rasmi dikunci mengikut rekod sekolah.',
        ),
        validator: (value) => value == null || value.trim().isEmpty
            ? 'Sila isi nama anda.'
            : null,
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
        readOnly: true,
        decoration: const InputDecoration(
          labelText: 'E-mel log masuk',
          prefixIcon: Icon(Icons.mail_outline_rounded),
          helperText:
              'E-mel log masuk tidak boleh diubah. Hubungi pentadbir jika perlu.',
        ),
      ),
    ],
  );

  Widget _profileFields() {
    if (!_isStudent && !_isTeacher) return const SizedBox.shrink();

    if (_isTeacher) return _teacherFields();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Maklumat sekolah',
          style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
        ),
        const SizedBox(height: 12),
        _ReadOnlyInfoTile(
          icon: Icons.account_balance_outlined,
          label: 'Sekolah',
          value: widget.user.school?.name ?? 'Belum ditetapkan',
        ),
        const SizedBox(height: 12),
        _ReadOnlyInfoTile(
          icon: Icons.school_outlined,
          label: 'Tahun',
          value: widget.user.grade?.name ?? 'Belum ditetapkan',
        ),
        const SizedBox(height: 12),
        _ReadOnlyInfoTile(
          icon: Icons.groups_outlined,
          label: 'Kelas',
          value: widget.user.schoolClass?.label ?? 'Belum ditetapkan',
        ),
        const SizedBox(height: 22),
        const Text(
          'Maklumat penjaga',
          style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
        ),
        const SizedBox(height: 12),
        _ReadOnlyInfoTile(
          icon: Icons.family_restroom_outlined,
          label: 'Nama penjaga',
          value: _guardianName.text.isEmpty
              ? 'Belum ditetapkan'
              : _guardianName.text,
        ),
        const SizedBox(height: 12),
        _ReadOnlyInfoTile(
          icon: Icons.phone_outlined,
          label: 'Nombor telefon penjaga',
          value: _guardianPhone.text.isEmpty
              ? 'Belum ditetapkan'
              : _guardianPhone.text,
        ),
        const SizedBox(height: 12),
        _ReadOnlyInfoTile(
          icon: Icons.mail_outline_rounded,
          label: 'E-mel penjaga',
          value: _guardianEmail.text.isEmpty
              ? 'Belum ditetapkan'
              : _guardianEmail.text,
        ),
        const SizedBox(height: 8),
        const Text(
          'Butiran sekolah dan penjaga dikunci mengikut web. Hubungi pentadbir untuk perubahan rekod.',
          style: TextStyle(fontSize: 12, color: LmsColors.inkMuted),
        ),
      ],
    );
  }

  Widget _teacherFields() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      const Text(
        'Maklumat guru',
        style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
      ),
      const SizedBox(height: 12),
      TextFormField(
        controller: _phone,
        keyboardType: TextInputType.phone,
        decoration: const InputDecoration(
          labelText: 'Nombor telefon',
          prefixIcon: Icon(Icons.phone_outlined),
        ),
        validator: (value) {
          final phone = value?.trim() ?? '';
          if (phone.isNotEmpty &&
              !RegExp(r'^\+?[0-9\s\-()]{6,20}$').hasMatch(phone)) {
            return 'Sila masukkan nombor telefon yang sah.';
          }
          return null;
        },
      ),
      const SizedBox(height: 16),
      _ReadOnlyInfoTile(
        icon: Icons.work_outline_rounded,
        label: 'Jawatan',
        value: _position.text.isEmpty ? 'Belum ditetapkan' : _position.text,
      ),
      const SizedBox(height: 22),
      const Text(
        'Sekolah dan kelas',
        style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
      ),
      const SizedBox(height: 12),
      _ReadOnlyInfoTile(
        icon: Icons.account_balance_outlined,
        label: 'Sekolah',
        value: widget.user.school?.name ?? 'Belum ditetapkan',
      ),
      const SizedBox(height: 12),
      _ReadOnlyInfoTile(
        icon: Icons.groups_outlined,
        label: 'Kelas guru kelas',
        value: widget.user.homeroomClass?.label ?? 'Bukan guru kelas',
      ),
      const SizedBox(height: 22),
      const Text(
        'Subjek diajar',
        style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
      ),
      const SizedBox(height: 10),
      Wrap(
        spacing: 8,
        runSpacing: 8,
        children: widget.user.subjects
            .map(
              (subject) => Chip(
                label: Text(subject.name),
                avatar: const Icon(Icons.menu_book_outlined, size: 16),
              ),
            )
            .toList(growable: false),
      ),
      if (widget.user.subjects.isEmpty)
        const Text(
          'Belum ditetapkan',
          style: TextStyle(fontSize: 13, color: LmsColors.inkMuted),
        ),
      const SizedBox(height: 8),
      const Text(
        'Jawatan, sekolah, kelas dan subjek dikunci mengikut web. Hubungi pentadbir untuk perubahan rekod.',
        style: TextStyle(fontSize: 12, color: LmsColors.inkMuted),
      ),
    ],
  );
}

class _ReadOnlyInfoTile extends StatelessWidget {
  const _ReadOnlyInfoTile({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: LmsPalette.surface(context),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: LmsPalette.border(context)),
      ),
      child: Row(
        children: [
          Icon(icon, color: LmsColors.brandStrong),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 11.5,
                    color: LmsPalette.muted(context),
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: TextStyle(
                    fontSize: 14,
                    color: LmsPalette.text(context),
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ],
            ),
          ),
          Icon(
            Icons.lock_outline_rounded,
            size: 16,
            color: LmsPalette.faint(context),
          ),
        ],
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
