<x-guest-layout :title="__('Tetapkan Kata Laluan Baharu')">
    <div class="card card-pad">
        <h1 class="text-2xl font-extrabold text-ink">{{ __('Kata laluan baharu') }}</h1>

        <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-5">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <label for="email" class="label">{{ __('Emel') }}</label>

                <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}"
                       required autofocus autocomplete="email" class="input"
                       @error('email') aria-invalid="true" @enderror>

                @error('email')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="label">{{ __('Kata laluan baharu') }}</label>

                <input id="password" name="password" type="password" required autocomplete="new-password"
                       class="input" aria-describedby="password-help"
                       @error('password') aria-invalid="true" @enderror>

                <p id="password-help" class="help">{{ __('Sekurang-kurangnya 6 aksara.') }}</p>

                @error('password')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="label">{{ __('Ulang kata laluan baharu') }}</label>

                <input id="password_confirmation" name="password_confirmation" type="password"
                       required autocomplete="new-password" class="input">

                @error('password_confirmation')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-primary w-full">{{ __('Simpan Kata Laluan') }}</button>
        </form>
    </div>
</x-guest-layout>
