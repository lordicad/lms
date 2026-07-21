<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        $user = new User;
        $user->role = $data['role'];
        $this->fill($user, $data);
        $user->password = Hash::make($data['password']);
        // Admin-issued: left null so the owner is asked to choose their own at first sign-in.
        $user->password_changed_at = null;
        $user->save();
        $this->syncSubjects($user, $data);

        return redirect()->route('admin.pengguna')
            ->with('status', __('Akaun :name berjaya dicipta.', ['name' => $user->name]));
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

        // Setting a password here is a reset: the admin now knows it, so the owner is asked to
        // replace it at their next sign-in, exactly as when the account was first created.
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
            $user->password_changed_at = null;
        }

        $user->save();
        $this->syncSubjects($user, $data);

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
                'regex:/^[a-zA-Z0-9._-]+$/',
                // Usernames may repeat; email is the unique identifier.
            ],
            'email' => [
                Rule::requiredIf($isTeacher),
                'nullable', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'grade_level' => [
                Rule::requiredIf(! $isTeacher),
                'nullable', 'integer', Rule::exists('grades', 'level'),
            ],
            'password' => [$creating ? 'required' : 'nullable', 'confirmed', Password::min(6)],
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
            'username.regex' => __('Nama pengguna hanya boleh mengandungi huruf, nombor, titik, garis bawah dan sengkang.'),
            'username.min' => __('Nama pengguna mesti sekurang-kurangnya 3 aksara.'),
            'email.required' => __('Guru perlu memberikan alamat emel.'),
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
