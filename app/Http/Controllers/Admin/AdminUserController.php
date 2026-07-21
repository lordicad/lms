<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccountCredentialsMail;
use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Support\TemporaryPassword;
use App\Support\WhatsAppLink;
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
        $role = in_array($request->query('role'), self::MANAGED_ROLES, true) ? $request->query('role') : null;
        $status = in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : null;
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
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
                'teacher' => User::where('role', User::ROLE_TEACHER)->count(),
                'student' => User::where('role', User::ROLE_STUDENT)->count(),
                'inactive' => User::whereIn('role', self::MANAGED_ROLES)->where('is_active', false)->count(),
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
     * A teacher gets them at their own address. A student's go to the guardian: by email when there
     * is one, and by WhatsApp when there is a phone number — the WhatsApp part comes back as a
     * click-to-send link for the admin to press, since sending server-side needs a paid API account.
     *
     * Used for a new account and for an admin password reset alike: both end with the admin holding
     * a password the owner does not yet know, so both need the same hand-off.
     *
     * @return array<string, string> session keys to flash
     */
    private function deliverCredentials(User $user, string $plainPassword, bool $isReset = false): array
    {
        $sentTo = [];

        // The password is deliberately never put on screen: it reaches its owner by email, or the
        // guardian by WhatsApp, and nowhere else.
        $flash = [];

        if ($user->isTeacher()) {
            if ($this->mail($user->email, $user, $plainPassword)) {
                $sentTo[] = $user->email;
            }
        } else {
            if ($this->mail($user->guardian_email, $user, $plainPassword, $user->guardian_name)) {
                $sentTo[] = $user->guardian_email;
            }

            if ($link = WhatsAppLink::for($user, $plainPassword, $user->guardian_name)) {
                $flash['wa_link'] = $link;
                $flash['wa_name'] = $user->name;
            }
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
        return [
            'grades' => Grade::orderBy('level')->get(),
            'schools' => School::orderBy('name')->get(),
            'subjects' => Subject::orderBy('sort_order')->get(),
            'allClasses' => SchoolClass::active()->with('grade')->orderBy('grade_id')->orderBy('name')
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
            'password' => [
                $creating && ! $request->boolean('auto_password') ? 'required' : 'nullable',
                'confirmed', Password::min(6),
            ],
            'is_active' => ['nullable', 'boolean'],

            // Shared + role-specific profile fields, so the admin form stays coherent with the schema.
            'school_id' => ['nullable', 'integer', Rule::exists('schools', 'id')],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{6,20}$/'],
            'position' => ['nullable', 'string', 'max:100'],
            'subjects' => ['nullable', 'array'],
            'subjects.*' => ['integer', Rule::exists('subjects', 'id')],
            'school_class_id' => [
                'nullable', 'integer',
                function (string $attr, mixed $value, \Closure $fail) use ($request, $isTeacher) {
                    $class = SchoolClass::find($value);
                    if (! $class) {
                        $fail(__('Kelas tidak sah.'));

                        return;
                    }
                    if ((int) $class->school_id !== (int) $request->input('school_id')) {
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
            'guardian_phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{6,20}$/'],
            'guardian_email' => ['nullable', 'string', 'email', 'max:255'],
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
            'password.required' => __('Sila tetapkan kata laluan.'),
            'password.confirmed' => __('Pengesahan kata laluan tidak sepadan.'),
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
        $user->school_id = $data['school_id'] ?? null;

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
