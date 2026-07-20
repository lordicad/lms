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

  ProfileOptions? _options;
  Object? _optionsError;
  int? _schoolId;
  int? _gradeLevel;
  int? _schoolClassId;
  int? _homeroomClassId;
  late Set<int> _subjectIds;
  var _loadingOptions = true;
  var _saving = false;
  var _uploadingAvatar = false;
  late AuthUser _currentUser;

  bool get _isStudent => widget.user.role == UserRole.student;
  bool get _isTeacher => widget.user.role == UserRole.teacher;
  bool get _emailRequired => _isTeacher;

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
    _schoolId = widget.user.school?.id;
    _gradeLevel = widget.user.grade?.level;
    _schoolClassId = widget.user.schoolClass?.id;
    _homeroomClassId = widget.user.homeroomClass?.id;
    _subjectIds = widget.user.subjects.map((subject) => subject.id).toSet();
    _currentUser = widget.user;
    unawaited(_loadOptions());
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

  Future<void> _loadOptions() async {
    setState(() {
      _loadingOptions = true;
      _optionsError = null;
    });
    try {
      final options = await widget.loadOptions();
      if (!mounted) return;
      setState(() {
        _options = options;
        _loadingOptions = false;
        _normaliseSelections();
      });
    } catch (error) {
      if (mounted) {
        setState(() {
          _loadingOptions = false;
          _optionsError = error;
        });
      }
    }
  }

  int? get _selectedGradeId {
    final level = _gradeLevel;
    if (level == null) return null;
    for (final grade in _options?.grades ?? const <Grade>[]) {
      if (grade.level == level) return grade.id;
    }
    return null;
  }

  List<SchoolClassInfo> get _studentClasses {
    final schoolId = _schoolId;
    final gradeId = _selectedGradeId;
    if (schoolId == null || gradeId == null) return const [];
    return (_options?.classes ?? const [])
        .where((item) => item.schoolId == schoolId && item.gradeId == gradeId)
        .toList(growable: false);
  }

  List<SchoolClassInfo> get _teacherClasses {
    final schoolId = _schoolId;
    if (schoolId == null) return const [];
    return (_options?.classes ?? const [])
        .where((item) => item.schoolId == schoolId)
        .toList(growable: false);
  }

  void _normaliseSelections() {
    if (_isStudent &&
        !_studentClasses.any((item) => item.id == _schoolClassId)) {
      _schoolClassId = null;
    }
    if (_isTeacher &&
        !_teacherClasses.any((item) => item.id == _homeroomClassId)) {
      _homeroomClassId = null;
    }
  }

  String? _clean(TextEditingController controller) {
    final value = controller.text.trim();
    return value.isEmpty ? null : value;
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate() || _options == null) return;
    if (_isStudent && _gradeLevel == null) return;

    setState(() => _saving = true);
    try {
      final updated = await widget.onSave(
        ProfileUpdate(
          name: _name.text.trim(),
          username: _username.text.trim(),
          email: _clean(_email),
          schoolId: _schoolId,
          gradeLevel: _gradeLevel,
          schoolClassId: _schoolClassId,
          guardianName: _clean(_guardianName),
          guardianPhone: _clean(_guardianPhone),
          guardianEmail: _clean(_guardianEmail),
          phone: _clean(_phone),
          position: _clean(_position),
          subjectIds: _subjectIds.toList(growable: false),
          homeroomClassId: _homeroomClassId,
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
                onPressed: _saving || _loadingOptions || _optionsError != null
                    ? null
                    : _save,
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
        decoration: const InputDecoration(
          labelText: 'Nama penuh',
          prefixIcon: Icon(Icons.person_outline_rounded),
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
    ],
  );

  Widget _profileFields() {
    if (_loadingOptions) {
      return const Padding(
        padding: EdgeInsets.all(16),
        child: Center(child: CircularProgressIndicator()),
      );
    }
    if (_optionsError != null) {
      return OutlinedButton.icon(
        onPressed: _loadOptions,
        icon: const Icon(Icons.refresh_rounded),
        label: Text('Muat semula pilihan profil: $_optionsError'),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Maklumat sekolah',
          style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
        ),
        const SizedBox(height: 12),
        _schoolDropdown(),
        if (_isStudent) ..._studentFields(),
        if (_isTeacher) ..._teacherFields(),
      ],
    );
  }

  Widget _schoolDropdown() => DropdownButtonFormField<int?>(
    key: ValueKey('school-$_schoolId'),
    initialValue: _schoolId,
    isExpanded: true,
    decoration: const InputDecoration(
      labelText: 'Sekolah',
      prefixIcon: Icon(Icons.account_balance_outlined),
    ),
    items: [
      const DropdownMenuItem<int?>(
        value: null,
        child: Text('Belum ditetapkan'),
      ),
      ...(_options?.schools ?? const []).map(
        (school) => DropdownMenuItem<int?>(
          value: school.id,
          child: Text(school.name, overflow: TextOverflow.ellipsis),
        ),
      ),
    ],
    onChanged: (value) => setState(() {
      _schoolId = value;
      _normaliseSelections();
    }),
  );

  List<Widget> _studentFields() => [
    const SizedBox(height: 16),
    DropdownButtonFormField<int>(
      key: ValueKey('grade-$_gradeLevel'),
      initialValue: _gradeLevel,
      isExpanded: true,
      decoration: const InputDecoration(
        labelText: 'Tahun',
        prefixIcon: Icon(Icons.school_outlined),
      ),
      items: (_options?.grades ?? const [])
          .map(
            (grade) => DropdownMenuItem<int>(
              value: grade.level,
              child: Text(grade.name),
            ),
          )
          .toList(growable: false),
      onChanged: (value) => setState(() {
        _gradeLevel = value;
        _normaliseSelections();
      }),
      validator: (value) => value == null ? 'Sila pilih Tahun anda.' : null,
    ),
    const SizedBox(height: 16),
    DropdownButtonFormField<int?>(
      key: ValueKey('student-class-$_schoolClassId-$_schoolId-$_gradeLevel'),
      initialValue: _schoolClassId,
      isExpanded: true,
      decoration: const InputDecoration(
        labelText: 'Kelas',
        prefixIcon: Icon(Icons.groups_outlined),
      ),
      items: [
        const DropdownMenuItem<int?>(
          value: null,
          child: Text('Belum ditetapkan'),
        ),
        ..._studentClasses.map(
          (schoolClass) => DropdownMenuItem<int?>(
            value: schoolClass.id,
            child: Text(schoolClass.label, overflow: TextOverflow.ellipsis),
          ),
        ),
      ],
      onChanged: _schoolId == null || _gradeLevel == null
          ? null
          : (value) => setState(() => _schoolClassId = value),
    ),
    if (_schoolId == null || _gradeLevel == null)
      const Padding(
        padding: EdgeInsets.only(top: 7),
        child: Text(
          'Pilih sekolah dan Tahun dahulu untuk melihat kelas.',
          style: TextStyle(fontSize: 12, color: LmsColors.inkMuted),
        ),
      ),
    const SizedBox(height: 22),
    const Text(
      'Maklumat penjaga',
      style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
    ),
    const SizedBox(height: 12),
    TextFormField(
      controller: _guardianName,
      textCapitalization: TextCapitalization.words,
      decoration: const InputDecoration(
        labelText: 'Nama penjaga',
        prefixIcon: Icon(Icons.family_restroom_outlined),
      ),
    ),
    const SizedBox(height: 16),
    TextFormField(
      controller: _guardianPhone,
      keyboardType: TextInputType.phone,
      decoration: const InputDecoration(
        labelText: 'Nombor telefon penjaga',
        prefixIcon: Icon(Icons.phone_outlined),
      ),
    ),
    const SizedBox(height: 16),
    TextFormField(
      controller: _guardianEmail,
      keyboardType: TextInputType.emailAddress,
      decoration: const InputDecoration(
        labelText: 'E-mel penjaga',
        prefixIcon: Icon(Icons.mail_outline_rounded),
      ),
    ),
  ];

  List<Widget> _teacherFields() => [
    const SizedBox(height: 16),
    TextFormField(
      controller: _position,
      textCapitalization: TextCapitalization.words,
      decoration: const InputDecoration(
        labelText: 'Jawatan',
        prefixIcon: Icon(Icons.work_outline_rounded),
      ),
    ),
    const SizedBox(height: 16),
    TextFormField(
      controller: _phone,
      keyboardType: TextInputType.phone,
      decoration: const InputDecoration(
        labelText: 'Nombor telefon',
        prefixIcon: Icon(Icons.phone_outlined),
      ),
    ),
    const SizedBox(height: 22),
    const Text(
      'Subjek diajar',
      style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
    ),
    const SizedBox(height: 9),
    Wrap(
      spacing: 8,
      runSpacing: 8,
      children: (_options?.subjects ?? const [])
          .map(
            (subject) => FilterChip(
              label: Text(subject.name),
              selected: _subjectIds.contains(subject.id),
              onSelected: (selected) => setState(() {
                if (selected) {
                  _subjectIds.add(subject.id);
                } else {
                  _subjectIds.remove(subject.id);
                }
              }),
            ),
          )
          .toList(growable: false),
    ),
    const SizedBox(height: 22),
    DropdownButtonFormField<int?>(
      key: ValueKey('homeroom-$_homeroomClassId-$_schoolId'),
      initialValue: _homeroomClassId,
      isExpanded: true,
      decoration: const InputDecoration(
        labelText: 'Guru kelas',
        prefixIcon: Icon(Icons.class_outlined),
      ),
      items: [
        const DropdownMenuItem<int?>(
          value: null,
          child: Text('Belum ditetapkan'),
        ),
        ..._teacherClasses.map(
          (schoolClass) => DropdownMenuItem<int?>(
            value: schoolClass.id,
            child: Text(schoolClass.label, overflow: TextOverflow.ellipsis),
          ),
        ),
      ],
      onChanged: _schoolId == null
          ? null
          : (value) => setState(() => _homeroomClassId = value),
    ),
  ];
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
