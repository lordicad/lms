<x-cikgu-layout :title="__('Profil')" :heading="__('Profil')" :sub="__('Urus akaun dan tetapan anda')">
    @php
        // Exact WeLearn Teacher design tokens.
        $card = "background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:18px;padding:24px;display:flex;flex-direction:column;box-shadow:0 2px 10px rgba(46,44,80,.04)";
        $h2 = "margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--tp-ink)";
        $label = "font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;color:var(--tp-muted-2)";
        // Trailing semicolon matters: these get concatenated below, and "width:100%display:flex"
        // is one invalid declaration that takes both properties down with it.
        $input = "min-height:46px;border:1.5px solid var(--tp-line-2);border-radius:12px;padding:0 14px;background:var(--tp-input);font-family:'Nunito',sans-serif;font-size:14.5px;color:var(--tp-ink);box-sizing:border-box;width:100%;";
        $field = "display:flex;flex-direction:column;gap:6px";
        $primary = "align-self:flex-start;min-height:46px;border:none;cursor:pointer;border-radius:12px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;padding:0 22px";
        $err = "margin:0;font-size:12.5px;font-weight:700;color:#C24936";
        // Admin-maintained fields. Styled exactly like an input — same box, same ink — because the
        // teacher still needs to read these; they are simply not theirs to edit. Rendered as text
        // rather than a disabled <input>: there is nothing to submit, and a real input invites
        // tabbing into it. The flex centring is what puts the value on the box's midline.
        $locked = $input."display:flex;align-items:center;cursor:default";
        $note = "font-size:12.5px;color:var(--tp-muted-2)";
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

        {{-- Account details.

             Only the display name, photo and phone number are editable. The rest is the school's
             record of the teacher — it is shown here for reference and changed by the admin. The
             controller enforces this by never validating those fields; the styling below only
             reflects that decision. --}}
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
                           style="min-height:46px;border:1.5px solid var(--tp-line-2);border-radius:12px;padding:10px 14px;background:var(--tp-input);font-family:'Nunito',sans-serif;font-size:13.5px;color:var(--tp-ink);box-sizing:border-box;width:100%">
                    @error('avatar')<p style="{{ $err }}">{{ $message }}</p>@enderror
                </div>
            </div>

            <div style="{{ $field }}">
                <span style="{{ $label }}">{{ __('Nama penuh') }}</span>
                <span style="{{ $locked }}">{{ $user->name }}</span>
            </div>

            <div style="{{ $field }}">
                <label for="username" style="{{ $label }}">{{ __('Nama pengguna (nama paparan)') }}</label>
                <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" required style="{{ $input }}">
                <span style="font-size:12.5px;color:var(--tp-muted-2)">{{ __('Nama ini dipaparkan di papan pemuka anda. Anda boleh menukarnya bila-bila masa.') }}</span>
                @error('username')<p style="{{ $err }}">{{ $message }}</p>@enderror
            </div>

            {{-- Read-only: email is the sign-in identifier, set by the admin. --}}
            <div style="{{ $field }}">
                <span style="{{ $label }}">{{ __('E-mel (untuk log masuk)') }}</span>
                <span style="{{ $locked }}">{{ $user->email }}</span>
                <span style="{{ $note }}">{{ __('Emel log masuk anda tidak boleh diubah. Hubungi pentadbir sekolah jika ia perlu ditukar.') }}</span>
            </div>

            <div style="{{ $field }}">
                <label for="phone" style="{{ $label }}">{{ __('Nombor telefon') }}</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" placeholder="+60 12-345 6789" style="{{ $input }}">
                @error('phone')<p style="{{ $err }}">{{ $message }}</p>@enderror
            </div>

            <div style="{{ $field }}">
                <span style="{{ $label }}">{{ __('Jawatan') }}</span>
                <span style="{{ $locked }}">{{ $user->position ?: __('Belum ditetapkan') }}</span>
            </div>

            <div style="{{ $field }}">
                <span style="{{ $label }}">{{ __('Sekolah') }}</span>
                <span style="{{ $locked }}">{{ $user->school?->name ?: __('Belum ditetapkan') }}</span>
            </div>

            <div style="{{ $field }}">
                <span style="{{ $label }}">{{ __('Kelas guru kelas') }}</span>
                <span style="{{ $locked }}">{{ $user->homeroomClass?->label() ?: __('Bukan guru kelas') }}</span>
            </div>

            {{-- Subjects taught: the ones assigned, rather than every subject with most unticked. --}}
            <div style="{{ $field }}">
                <span style="{{ $label }}">{{ __('Subjek diajar') }}</span>
                @if ($user->subjects->isEmpty())
                    <span style="{{ $locked }}">{{ __('Belum ditetapkan') }}</span>
                @else
                    <div style="display:flex;flex-wrap:wrap;gap:8px">
                        @foreach ($user->subjects as $subject)
                            <span style="display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 13px;font-family:'Nunito',sans-serif;font-size:13.5px;background:rgb({{ $subject->rgb }} / .12);color:rgb({{ $subject->rgb }})">{{ $subject->displayName() }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            <p style="margin:0;{{ $note }}">{{ __('Butiran sekolah di atas diselenggara oleh pentadbir. Hubungi mereka jika ada yang perlu ditukar.') }}</p>

            <button type="submit" class="wl-primary" style="{{ $primary }}">{{ __('Simpan') }}</button>
        </form>

        {{-- Connect YouTube --}}
        @php($channels = $user->youtubeChannels()->latest('verified_at')->get())
        <div style="{{ $card }};gap:14px">
            <h2 style="{{ $h2 }}">{{ __('Sambung YouTube') }}</h2>
            <p style="margin:0;font-size:13.5px;color:var(--tp-muted-2);line-height:1.55">{{ __('Sahkan pemilikan saluran YouTube anda supaya video YouTube anda sendiri dikira dalam skor bakat. Kami hanya membaca senarai saluran anda — tiada token disimpan.') }}</p>

            @if (\App\Http\Controllers\YoutubeConnectController::isConfigured())
                <a href="{{ route('oauth.youtube.redirect') }}" class="wl-primary" style="{{ $primary }};padding:0 20px;display:flex;align-items:center;gap:8px;text-decoration:none">▶ {{ $channels->isEmpty() ? __('Sambung Akaun') : __('Sambung Lagi') }}</a>
            @endif

            @if ($channels->isEmpty())
                <div style="background:var(--tp-input);border-radius:12px;padding:14px 16px">
                    <span style="font-size:13px;color:var(--tp-muted-2)">{{ __('Tiada saluran disambungkan lagi. Video YouTube anda dikira sebagai rujukan sehingga anda menyambung.') }}</span>
                </div>
            @else
                <div style="display:flex;flex-direction:column;gap:8px">
                    @foreach ($channels as $channel)
                        <div style="display:flex;align-items:center;gap:12px;background:var(--tp-input);border-radius:12px;padding:12px 14px">
                            @if ($channel->thumbnail_url)
                                <img src="{{ $channel->thumbnail_url }}" alt="" loading="lazy" style="width:40px;height:40px;border-radius:50%;flex-shrink:0">
                            @else
                                <span style="width:40px;height:40px;border-radius:50%;flex-shrink:0;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center">▶</span>
                            @endif
                            <span style="min-width:0;flex:1">
                                <span style="display:block;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $channel->title }}</span>
                                <span style="display:block;font-size:12px;color:var(--tp-muted)">{{ __('Disahkan :date', ['date' => $channel->verified_at->translatedFormat('d M Y')]) }}</span>
                            </span>
                            <form method="POST" action="{{ route('oauth.youtube.disconnect', $channel) }}" style="flex-shrink:0"
                                  onsubmit="return confirm(@js(__("Putuskan sambungan saluran ini? Video YouTube dari saluran ini tidak akan lagi dikira untuk skor bakat anda.")))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="min-height:38px;border:1.5px solid var(--tp-line-2);cursor:pointer;border-radius:10px;background:var(--tp-surface);color:#C24936;font-family:'Geist',sans-serif;font-weight:800;font-size:13px;padding:0 14px">{{ __('Putuskan') }}</button>
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
