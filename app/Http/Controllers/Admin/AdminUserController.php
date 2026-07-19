<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
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
        return view('admin.pengguna-form', [
            'user' => new User(['role' => User::ROLE_TEACHER, 'is_active' => true]),
            'grades' => Grade::orderBy('level')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);

        $user = new User;
        $user->role = $data['role'];
        $this->fill($user, $data);
        $user->password = Hash::make($data['password']);
        $user->save();

        return redirect()->route('admin.pengguna')
            ->with('status', __('Akaun :name berjaya dicipta.', ['name' => $user->name]));
    }

    public function edit(User $user): View
    {
        $this->ensureManaged($user);

        return view('admin.pengguna-form', [
            'user' => $user,
            'grades' => Grade::orderBy('level')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureManaged($user);
        $data = $this->validated($request, $user);

        $this->fill($user, $data);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

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
                Rule::unique('users', 'username')->ignore($user?->id),
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
    }
}
