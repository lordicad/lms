<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccountCredentialsMail;
use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Support\SchoolScope;
use App\Support\TemporaryPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Admin CRUD for teacher and student accounts. Admin accounts are deliberately out of
 * scope here — they are never listed, edited or deleted from this screen, so an admin can
 * neither lock themselves out nor tamper with a peer.
 */
class AdminUserController extends Controller
{
    private const MANAGED_ROLES = [User::ROLE_TEACHER, User::ROLE_STUDENT];

    public function index(Request $request): View
    {
        // No `role` in the query string at all means a first visit, which opens on Teacher: the
        // fourth column carries a different field per role, so a mixed list has to blank it out for
        // half the rows. Picking "All roles" sends an empty value, which is honoured as "no filter".
        $role = $request->has('role')
            ? (in_array($request->query('role'), self::MANAGED_ROLES, true) ? $request->query('role') : null)
            : User::ROLE_TEACHER;
        $status = in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : null;
        $search = trim((string) $request->query('q', ''));

        $users = SchoolScope::users(User::query())
            ->whereIn('role', self::MANAGED_ROLES)
            ->with('grade')
            ->when($role, fn ($q) => $q->where('role', $role))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pengguna', [
            'users' => $users,
            'role' => $role,
            'status' => $status,
            'search' => $search,
            'counts' => [
                'teacher' => SchoolScope::users(User::where('role', User::ROLE_TEACHER))->count(),
                'student' => SchoolScope::users(User::where('role', User::ROLE_STUDENT))->count(),
                'inactive' => SchoolScope::users(User::whereIn('role', self::MANAGED_ROLES))->where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.pengguna-form', array_merge([
            'user' => new User(['role' => User::ROLE_TEACHER, 'is_active' => true]),
        ], $this->formLists()));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);

        // Blank or auto_password: generate one rather than making the admin invent it.
        $plainPassword = empty($data['password'])
            ? TemporaryPassword::generate()
            : $data['password'];

        $user = new User;
        $user->role = $data['role'];
        $this->fill($user, $data);
        $user->password = Hash::make($plainPassword);
        // Admin-issued: left null so the owner is asked to choose their own at first sign-in.
        $user->password_changed_at = null;
        $user->save();
        $this->syncSubjects($user, $data);

        // The only moment the password exists in plain text — hand it over now or never.
        return redirect()->route('admin.pengguna')
            ->with($this->deliverCredentials($user, $plainPassword));
    }

    /**
     * Send the new sign-in details out, and describe what happened for the flash message.
     *
     * A teacher gets them at their own address; a student's go to their guardian's. Email is the
     * only channel: it is the one that delivers by itself at no cost. (WhatsApp would need either a
     * paid Business API or a human pressing send — see App\Support\WhatsAppLink, currently parked.)
     *
     * Used for a new account and for an admin password reset alike: both end with the admin holding
     * a password the owner does not yet know, so both need the same hand-off.
     *
     * @return array<string, string> session keys to flash
     */
    private function deliverCredentials(User $user, string $plainPassword, bool $isReset = false): array
    {
        $sentTo = [];

        // The password is deliberately never put on screen: it reaches its owner by email and
        // nowhere else.
        $flash = [];

        if ($user->isTeacher()) {
            if ($this->mail($user->email, $user, $plainPassword)) {
                $sentTo[] = $user->email;
            }
        } elseif ($this->mail($user->guardian_email, $user, $plainPassword, $user->guardian_name)) {
            $sentTo[] = $user->guardian_email;
        }

        if ($isReset) {
            $flash['status'] = $sentTo === []
                ? __('Kata laluan :name berjaya ditetapkan semula. Butiran belum dihantar — tiada alamat e-mel disimpan.', ['name' => $user->name])
                : __('Kata laluan :name berjaya ditetapkan semula. Butiran log masuk baharu dihantar ke :to.', [
                    'name' => $user->name,
                    'to' => implode(', ', $sentTo),
                ]);
        } else {
            $flash['status'] = $sentTo === []
                ? __('Akaun :name berjaya dicipta. Butiran log masuk belum dihantar — tiada alamat e-mel disimpan.', ['name' => $user->name])
                : __('Akaun :name berjaya dicipta. Butiran log masuk dihantar ke :to.', [
                    'name' => $user->name,
                    'to' => implode(', ', $sentTo),
                ]);
        }

        return $flash;
    }

    /**
     * Mail the credentials, reporting whether it went. A mail server that is down or misconfigured
     * must not undo an account that is already saved, so a failure is logged and swallowed.
     */
    private function mail(?string $address, User $user, string $plainPassword, ?string $guardianName = null): bool
    {
        if (! $address) {
            return false;
        }

        try {
            Mail::to($address)->send(new AccountCredentialsMail($user, $plainPassword, $guardianName));

            return true;
        } catch (\Throwable $e) {
            Log::error('Could not send account credentials', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function edit(User $user): View
    {
        $this->ensureManaged($user);
        $user->load('subjects');

        return view('admin.pengguna-form', array_merge([
            'user' => $user,
        ], $this->formLists()));
    }

    /**
     * Shared reference lists for the create/edit form.
     *
     * @return array<string, mixed>
     */
    private function formLists(): array
    {
        // Only the admin's own school and its classes are offered. The save forces the school
        // regardless, so this keeps the form honest about the one choice actually available.
        $schoolId = SchoolScope::currentSchoolId();

        return [
            'grades' => Grade::orderBy('level')->get(),
            'schools' => School::when($schoolId, fn ($q) => $q->where('id', $schoolId))->orderBy('name')->get(),
            'subjects' => Subject::orderBy('sort_order')->get(),
            'allClasses' => SchoolClass::active()->with('grade')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->orderBy('grade_id')->orderBy('name')
                ->get()
                ->map(fn (SchoolClass $c) => [
                    'id' => $c->id,
                    'school_id' => $c->school_id,
                    'grade_id' => $c->grade_id,
                    'label' => $c->label(),
                ])->values(),
        ];
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureManaged($user);
        $data = $this->validated($request, $user);

        $this->fill($user, $data);

        // Blank password on edit means "leave it alone" — unless auto_password asks for a fresh one.
        $plainPassword = ($data['password'] ?? null)
            ?: (($data['auto_password'] ?? false) ? TemporaryPassword::generate() : null);

        // Setting a password here is a reset: the admin now knows it, so the owner is asked to
        // replace it at their next sign-in, exactly as when the account was first created.
        if ($plainPassword) {
            $user->password = Hash::make($plainPassword);
            $user->password_changed_at = null;
        }

        $user->save();
        $this->syncSubjects($user, $data);

        // A reset leaves the admin holding a password the owner does not know yet, so it is handed
        // over the same way a brand new account is. An edit that left the password alone is silent.
        if ($plainPassword) {
            return redirect()->route('admin.pengguna')
                ->with($this->deliverCredentials($user, $plainPassword, isReset: true));
        }

        return redirect()->route('admin.pengguna')
            ->with('status', __('Akaun :name berjaya dikemas kini.', ['name' => $user->name]));
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->ensureManaged($user);
        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('status', $user->is_active
            ? __('Akaun :name diaktifkan.', ['name' => $user->name])
            : __('Akaun :name dinyahaktifkan.', ['name' => $user->name]));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->ensureManaged($user);

        $name = $user->name;

        if ($user->avatar) {
            Storage::disk('uploads')->delete($user->avatar);
        }

        $user->delete();   // teacher content / student activity cascade via FKs

        return redirect()->route('admin.pengguna')
            ->with('status', __('Akaun :name berjaya dipadam.', ['name' => $name]));
    }

    /** Only teacher/student rows may be touched here; anything else 404s. */
    private function ensureManaged(User $user): void
    {
        abort_unless(in_array($user->role, self::MANAGED_ROLES, true), 404);

        // An admin oversees one school, so a record from another one does not exist as far as they
        // are concerned. 404 rather than 403: it does not confirm the account is there at all.
        abort_unless(SchoolScope::allows($user->school_id), 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $user): array
    {
        $creating = $user === null;
        // On create the role comes from the form; on edit it is fixed to the row's role.
        $isTeacher = $creating ? $request->input('role') === User::ROLE_TEACHER : $user->isTeacher();

        return $request->validate([
            'role' => [$creating ? 'required' : 'nullable', Rule::in(self::MANAGED_ROLES)],
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required', 'string', 'min:3', 'max:30',
                // A student types this to sign in, so it stays strict. A teacher signs in with
                // their email, making theirs a display nickname only — "Cikgu Ana" should be
                // allowed. Usernames may repeat either way; email is the unique identifier.
                $isTeacher ? 'regex:/^[\pL\pN ._-]+$/u' : 'regex:/^[a-zA-Z0-9._-]+$/',
            ],
            // Everyone signs in with their email, so every account needs one — students included.
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'grade_level' => [
                Rule::requiredIf(! $isTeacher),
                'nullable', 'integer', Rule::exists('grades', 'level'),
            ],
            // Typing a password is optional: with auto_password on, one is generated instead.
            'auto_password' => ['nullable', 'boolean'],
            // No 'confirmed' rule: the form has a single password box. Auto-generate is the normal
            // path, and a mistyped one is fixed by resetting, which re-sends the details anyway.
            'password' => [
                $creating && ! $request->boolean('auto_password') ? 'required' : 'nullable',
                Password::min(6),
            ],
            'is_active' => ['nullable', 'boolean'],

            // Shared + role-specific profile fields, so the admin form stays coherent with the schema.
            'school_id' => ['nullable', 'integer', Rule::exists('schools', 'id')],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{6,20}$/'],
            'position' => ['nullable', 'string', 'max:100'],
            'subjects' => ['nullable', 'array'],
            'subjects.*' => ['integer', Rule::exists('subjects', 'id')],
            // Required for students: a student without a class also has no homeroom teacher, and
            // nothing else in the app can give them one — the profiles cannot set it and the only
            // other filler is ProfileBackfillSeeder.
            'school_class_id' => [
                Rule::requiredIf(! $isTeacher),
                'nullable', 'integer',
                function (string $attr, mixed $value, \Closure $fail) use ($request, $isTeacher) {
                    $class = SchoolClass::find($value);
                    if (! $class) {
                        $fail(__('Kelas tidak sah.'));

                        return;
                    }
                    // The school the account will actually get: a school-scoped admin's own school
                    // overrides whatever the form posted (see fill()). Validating against the
                    // posted value instead would reject a class that is in their school.
                    $schoolId = SchoolScope::currentSchoolId() ?? $request->input('school_id');

                    if ((int) $class->school_id !== (int) $schoolId) {
                        $fail(__('Kelas ini bukan di sekolah yang dipilih.'));

                        return;
                    }
                    if (! $isTeacher) {
                        $gradeId = Grade::where('level', $request->integer('grade_level'))->value('id');
                        if ((int) $class->grade_id !== (int) $gradeId) {
                            $fail(__('Kelas ini tidak sepadan dengan tahun murid.'));
                        }
                    }
                },
            ],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            // Kept as contact details only — nothing is sent here.
            'guardian_phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{6,20}$/'],
            // The single delivery address for a student's sign-in details, so it has to be there:
            // without it the password is generated, sent nowhere, and lost for good.
            'guardian_email' => [
                Rule::requiredIf(fn () => ! $isTeacher),
                'nullable', 'string', 'email', 'max:255',
            ],
        ], [
            'name.required' => __('Sila isi nama penuh.'),
            'username.required' => __('Sila pilih nama pengguna.'),
            'username.unique' => __('Nama pengguna ini sudah diambil.'),
            'username.regex' => $isTeacher
                ? __('Nama pengguna hanya boleh mengandungi huruf, nombor, ruang, titik, garis bawah dan sengkang.')
                : __('Nama pengguna hanya boleh mengandungi huruf, nombor, titik, garis bawah dan sengkang.'),
            'username.min' => __('Nama pengguna mesti sekurang-kurangnya 3 aksara.'),
            'email.required' => __('Emel diperlukan — ia digunakan untuk log masuk.'),
            'email.unique' => __('Emel ini sudah didaftarkan.'),
            'grade_level.required' => __('Sila pilih Tahun untuk murid.'),
            'school_class_id.required' => __('Sila pilih kelas untuk murid. Pilih sekolah dan tahun dahulu jika senarai kosong.'),
            'password.required' => __('Sila tetapkan kata laluan.'),
            'guardian_email.required' => __('Sila isi e-mel penjaga — butiran log masuk murid dihantar ke alamat ini.'),
        ]);
    }

    /** @param array<string, mixed> $data */
    private function fill(User $user, array $data): void
    {
        $isTeacher = $user->role === User::ROLE_TEACHER;

        $user->name = $data['name'];
        $user->username = $data['username'];
        $user->email = $isTeacher ? $data['email'] : ($data['email'] ?? null);
        $user->grade_id = $isTeacher ? null : Grade::where('level', $data['grade_level'])->value('id');
        $user->is_active = (bool) ($data['is_active'] ?? false);
        // Forced, not taken from the form: an admin creates accounts for their own school only.
        $user->school_id = SchoolScope::currentSchoolId() ?? ($data['school_id'] ?? null);

        if ($isTeacher) {
            $user->phone = $data['phone'] ?? null;
            $user->position = $data['position'] ?? null;
            $user->school_class_id = null;
            $user->guardian_name = null;
            $user->guardian_phone = null;
            $user->guardian_email = null;
        } else {
            $user->school_class_id = $data['school_class_id'] ?? null;
            $user->guardian_name = $data['guardian_name'] ?? null;
            $user->guardian_phone = $data['guardian_phone'] ?? null;
            $user->guardian_email = $data['guardian_email'] ?? null;
            $user->phone = null;
            $user->position = null;
        }
    }

    /** Sync the teacher-subjects pivot after the row is saved. */
    private function syncSubjects(User $user, array $data): void
    {
        if ($user->role === User::ROLE_TEACHER) {
            $user->subjects()->sync($data['subjects'] ?? []);
        }
    }
}
