{{--
    First sign-in on an admin-created account. The username stays as the admin issued it; only the
    password changes, so it is shown here read-only to make that clear rather than left to guesswork.
--}}
<x-welearn-auth :title="__('Tetapkan Kata Laluan')" :tabs="false" :back="false">
    <div class="wla-stack">
        <div class="wla-head">
            <h2>{{ __('Selamat datang, :name!', ['name' => auth()->user()->name]) }}</h2>
            <p>{{ __('Ini kali pertama anda log masuk. Sila tetapkan kata laluan anda sendiri sebelum meneruskan.') }}</p>
        </div>

        @php($me = auth()->user())

        <div class="wla-alert info">
            {{ $me->signsInWithEmail()
                ? __('Anda log masuk dengan emel ini. Hanya kata laluan yang berubah.')
                : __('Nama pengguna anda kekal sama. Hanya kata laluan yang berubah.') }}
        </div>

        {{-- The identifier they actually sign in with: email for a teacher, username for a student. --}}
        <label class="wla-label">
            {{ $me->signsInWithEmail() ? __('Emel') : __('Nama pengguna') }}
            <input type="text" value="{{ $me->signInIdentifier() }}" readonly disabled
                   class="wla-input" style="opacity:.7;cursor:not-allowed">
        </label>

        <form method="POST" action="{{ route('password.first.store') }}" class="wla-stack">
            @csrf

            <label for="password" class="wla-label">
                {{ __('Kata laluan baharu') }}
                <input id="password" name="password" type="password" required autofocus
                       autocomplete="new-password" class="wla-input" placeholder="••••••••"
                       @error('password') aria-invalid="true" @enderror>
            </label>
            @error('password')
                <p class="wla-field-error">{{ $message }}</p>
            @enderror

            <label for="password_confirmation" class="wla-label">
                {{ __('Sahkan kata laluan baharu') }}
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       autocomplete="new-password" class="wla-input" placeholder="••••••••">
            </label>

            <p style="margin:0;font-size:13px;color:var(--muted)">{{ __('Kata laluan mesti sekurang-kurangnya 6 aksara.') }}</p>

            <button type="submit" class="wla-btn">{{ __('Simpan & Teruskan') }}</button>
        </form>
    </div>

    <x-slot:footer>
        {{-- The only way out while the account is held, so it must not be a link back into the app. --}}
        <form method="POST" action="{{ route('logout') }}" onsubmit='return confirm(@js(__("Log keluar daripada akaun anda?")))' style="text-align:center;margin-top:18px">
            @csrf
            <button type="submit" class="wla-back" style="background:none;border:none;cursor:pointer;font:inherit">
                {{ __('Log Keluar') }}
            </button>
        </form>
    </x-slot:footer>
</x-welearn-auth>
