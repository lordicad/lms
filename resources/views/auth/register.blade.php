<x-welearn-auth active="register" :title="__('Daftar Akaun')">
    {{-- One form, two roles. The Murid/Cikgu picker drives the hidden is_teacher field and reveals
         the role-specific inputs; the server re-checks everything anyway (see RegisteredUserController). --}}
    @php($startTeacher = (bool) old('is_teacher'))

    <div class="wla-stack" id="reg-root" data-teacher="{{ $startTeacher ? '1' : '0' }}">
        <div class="wla-head">
            <h2>{{ __('Cipta akaun baharu') }}</h2>
            <p>{{ __('Murid boleh daftar sendiri. Cikgu perlukan kod daripada sekolah.') }}</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="wla-stack">
            @csrf

            {{-- Role picker → sets is_teacher --}}
            <input type="hidden" id="is_teacher" name="is_teacher" value="{{ $startTeacher ? '1' : '0' }}">
            <div class="wla-roles" role="group" aria-label="{{ __('Pilih peranan') }}">
                <button type="button" class="wla-role {{ $startTeacher ? '' : 'is-active' }}" data-role="murid" aria-pressed="{{ $startTeacher ? 'false' : 'true' }}">
                    <span class="emoji">🎒</span>
                    <span class="name">{{ __('Murid') }}</span>
                </button>
                <button type="button" class="wla-role {{ $startTeacher ? 'is-active' : '' }}" data-role="cikgu" aria-pressed="{{ $startTeacher ? 'true' : 'false' }}">
                    <span class="emoji">🍎</span>
                    <span class="name">{{ __('Cikgu') }}</span>
                </button>
            </div>

            <label for="name" class="wla-label">
                {{ __('Nama penuh') }}
                <input id="name" name="name" type="text" value="{{ old('name') }}"
                       required autofocus autocomplete="name" class="wla-input"
                       placeholder="cth: Aiman Zulkifli" @error('name') aria-invalid="true" @enderror>
            </label>
            @error('name')<p class="wla-field-error">{{ $message }}</p>@enderror

            <label for="username" class="wla-label">
                {{ __('Nama pengguna') }}
                <input id="username" name="username" type="text" value="{{ old('username') }}"
                       required autocomplete="username" class="wla-input" placeholder="cth: aiman123"
                       aria-describedby="username-help" @error('username') aria-invalid="true" @enderror>
            </label>
            <p id="username-help" class="wla-hint">{{ __('Untuk log masuk. Contoh: aisyah.t3') }}</p>
            @error('username')<p class="wla-field-error">{{ $message }}</p>@enderror

            {{-- Student-only: Tahun --}}
            <div data-role-block="murid" @if($startTeacher) style="display:none" @endif>
                <label for="grade_level" class="wla-label">
                    {{ __('Tahun anda') }}
                    <select id="grade_level" name="grade_level" class="wla-select" @error('grade_level') aria-invalid="true" @enderror>
                        <option value="">{{ __('Sila pilih Tahun') }}</option>
                        @foreach ($grades as $grade)
                            <option value="{{ $grade->level }}" @selected(old('grade_level') == $grade->level)>{{ $grade->name }}</option>
                        @endforeach
                    </select>
                </label>
                @error('grade_level')<p class="wla-field-error">{{ $message }}</p>@enderror
            </div>

            {{-- Teacher-only: Emel + Kod Pendaftaran Guru --}}
            <div data-role-block="cikgu" class="wla-stack" @if(! $startTeacher) style="display:none" @endif>
                <div>
                    <label for="email" class="wla-label">
                        {{ __('Emel') }}
                        <input id="email" name="email" type="email" value="{{ old('email') }}"
                               autocomplete="email" class="wla-input" @error('email') aria-invalid="true" @enderror>
                    </label>
                    @error('email')<p class="wla-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="teacher_code" class="wla-label">
                        {{ __('Kod Pendaftaran Guru') }}
                        <input id="teacher_code" name="teacher_code" type="text" value="{{ old('teacher_code') }}"
                               class="wla-input" style="background:var(--field-warn)"
                               placeholder="cth: WBK-2026" aria-describedby="teacher-code-help"
                               @error('teacher_code') aria-invalid="true" @enderror>
                    </label>
                    <p id="teacher-code-help" class="wla-hint">{{ __('Dapatkan kod ini daripada pentadbir sekolah anda.') }}</p>
                    @error('teacher_code')<p class="wla-field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <label for="password" class="wla-label">
                {{ __('Kata laluan') }}
                <input id="password" name="password" type="password" required autocomplete="new-password"
                       class="wla-input" placeholder="••••••••" aria-describedby="password-help"
                       @error('password') aria-invalid="true" @enderror>
            </label>
            <p id="password-help" class="wla-hint">{{ __('Sekurang-kurangnya 6 aksara.') }}</p>
            @error('password')<p class="wla-field-error">{{ $message }}</p>@enderror

            <label for="password_confirmation" class="wla-label">
                {{ __('Ulang kata laluan') }}
                <input id="password_confirmation" name="password_confirmation" type="password"
                       required autocomplete="new-password" class="wla-input" placeholder="••••••••">
            </label>

            <button type="submit" class="wla-btn">{{ __('Daftar Sekarang') }}</button>
        </form>

        <p style="margin:0;text-align:center;font-size:14.5px;color:var(--muted)">
            {{ __('Sudah ada akaun?') }}
            <a href="{{ route('login') }}" style="font-weight:700">{{ __('Log masuk di sini') }}</a>
        </p>
    </div>

    <script>
        (function () {
            var root = document.getElementById('reg-root');
            if (!root) return;
            var hidden = document.getElementById('is_teacher');
            var roles = root.querySelectorAll('.wla-role');
            var blocks = root.querySelectorAll('[data-role-block]');
            function apply(role) {
                var teacher = role === 'cikgu';
                hidden.value = teacher ? '1' : '0';
                roles.forEach(function (b) {
                    var on = b.getAttribute('data-role') === role;
                    b.classList.toggle('is-active', on);
                    b.setAttribute('aria-pressed', on ? 'true' : 'false');
                });
                blocks.forEach(function (bl) {
                    bl.style.display = bl.getAttribute('data-role-block') === role ? '' : 'none';
                });
            }
            roles.forEach(function (b) {
                b.addEventListener('click', function () { apply(b.getAttribute('data-role')); });
            });
        })();
    </script>
</x-welearn-auth>
