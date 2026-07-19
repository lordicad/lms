<x-dynamic-component :component="auth()->user()->isTeacher() ? 'app-layout' : 'student-layout'" :title="__('Profil Saya')">
    <div class="mx-auto max-w-3xl space-y-6">
        @if ($stats)
            {{-- ── Student profile header (WeLearn) ── --}}
            <section class="flex flex-wrap items-center gap-5 rounded-panel border border-line bg-surface p-6 shadow-card sm:gap-6 sm:p-7">
                <span class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-brand text-3xl font-extrabold text-on-brand ring-4 ring-brand-soft">
                    @if ($user->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}" alt="" class="h-full w-full rounded-full object-cover">
                    @else
                        {{ $user->initials() }}
                    @endif
                </span>

                <div class="min-w-0 flex-1">
                    <h1 class="text-2xl font-extrabold tracking-tight text-ink">{{ $user->name }}</h1>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                        @if ($user->grade)
                            <span class="chip bg-brand-soft text-brand">{{ $user->grade->name }}</span>
                        @endif
                        @if ($user->email)
                            <span class="text-[13px] text-ink-2">{{ $user->email }}</span>
                        @endif
                    </div>
                </div>

                <a href="#akaun" class="btn-secondary btn-sm shrink-0">
                    <x-icon name="pencil" class="h-4 w-4" />
                    {{ __('Sunting') }}
                </a>
            </section>

            {{-- Stats --}}
            <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-4">
                <div class="rounded-panel bg-success-soft p-4">
                    <p class="text-xl">⭐</p>
                    <p class="mt-1 text-[22px] font-extrabold text-success">{{ number_format($stats['points']) }}</p>
                    <p class="text-[12.5px] font-bold text-success">{{ __('Jumlah mata') }}</p>
                </div>
                <div class="rounded-panel bg-warn-soft p-4">
                    <p class="text-xl">📝</p>
                    <p class="mt-1 text-[22px] font-extrabold text-warn">{{ $stats['quizzes'] }}</p>
                    <p class="text-[12.5px] font-bold text-warn">{{ __('Kuiz selesai') }}</p>
                </div>
                <div class="rounded-panel p-4" style="background:rgb(var(--c-brand-soft))">
                    <p class="text-xl">🎬</p>
                    <p class="mt-1 text-[22px] font-extrabold text-brand">{{ $stats['videos'] }}</p>
                    <p class="text-[12.5px] font-bold text-brand">{{ __('Video ditonton') }}</p>
                </div>
                <div class="rounded-panel bg-danger-soft p-4">
                    <p class="text-xl">🏆</p>
                    <p class="mt-1 text-[22px] font-extrabold text-danger">{{ $stats['rank'] ? '#'.$stats['rank'] : '—' }}</p>
                    <p class="text-[12.5px] font-bold text-danger">{{ __('Ranking') }}</p>
                </div>
            </div>

            {{-- Badges — earned only when the real metric is met --}}
            @php($badges = [
                ['icon' => '🔥', 'name' => __('Rajin Belajar'), 'desc' => __('5 kuiz selesai'), 'got' => $stats['quizzes'] >= 5],
                ['icon' => '🎯', 'name' => __('Markah Penuh'), 'desc' => __('100% dalam kuiz'), 'got' => $stats['perfect']],
                ['icon' => '🎬', 'name' => __('Penonton Setia'), 'desc' => __('25 video ditonton'), 'got' => $stats['videos'] >= 25],
                ['icon' => '🚀', 'name' => __('Top 10'), 'desc' => __('Capai ranking top 10'), 'got' => $stats['rank'] && $stats['rank'] <= 10],
            ])
            <section>
                <h2 class="mb-3 text-[17px] font-extrabold text-ink">{{ __('Lencana Saya') }}</h2>
                <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-4">
                    @foreach ($badges as $b)
                        <div class="flex flex-col items-center gap-1.5 rounded-panel border border-line bg-surface p-4 text-center shadow-card {{ $b['got'] ? '' : 'opacity-60' }}">
                            <span class="text-3xl" style="{{ $b['got'] ? '' : 'filter:grayscale(1)' }}">{{ $b['icon'] }}</span>
                            <span class="text-[13.5px] font-extrabold text-ink">{{ $b['name'] }}</span>
                            <span class="text-[11.5px] text-ink-2">{{ $b['desc'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @else
            <h1 class="text-3xl font-extrabold text-ink">{{ __('Profil Saya') }}</h1>
        @endif

        {{-- ── Account info ── --}}
        <section id="akaun" class="card card-pad scroll-mt-24">
            <h2 class="text-xl font-extrabold text-ink">{{ __('Maklumat akaun') }}</h2>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                @csrf
                @method('PATCH')

                <div class="flex items-center gap-4">
                    <x-avatar :user="$user" size="lg" />

                    <div class="flex-1">
                        <label for="avatar" class="label">{{ __('Gambar profil') }}</label>
                        <input id="avatar" name="avatar" type="file" accept="image/*"
                               class="input py-2.5 file:mr-3 file:rounded-control file:border-0 file:bg-brand-soft file:px-3 file:py-1.5 file:font-bold file:text-brand">
                        @error('avatar')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="name" class="label">{{ __('Nama penuh') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="input" @error('name') aria-invalid="true" @enderror>
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="username" class="label">{{ __('Nama pengguna') }}</label>
                    <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" required class="input" @error('username') aria-invalid="true" @enderror>
                    @error('username')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="label">{{ __('Emel') }} {{ $user->isStudent() ? __('(pilihan)') : '' }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="input" @error('email') aria-invalid="true" @enderror>
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                @if ($user->isStudent())
                    <div>
                        <label for="grade_level" class="label">{{ __('Tahun') }}</label>
                        <select id="grade_level" name="grade_level" class="input" @error('grade_level') aria-invalid="true" @enderror>
                            <option value="">{{ __('Sila pilih Tahun') }}</option>
                            @foreach ($grades as $grade)
                                <option value="{{ $grade->level }}" @selected(old('grade_level', $user->grade?->level) == $grade->level)>{{ $grade->name }}</option>
                            @endforeach
                        </select>
                        @error('grade_level')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                @endif

                <button type="submit" class="btn-primary">{{ __('Simpan') }}</button>
            </form>
        </section>

        @if ($user->isTeacher())
            <x-youtube-connect-card :user="$user" />
        @endif

        {{-- ── Change password ── --}}
        <section class="card card-pad">
            <h2 class="text-xl font-extrabold text-ink">{{ __('Tukar kata laluan') }}</h2>

            <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="label">{{ __('Kata laluan semasa') }}</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="input">
                    @error('current_password', 'updatePassword')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="new_password" class="label">{{ __('Kata laluan baharu') }}</label>
                    <input id="new_password" name="password" type="password" autocomplete="new-password" class="input">
                    @error('password', 'updatePassword')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="new_password_confirmation" class="label">{{ __('Ulang kata laluan baharu') }}</label>
                    <input id="new_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="input">
                </div>

                <button type="submit" class="btn-primary">{{ __('Tukar Kata Laluan') }}</button>
            </form>
        </section>

        {{-- ── Delete account ── --}}
        <section class="card card-pad border-danger/30">
            <h2 class="text-xl font-extrabold text-danger">{{ __('Padam akaun') }}</h2>

            <p class="mt-2 max-w-prose text-ink-2">
                {{ __('Akaun dan semua rekod anda akan dipadam sepenuhnya. Tindakan ini tidak boleh dibatalkan.') }}
            </p>

            <form method="POST" action="{{ route('profile.destroy') }}" class="mt-6 space-y-5" x-data="{ confirming: false }">
                @csrf
                @method('DELETE')

                <button type="button" class="btn-danger" x-show="! confirming" @click="confirming = true">
                    <x-icon name="trash" class="h-5 w-5" />
                    {{ __('Padam Akaun Saya') }}
                </button>

                <div x-show="confirming" x-cloak class="space-y-4">
                    <div>
                        <label for="delete_password" class="label">{{ __('Masukkan kata laluan untuk mengesahkan') }}</label>
                        <input id="delete_password" name="password" type="password" class="input">
                        @error('password', 'userDeletion')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="btn-danger">{{ __('Ya, padam akaun saya') }}</button>
                        <button type="button" class="btn-secondary" @click="confirming = false">{{ __('Batal') }}</button>
                    </div>
                </div>
            </form>
        </section>
    </div>
</x-dynamic-component>
