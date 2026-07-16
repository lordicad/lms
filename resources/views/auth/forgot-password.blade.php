<x-guest-layout :title="__('Lupa Kata Laluan')">
    <div class="card card-pad">
        <h1 class="text-2xl font-extrabold text-ink">{{ __('Lupa kata laluan') }}</h1>

        <p class="mt-2 text-ink-2">
            {{ __('Masukkan emel anda dan kami akan hantar pautan untuk menetapkan kata laluan baharu. Murid yang tiada emel perlu meminta bantuan cikgu.') }}
        </p>

        @if (session('status'))
            <x-alert type="success" class="mt-6">{{ session('status') }}</x-alert>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <label for="email" class="label">{{ __('Emel') }}</label>

                <input id="email" name="email" type="email" value="{{ old('email') }}"
                       required autofocus autocomplete="email" class="input"
                       @error('email') aria-invalid="true" @enderror>

                @error('email')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-primary w-full">{{ __('Hantar Pautan') }}</button>
        </form>
    </div>

    <p class="mt-6 text-center">
        <a href="{{ route('login') }}" class="font-bold text-brand hover:underline">{{ __('Kembali ke log masuk') }}</a>
    </p>
</x-guest-layout>
