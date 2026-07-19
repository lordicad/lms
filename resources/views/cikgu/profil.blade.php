<x-cikgu-layout :title="__('Profil')" :heading="__('Profil')" :sub="__('Urus akaun dan tetapan anda')">
    @php
        // Exact WeLearn Teacher design tokens.
        $card = "background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:18px;padding:24px;display:flex;flex-direction:column;box-shadow:0 2px 10px rgba(46,44,80,.04)";
        $h2 = "margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:#28293F";
        $label = "font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;color:#6C6F87";
        $input = "min-height:46px;border:1.5px solid rgba(46,44,80,.12);border-radius:12px;padding:0 14px;background:#F6F5F0;font-family:'Nunito',sans-serif;font-size:14.5px;color:#28293F;box-sizing:border-box;width:100%";
        $field = "display:flex;flex-direction:column;gap:6px";
        $primary = "align-self:flex-start;min-height:46px;border:none;cursor:pointer;border-radius:12px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;padding:0 22px";
        $err = "margin:0;font-size:12.5px;font-weight:700;color:#C24936";
    @endphp

    <style>
        .wl-file::file-selector-button { min-height:38px;border:none;cursor:pointer;border-radius:10px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:13px;padding:0 16px;margin-right:14px;transition:background .15s; }
        .wl-file::file-selector-button:hover { background:#2BB39B; }
        .wl-primary:hover { background:#2BB39B; }
        .wl-primary:active { transform:scale(.98); }
    </style>

    <div style="display:flex;flex-direction:column;gap:20px;max-width:720px">

        @if (session('status'))
            <div style="background:#DCF2EE;border:1px solid rgba(15,122,104,.25);border-radius:12px;padding:12px 16px;font-family:'Geist',sans-serif;font-size:13.5px;font-weight:700;color:#0F7A68">{{ session('status') }}</div>
        @endif

        {{-- Account details --}}
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" style="{{ $card }};gap:16px">
            @csrf
            @method('PATCH')
            <h2 style="{{ $h2 }}">{{ __('Butiran akaun') }}</h2>

            <div style="display:flex;align-items:flex-end;gap:16px">
                <span style="width:64px;height:64px;border-radius:50%;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:20px;flex-shrink:0;overflow:hidden">
                    @if ($user->avatarUrl())<img src="{{ $user->avatarUrl() }}" alt="" style="width:100%;height:100%;object-fit:cover">@else{{ $user->initials() }}@endif
                </span>
                <div style="{{ $field }};flex:1">
                    <label for="avatar" style="{{ $label }}">{{ __('Gambar profil') }}</label>
                    <input id="avatar" name="avatar" type="file" accept="image/*" class="wl-file"
                           style="min-height:46px;border:1.5px solid rgba(46,44,80,.12);border-radius:12px;padding:10px 14px;background:#F6F5F0;font-family:'Nunito',sans-serif;font-size:13.5px;color:#28293F;box-sizing:border-box;width:100%">
                    @error('avatar')<p style="{{ $err }}">{{ $message }}</p>@enderror
                </div>
            </div>

            <div style="{{ $field }}">
                <label for="name" style="{{ $label }}">{{ __('Nama penuh') }}</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required style="{{ $input }}">
                @error('name')<p style="{{ $err }}">{{ $message }}</p>@enderror
            </div>

            <div style="{{ $field }}">
                <label for="username" style="{{ $label }}">{{ __('Nama pengguna') }}</label>
                <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" required style="{{ $input }}">
                @error('username')<p style="{{ $err }}">{{ $message }}</p>@enderror
            </div>

            <div style="{{ $field }}">
                <label for="email" style="{{ $label }}">{{ __('E-mel') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required style="{{ $input }}">
                @error('email')<p style="{{ $err }}">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="wl-primary" style="{{ $primary }}">{{ __('Simpan') }}</button>
        </form>

        {{-- Connect YouTube --}}
        @php($channels = $user->youtubeChannels()->latest('verified_at')->get())
        <div style="{{ $card }};gap:14px">
            <h2 style="{{ $h2 }}">{{ __('Sambung YouTube') }}</h2>
            <p style="margin:0;font-size:13.5px;color:#6C6F87;line-height:1.55">{{ __('Sahkan pemilikan saluran YouTube anda supaya video YouTube anda sendiri dikira dalam skor bakat. Kami hanya membaca senarai saluran anda — tiada token disimpan.') }}</p>

            @if (\App\Http\Controllers\YoutubeConnectController::isConfigured())
                <a href="{{ route('oauth.youtube.redirect') }}" class="wl-primary" style="{{ $primary }};padding:0 20px;display:flex;align-items:center;gap:8px;text-decoration:none">▶ {{ $channels->isEmpty() ? __('Sambung Akaun') : __('Sambung Lagi') }}</a>
            @endif

            @if ($channels->isEmpty())
                <div style="background:#F6F5F0;border-radius:12px;padding:14px 16px">
                    <span style="font-size:13px;color:#6C6F87">{{ __('Tiada saluran disambungkan lagi. Video YouTube anda dikira sebagai rujukan sehingga anda menyambung.') }}</span>
                </div>
            @else
                <div style="display:flex;flex-direction:column;gap:8px">
                    @foreach ($channels as $channel)
                        <div style="display:flex;align-items:center;gap:12px;background:#F6F5F0;border-radius:12px;padding:12px 14px">
                            @if ($channel->thumbnail_url)
                                <img src="{{ $channel->thumbnail_url }}" alt="" loading="lazy" style="width:40px;height:40px;border-radius:50%;flex-shrink:0">
                            @else
                                <span style="width:40px;height:40px;border-radius:50%;flex-shrink:0;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center">▶</span>
                            @endif
                            <span style="min-width:0;flex:1">
                                <span style="display:block;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:#28293F;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $channel->title }}</span>
                                <span style="display:block;font-size:12px;color:#8B8AA3">{{ __('Disahkan :date', ['date' => $channel->verified_at->translatedFormat('d M Y')]) }}</span>
                            </span>
                            <form method="POST" action="{{ route('oauth.youtube.disconnect', $channel) }}" style="flex-shrink:0"
                                  onsubmit='return confirm(@js(__("Putuskan sambungan saluran ini? Video YouTube dari saluran ini tidak akan lagi dikira untuk skor bakat anda.")))'>
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="min-height:38px;border:1.5px solid rgba(46,44,80,.12);cursor:pointer;border-radius:10px;background:#fff;color:#C24936;font-family:'Geist',sans-serif;font-weight:800;font-size:13px;padding:0 14px">{{ __('Putuskan') }}</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Change password --}}
        <form method="POST" action="{{ route('profile.password') }}" style="{{ $card }};gap:16px">
            @csrf
            @method('PUT')
            <h2 style="{{ $h2 }}">{{ __('Tukar kata laluan') }}</h2>

            <div style="{{ $field }}">
                <label for="current_password" style="{{ $label }}">{{ __('Kata laluan semasa') }}</label>
                <input id="current_password" name="current_password" type="password" style="{{ $input }}">
                @error('current_password', 'updatePassword')<p style="{{ $err }}">{{ $message }}</p>@enderror
            </div>

            <div style="{{ $field }}">
                <label for="password" style="{{ $label }}">{{ __('Kata laluan baru') }}</label>
                <input id="password" name="password" type="password" style="{{ $input }}">
                @error('password', 'updatePassword')<p style="{{ $err }}">{{ $message }}</p>@enderror
            </div>

            <div style="{{ $field }}">
                <label for="password_confirmation" style="{{ $label }}">{{ __('Ulang kata laluan baru') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" style="{{ $input }}">
            </div>

            <button type="submit" class="wl-primary" style="{{ $primary }}">{{ __('Tukar Kata Laluan') }}</button>
        </form>

    </div>
</x-cikgu-layout>
