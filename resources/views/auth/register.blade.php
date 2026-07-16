<x-guest-layout :title="__('Daftar Akaun')">
    {{-- One form, two roles. Ticking "Saya seorang guru" reveals the teacher-only fields;
         Alpine keeps that state client-side, and the server re-checks everything anyway. --}}
    <div class="card card-pad" x-data="{ isTeacher: {{ old('is_teacher') ? 'true' : 'false' }} }">
        <h1 class="text-2xl font-extrabold text-ink">{{ __('Daftar Akaun') }}</h1>
        <p class="mt-2 text-ink-2">{{ __('Murid boleh daftar sendiri. Cikgu perlukan kod daripada sekolah.') }}</p>

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <label for="name" class="label">{{ __('Nama penuh') }}</label>

                <input id="name" name="name" type="text" value="{{ old('name') }}"
                       required autofocus autocomplete="name" class="input"
                       @error('name') aria-invalid="true" @enderror>

                @error('name')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="username" class="label">{{ __('Nama pengguna') }}</label>

                <input id="username" name="username" type="text" value="{{ old('username') }}"
                       required autocomplete="username" class="input" aria-describedby="username-help"
                       @error('username') aria-invalid="true" @enderror>

                <p id="username-help" class="help">{{ __('Untuk log masuk. Contoh: aisyah.t3') }}</p>

                @error('username')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Role toggle --}}
            <div class="rounded-card border border-line bg-surface-2 p-4">
                <label for="is_teacher" class="flex items-start gap-3">
                    <input id="is_teacher" name="is_teacher" type="checkbox" value="1" x-model="isTeacher"
                           class="mt-0.5 h-5 w-5 rounded border-line text-brand focus:ring-brand">

                    <span>
                        <span class="block font-bold text-ink">{{ __('Saya seorang guru') }}</span>
                        <span class="block text-sm text-ink-2">{{ __('Tandakan jika anda mendaftar sebagai cikgu.') }}</span>
                    </span>
                </label>
            </div>

            {{-- Student-only --}}
            <div x-show="! isTeacher" x-cloak>
                <label for="grade_level" class="label">{{ __('Tahun anda') }}</label>

                <select id="grade_level" name="grade_level" class="input"
                        @error('grade_level') aria-invalid="true" @enderror>
                    <option value="">{{ __('Sila pilih Tahun') }}</option>
                    @foreach ($grades as $grade)
                        <option value="{{ $grade->level }}" @selected(old('grade_level') == $grade->level)>
                            {{ $grade->name }}
                        </option>
                    @endforeach
                </select>

                @error('grade_level')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Teacher-only --}}
            <div x-show="isTeacher" x-cloak class="space-y-5">
                <div>
                    <label for="email" class="label">{{ __('Emel') }}</label>

                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                           autocomplete="email" class="input" @error('email') aria-invalid="true" @enderror>

                    @error('email')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="teacher_code" class="label">{{ __('Kod Pendaftaran Guru') }}</label>

                    <input id="teacher_code" name="teacher_code" type="text" value="{{ old('teacher_code') }}"
                           class="input" aria-describedby="teacher-code-help"
                           @error('teacher_code') aria-invalid="true" @enderror>

                    <p id="teacher-code-help" class="help">{{ __('Dapatkan kod ini daripada pentadbir sekolah anda.') }}</p>

                    @error('teacher_code')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="password" class="label">{{ __('Kata laluan') }}</label>

                <input id="password" name="password" type="password" required autocomplete="new-password"
                       class="input" aria-describedby="password-help"
                       @error('password') aria-invalid="true" @enderror>

                <p id="password-help" class="help">{{ __('Sekurang-kurangnya 6 aksara.') }}</p>

                @error('password')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="label">{{ __('Ulang kata laluan') }}</label>

                <input id="password_confirmation" name="password_confirmation" type="password"
                       required autocomplete="new-password" class="input">
            </div>

            <button type="submit" class="btn-primary w-full">{{ __('Daftar') }}</button>
        </form>
    </div>

    <p class="mt-6 text-center text-ink-2">
        {{ __('Sudah ada akaun?') }}
        <a href="{{ route('login') }}" class="font-bold text-brand hover:underline">{{ __('Log masuk di sini') }}</a>
    </p>
</x-guest-layout>
