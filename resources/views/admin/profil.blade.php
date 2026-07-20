@php($user = auth()->user())

<x-admin-layout :title="__('Profil Saya')"
                :heading="__('Profil Saya')"
                :sub="__('Kemas kini butiran akaun dan kata laluan anda')">

    <div style="display:flex;flex-direction:column;gap:20px;max-width:720px">

        {{-- Account details --}}
        <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:18px;padding:24px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
            <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Butiran akaun') }}</h2>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:16px;margin-top:16px">
                @csrf
                @method('PATCH')

                <div style="display:flex;align-items:flex-end;gap:16px">
                    <span style="width:64px;height:64px;border-radius:50%;background:#17907B;color:#fff;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:20px;flex-shrink:0;overflow:hidden">
                        @if ($user->avatarUrl())<img src="{{ $user->avatarUrl() }}" alt="" style="width:100%;height:100%;object-fit:cover">@else{{ $user->initials() }}@endif
                    </span>
                    <div class="tp-field" style="flex:1">
                        <label for="avatar" class="tp-label">{{ __('Gambar profil') }}</label>
                        <input id="avatar" name="avatar" type="file" accept="image/*" class="tp-file">
                        @error('avatar')<span class="tp-hint" style="color:#C24936;font-weight:700">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="tp-field">
                    <label for="name" class="tp-label">{{ __('Nama penuh') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="tp-input">
                    @error('name')<span class="tp-hint" style="color:#C24936;font-weight:700">{{ $message }}</span>@enderror
                </div>

                <div class="tp-field">
                    <label for="username" class="tp-label">{{ __('Nama pengguna') }}</label>
                    <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" required class="tp-input">
                    @error('username')<span class="tp-hint" style="color:#C24936;font-weight:700">{{ $message }}</span>@enderror
                </div>

                <div class="tp-field">
                    <label for="email" class="tp-label">{{ __('E-mel') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="tp-input">
                    @error('email')<span class="tp-hint" style="color:#C24936;font-weight:700">{{ $message }}</span>@enderror
                </div>

                <button type="submit" class="tp-btn" style="align-self:flex-start">{{ __('Simpan') }}</button>
            </form>
        </div>

        {{-- Change password --}}
        <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:18px;padding:24px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
            <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Tukar kata laluan') }}</h2>

            <form method="POST" action="{{ route('password.update') }}" style="display:flex;flex-direction:column;gap:16px;margin-top:16px">
                @csrf
                @method('PUT')

                <div class="tp-field">
                    <label for="current_password" class="tp-label">{{ __('Kata laluan semasa') }}</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="tp-input">
                    @error('current_password', 'updatePassword')<span class="tp-hint" style="color:#C24936;font-weight:700">{{ $message }}</span>@enderror
                </div>

                <div class="tp-field">
                    <label for="password" class="tp-label">{{ __('Kata laluan baharu') }}</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" class="tp-input">
                    @error('password', 'updatePassword')<span class="tp-hint" style="color:#C24936;font-weight:700">{{ $message }}</span>@enderror
                </div>

                <div class="tp-field">
                    <label for="password_confirmation" class="tp-label">{{ __('Ulang kata laluan baharu') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="tp-input">
                </div>

                <button type="submit" class="tp-btn" style="align-self:flex-start">{{ __('Tukar Kata Laluan') }}</button>
            </form>
        </div>
    </div>
</x-admin-layout>
