{{-- tabs off: with registration hidden, the lone "Log Masuk" tab was redundant. Remove :tabs to bring it back. --}}
<x-welearn-auth active="login" :tabs="false" :title="__('Log Masuk')">
    <div class="wla-stack">
        <div class="wla-head">
            <h2>{{ __('Selamat kembali!') }}</h2>
            <p>{{ __('Murid boleh log masuk dengan nama pengguna sahaja.') }}</p>
        </div>

        @if (session('status'))
            <div class="wla-alert info">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="wla-stack">
            @csrf

            <label for="login" class="wla-label">
                {{ __('Nama pengguna atau emel') }}
                <input id="login" name="login" type="text" value="{{ old('login') }}"
                       required autofocus autocomplete="username" class="wla-input"
                       placeholder="cth: aiman123" @error('login') aria-invalid="true" @enderror>
            </label>
            @error('login')
                <p class="wla-field-error">{{ $message }}</p>
            @enderror

            <label for="password" class="wla-label">
                <span class="wla-label-row">
                    {{ __('Kata laluan') }}
                    <a href="{{ route('password.request') }}" style="font-size:13px;font-weight:700">{{ __('Lupa kata laluan?') }}</a>
                </span>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="wla-input" placeholder="••••••••" @error('password') aria-invalid="true" @enderror>
            </label>
            @error('password')
                <p class="wla-field-error">{{ $message }}</p>
            @enderror

            <label for="remember" style="display:flex;align-items:center;gap:10px;font-weight:700;font-size:14px;color:var(--muted);cursor:pointer">
                <input id="remember" name="remember" type="checkbox" style="width:18px;height:18px;accent-color:var(--brand)">
                {{ __('Ingat saya') }}
            </label>

            <button type="submit" class="wla-btn">{{ __('Log Masuk') }}</button>
        </form>
    </div>
</x-welearn-auth>
