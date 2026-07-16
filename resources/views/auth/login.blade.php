<x-guest-layout :title="__('Log Masuk')">
    <div class="card card-pad">
        <h1 class="text-2xl font-extrabold text-ink">{{ __('Log Masuk') }}</h1>
        <p class="mt-2 text-ink-2">{{ __('Selamat kembali. Sila masukkan butiran akaun anda.') }}</p>

        @if (session('status'))
            <x-alert type="success" class="mt-6">{{ session('status') }}</x-alert>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <label for="login" class="label">{{ __('Nama pengguna atau emel') }}</label>

                <input id="login" name="login" type="text" value="{{ old('login') }}"
                       required autofocus autocomplete="username" class="input"
                       aria-describedby="login-help" @error('login') aria-invalid="true" @enderror>

                <p id="login-help" class="help">{{ __('Murid boleh guna nama pengguna sahaja.') }}</p>

                @error('login')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="label">{{ __('Kata laluan') }}</label>

                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="input" @error('password') aria-invalid="true" @enderror>

                @error('password')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <label for="remember" class="flex items-center gap-2 text-sm font-semibold text-ink">
                    <input id="remember" name="remember" type="checkbox"
                           class="h-5 w-5 rounded border-line text-brand focus:ring-brand">
                    {{ __('Ingat saya') }}
                </label>

                <a href="{{ route('password.request') }}" class="text-sm font-bold text-brand hover:underline">
                    {{ __('Lupa kata laluan?') }}
                </a>
            </div>

            <button type="submit" class="btn-primary w-full">{{ __('Log Masuk') }}</button>
        </form>
    </div>

    <p class="mt-6 text-center text-ink-2">
        {{ __('Belum ada akaun?') }}
        <a href="{{ route('register') }}" class="font-bold text-brand hover:underline">{{ __('Daftar di sini') }}</a>
    </p>
</x-guest-layout>
