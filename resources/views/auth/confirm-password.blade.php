<x-guest-layout :title="__('Sahkan Kata Laluan')">
    <div class="card card-pad">
        <h1 class="text-2xl font-extrabold text-ink">{{ __('Sahkan kata laluan') }}</h1>
        <p class="mt-2 text-ink-2">{{ __('Sila masukkan kata laluan anda sekali lagi untuk meneruskan.') }}</p>

        <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <label for="password" class="label">{{ __('Kata laluan') }}</label>

                <input id="password" name="password" type="password" required autofocus
                       autocomplete="current-password" class="input"
                       @error('password') aria-invalid="true" @enderror>

                @error('password')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-primary w-full">{{ __('Sahkan') }}</button>
        </form>
    </div>
</x-guest-layout>
