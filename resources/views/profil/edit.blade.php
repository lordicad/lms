<x-dynamic-component :component="auth()->user()->isTeacher() ? 'app-layout' : 'student-layout'" :title="__('Profil Saya')">
    <div class="mx-auto max-w-2xl space-y-6">
        <h1 class="text-3xl font-extrabold text-ink">{{ __('Profil Saya') }}</h1>

        <section class="card card-pad">
            <h2 class="text-xl font-extrabold text-ink">{{ __('Maklumat akaun') }}</h2>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data"
                  class="mt-6 space-y-5">
                @csrf
                @method('PATCH')

                <div class="flex items-center gap-4">
                    <x-avatar :user="$user" size="lg" />

                    <div class="flex-1">
                        <label for="avatar" class="label">{{ __('Gambar profil') }}</label>

                        <input id="avatar" name="avatar" type="file" accept="image/*"
                               class="input py-2.5 file:mr-3 file:rounded-control file:border-0 file:bg-brand-soft
                                      file:px-3 file:py-1.5 file:font-bold file:text-brand">

                        @error('avatar')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="name" class="label">{{ __('Nama penuh') }}</label>

                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}"
                           required class="input" @error('name') aria-invalid="true" @enderror>

                    @error('name')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="username" class="label">{{ __('Nama pengguna') }}</label>

                    <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}"
                           required class="input" @error('username') aria-invalid="true" @enderror>

                    @error('username')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="label">
                        {{ __('Emel') }} {{ $user->isStudent() ? __('(pilihan)') : '' }}
                    </label>

                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
                           class="input" @error('email') aria-invalid="true" @enderror>

                    @error('email')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                @if ($user->isStudent())
                    <div>
                        <label for="grade_level" class="label">{{ __('Tahun') }}</label>

                        <select id="grade_level" name="grade_level" class="input"
                                @error('grade_level') aria-invalid="true" @enderror>
                            <option value="">{{ __('Sila pilih Tahun') }}</option>
                            @foreach ($grades as $grade)
                                <option value="{{ $grade->level }}"
                                    @selected(old('grade_level', $user->grade?->level) == $grade->level)>
                                    {{ $grade->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('grade_level')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <button type="submit" class="btn-primary">{{ __('Simpan') }}</button>
            </form>
        </section>

        @if ($user->isTeacher())
            <x-youtube-connect-card :user="$user" />
        @endif

        <section class="card card-pad">
            <h2 class="text-xl font-extrabold text-ink">{{ __('Tukar kata laluan') }}</h2>

            <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="label">{{ __('Kata laluan semasa') }}</label>

                    <input id="current_password" name="current_password" type="password"
                           autocomplete="current-password" class="input">

                    @error('current_password', 'updatePassword')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="new_password" class="label">{{ __('Kata laluan baharu') }}</label>

                    <input id="new_password" name="password" type="password"
                           autocomplete="new-password" class="input">

                    @error('password', 'updatePassword')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="new_password_confirmation" class="label">{{ __('Ulang kata laluan baharu') }}</label>

                    <input id="new_password_confirmation" name="password_confirmation" type="password"
                           autocomplete="new-password" class="input">
                </div>

                <button type="submit" class="btn-primary">{{ __('Tukar Kata Laluan') }}</button>
            </form>
        </section>

        <section class="card card-pad border-danger/30">
            <h2 class="text-xl font-extrabold text-danger">{{ __('Padam akaun') }}</h2>

            <p class="mt-2 max-w-prose text-ink-2">
                {{ __('Akaun dan semua rekod anda akan dipadam sepenuhnya. Tindakan ini tidak boleh dibatalkan.') }}
            </p>

            <form method="POST" action="{{ route('profile.destroy') }}" class="mt-6 space-y-5"
                  x-data="{ confirming: false }">
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

                        @error('password', 'userDeletion')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
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
