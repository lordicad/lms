<x-dynamic-component :component="auth()->user()->isTeacher() ? 'app-layout' : 'student-layout'" :title="__('Profil Saya')">
    @php
        $cardStyle = 'background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:22px;padding:28px;box-shadow:0 4px 16px rgba(46,44,80,.04)';
        $labelStyle = 'display:block;margin-bottom:6px;font-family:\'Geist\',sans-serif;font-weight:700;font-size:14px;color:#4A5A4E';
        $inputStyle = 'min-height:48px;width:100%;border:1px solid var(--wl-line-3);border-radius:12px;padding:0 16px;font-family:\'Nunito\',sans-serif;font-size:15px;color:var(--wl-body);background:var(--wl-surface);box-sizing:border-box';
        $errStyle = 'margin:6px 0 0;font-weight:700;font-size:13px;color:#C24936';
        $h2Style = 'margin:0 0 6px;font-family:\'Geist\',sans-serif;font-size:20px;font-weight:800;color:var(--wl-ink)';
        // Admin-maintained rows: the same box and the same ink as an input, because the student
        // still needs to read these — they are simply not theirs to edit. The leading semicolon
        // matters, $inputStyle above ends without one.
        $lockedStyle = $inputStyle.';display:flex;align-items:center;cursor:default';
        $noteStyle = 'margin:6px 0 0;font-size:12.5px;color:var(--wl-muted)';
    @endphp

    {{-- Centred within the main content box. --}}
    <div style="display:flex;flex-direction:column;gap:32px;max-width:820px;width:100%;margin:0 auto">
        @if ($stats)
            {{-- Header --}}
            <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:22px;padding:28px;display:flex;align-items:center;gap:22px;box-shadow:0 8px 24px var(--wl-line);flex-wrap:wrap">
                <span style="width:84px;height:84px;border-radius:50%;background:#17907B;color:#fff;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:32px;flex-shrink:0;border:4px solid #DCF2EE;overflow:hidden">
                    @if ($user->avatarUrl())<img src="{{ $user->avatarUrl() }}" alt="" style="width:100%;height:100%;object-fit:cover">@else{{ $user->initials() }}@endif
                </span>
                <div style="display:flex;flex-direction:column;gap:6px;min-width:0;flex:1">
                    <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:24px;font-weight:800;letter-spacing:-.01em;color:var(--wl-ink)">{{ $user->name }}</h2>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        @if ($user->grade)<span style="background:#E4EEF9;color:#2E6CA8;border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:12px;font-weight:800">{{ $user->grade->name }}</span>@endif
                    </div>
                    @if ($user->email)<span style="font-size:13px;color:var(--wl-muted)">{{ $user->email }}</span>@endif
                </div>
            </div>

            {{-- Stats --}}
            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px">
                <div style="background:#DCF2EE;border-radius:18px;padding:18px;display:flex;flex-direction:column;gap:4px">
                    <span style="font-size:20px">⭐</span>
                    <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#0F7A68">{{ number_format($stats['points']) }}</span>
                    <span style="font-size:12.5px;font-weight:700;color:#0F7A68">{{ __('Jumlah mata') }}</span>
                </div>
                <div style="background:#FEF0CE;border-radius:18px;padding:18px;display:flex;flex-direction:column;gap:4px">
                    <span style="font-size:20px">📝</span>
                    <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#8A6A12">{{ $stats['quizzes'] }}</span>
                    <span style="font-size:12.5px;font-weight:700;color:#8A6A12">{{ __('Kuiz selesai') }}</span>
                </div>
                <div style="background:#E4EEF9;border-radius:18px;padding:18px;display:flex;flex-direction:column;gap:4px">
                    <span style="font-size:20px">🎬</span>
                    <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#2E6CA8">{{ $stats['videos'] }}</span>
                    <span style="font-size:12.5px;font-weight:700;color:#2E6CA8">{{ __('Video ditonton') }}</span>
                </div>
                <div style="background:#FBE4ED;border-radius:18px;padding:18px;display:flex;flex-direction:column;gap:4px">
                    <span style="font-size:20px">🏆</span>
                    <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#B84A75">{{ $stats['rank'] ? '#'.$stats['rank'] : '—' }}</span>
                    <span style="font-size:12.5px;font-weight:700;color:#B84A75">Ranking</span>
                </div>
            </div>

            {{-- Badges --}}
            @php($badges = [
                ['icon' => '🔥', 'name' => __('Rajin Belajar'), 'desc' => __('5 kuiz selesai'), 'got' => $stats['quizzes'] >= 5, 'tint' => '#FEF0CE', 'ring' => '#E8A33D', 'ribbon' => '#F3B94C'],
                ['icon' => '🎯', 'name' => __('Markah Penuh'), 'desc' => __('100% dalam kuiz'), 'got' => $stats['perfect'], 'tint' => '#FBE4ED', 'ring' => '#D96A96', 'ribbon' => '#E886AC'],
                ['icon' => '🎬', 'name' => __('Penonton Setia'), 'desc' => __('25 video ditonton'), 'got' => $stats['videos'] >= 25, 'tint' => '#E9E4F9', 'ring' => '#8A6FD0', 'ribbon' => '#A48CE0'],
                ['icon' => '🚀', 'name' => __('10 Teratas'), 'desc' => __('Capai ranking top 10'), 'got' => $stats['rank'] && $stats['rank'] <= 10, 'tint' => '#EDEDF1', 'ring' => '#B9B8C6', 'ribbon' => '#C9C8D4'],
            ])
            <div style="display:flex;flex-direction:column;gap:12px">
                <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--wl-ink)">{{ __('Lencana Saya') }}</h3>
                <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px">
                    @foreach ($badges as $b)
                        @php($filter = $b['got'] ? 'none' : 'grayscale(1) opacity(.45)')
                        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:6px 0;{{ $b['got'] ? '' : 'opacity:.75' }}">
                            {{-- Rosette medal (ribbon tails + scalloped medal + tinted disc + emoji) --}}
                            <div style="position:relative;width:78px;height:90px;display:flex;justify-content:center">
                                <span style="position:absolute;top:36px;left:21px;width:26px;height:46px;background:{{ $b['ribbon'] }};filter:brightness(.82) saturate(1.15);transform:rotate(28deg);transform-origin:50% 0;clip-path:polygon(0 0,100% 0,100% 100%,50% 74%,0 100%)"></span>
                                <span style="position:absolute;top:36px;right:21px;width:26px;height:46px;background:{{ $b['ribbon'] }};filter:brightness(.82) saturate(1.15);transform:rotate(-28deg);transform-origin:50% 0;clip-path:polygon(0 0,100% 0,100% 100%,50% 74%,0 100%)"></span>
                                <span style="position:absolute;top:6px;left:13px;width:52px;height:52px;background:{{ $b['ribbon'] }};border-radius:9px"></span>
                                <span style="position:absolute;top:6px;left:13px;width:52px;height:52px;background:{{ $b['ribbon'] }};border-radius:9px;transform:rotate(30deg)"></span>
                                <span style="position:absolute;top:6px;left:13px;width:52px;height:52px;background:{{ $b['ribbon'] }};border-radius:9px;transform:rotate(60deg)"></span>
                                <span style="position:absolute;top:5px;width:54px;height:54px;border-radius:50%;background:{{ $b['tint'] }};display:grid;place-items:center;box-shadow:0 4px 12px rgba(46,44,80,.16)">
                                    <span style="width:41px;height:41px;border-radius:50%;background:var(--wl-surface);border:2px solid {{ $b['ring'] }};display:grid;place-items:center">
                                        <span style="font-size:19px;filter:{{ $filter }}">{{ $b['icon'] }}</span>
                                    </span>
                                </span>
                            </div>
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13px;color:var(--wl-ink);text-align:center">{{ $b['name'] }}</span>
                            <span style="font-size:11.5px;font-weight:700;color:var(--wl-muted);text-align:center">{{ $b['desc'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:24px;font-weight:800;color:var(--wl-ink)">{{ __('Profil Saya') }}</h2>
        @endif

        {{-- Account info form. Collapsed by default: it is a long form, and most visits to this
             page are only a look at the profile. It opens itself when the last save came back with
             errors, so a validation message is never left hidden behind a closed panel. --}}
        <section style="{{ $cardStyle }}"
                 x-data="{ open: @js($errors->getBag('default')->any()) }">
            <button type="button" @click="open = ! open"
                    :aria-expanded="open ? 'true' : 'false'" aria-controls="akaun-panel"
                    style="width:100%;display:flex;align-items:center;gap:12px;background:none;border:none;padding:0;margin:0;cursor:pointer;text-align:left;font:inherit;color:inherit">
                <h2 style="{{ $h2Style }};flex:1">{{ __('Maklumat akaun') }}</h2>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                     style="flex-shrink:0;color:var(--wl-muted);transition:transform .15s"
                     :style="{ transform: open ? 'rotate(180deg)' : 'none' }"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div id="akaun-panel" x-show="open" x-cloak>
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:18px;margin-top:14px">
                @csrf
                @method('PATCH')
                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                    <x-avatar :user="$user" size="lg" />
                    <div style="flex:1;min-width:200px">
                        <label for="avatar" style="{{ $labelStyle }}">{{ __('Gambar profil') }}</label>
                        <input id="avatar" name="avatar" type="file" accept="image/*" class="wl-file" style="{{ $inputStyle }};padding:9px 12px">
                        <style>
                            .wl-file::file-selector-button { min-height:38px;border:none;cursor:pointer;border-radius:10px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:13px;padding:0 16px;margin-right:14px;transition:background .15s; }
                            .wl-file::file-selector-button:hover { background:#2BB39B; }
                        </style>
                        @error('avatar')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    @if ($user->isAdmin())
                        <label for="name" style="{{ $labelStyle }}">{{ __('Nama penuh') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required style="{{ $inputStyle }}">
                        @error('name')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                    @else
                        <span style="{{ $labelStyle }}">{{ __('Nama penuh') }}</span>
                        <span style="{{ $lockedStyle }}">{{ $user->name }}</span>
                    @endif
                </div>
                <div>
                    <label for="username" style="{{ $labelStyle }}">{{ __('Nama pengguna (nama paparan)') }}</label>
                    <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" required style="{{ $inputStyle }}">
                    <span style="font-size:12.5px;color:var(--tp-muted-2)">{{ __('Nama ini dipaparkan di papan pemuka anda. Anda boleh menukarnya bila-bila masa.') }}</span>
                    @error('username')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                </div>
                {{-- Read-only: email is the sign-in identifier, set by the admin. --}}
                <div>
                    <span style="{{ $labelStyle }}">{{ __('E-mel (untuk log masuk)') }}</span>
                    <span style="{{ $lockedStyle }}">{{ $user->email }}</span>
                    <p style="{{ $noteStyle }}">{{ __('Emel log masuk anda tidak boleh diubah. Hubungi pentadbir sekolah jika ia perlu ditukar.') }}</p>
                </div>
                @if ($user->isStudent())
                    {{-- School record. Kept by the admin, shown here so the student can check it. --}}
                    <div>
                        <span style="{{ $labelStyle }}">{{ __('Sekolah') }}</span>
                        <span style="{{ $lockedStyle }}">{{ $user->school?->name ?: __('Belum ditetapkan') }}</span>
                    </div>

                    <div>
                        <span style="{{ $labelStyle }}">{{ __('Tahun') }}</span>
                        <span style="{{ $lockedStyle }}">{{ $user->grade?->name ?: __('Belum ditetapkan') }}</span>
                    </div>

                    <div>
                        <span style="{{ $labelStyle }}">{{ __('Kelas') }}</span>
                        <span style="{{ $lockedStyle }}">{{ $user->schoolClass?->label() ?: __('Belum ditetapkan') }}</span>
                    </div>

                    <div>
                        <span style="{{ $labelStyle }}">{{ __('Guru kelas') }}</span>
                        <span style="{{ $lockedStyle }}">{{ $homeroomTeacher?->name ?: __('Belum ditetapkan') }}</span>
                        <p style="{{ $noteStyle }}">{{ __('Ditentukan oleh kelas yang dipilih.') }}</p>
                    </div>

                    <div>
                        <span style="{{ $labelStyle }}">{{ __('Nama penjaga') }}</span>
                        <span style="{{ $lockedStyle }}">{{ $user->guardian_name ?: __('Belum ditetapkan') }}</span>
                    </div>

                    <div>
                        <span style="{{ $labelStyle }}">{{ __('Nombor telefon penjaga') }}</span>
                        <span style="{{ $lockedStyle }}">{{ $user->guardian_phone ?: __('Belum ditetapkan') }}</span>
                    </div>

                    <div>
                        <span style="{{ $labelStyle }}">{{ __('E-mel penjaga') }}</span>
                        <span style="{{ $lockedStyle }}">{{ $user->guardian_email ?: __('Belum ditetapkan') }}</span>
                    </div>

                    <p style="margin:0;{{ $noteStyle }}">{{ __('Butiran sekolah di atas diselenggara oleh pentadbir. Hubungi mereka jika ada yang perlu ditukar.') }}</p>
                @endif
                <button type="submit" class="wl-btn-primary" style="align-self:flex-start;min-height:48px;border:none;cursor:pointer;border-radius:13px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;padding:0 24px">{{ __('Simpan') }}</button>
            </form>
            </div>
        </section>

        @if ($user->isTeacher())
            <x-youtube-connect-card :user="$user" />
        @endif

        {{-- Change password. Same treatment, and it opens on its own error bag: this form posts
             to password.update, which reports into 'updatePassword' rather than the default bag. --}}
        <section style="{{ $cardStyle }}"
                 x-data="{ open: @js($errors->getBag('updatePassword')->any()) }">
            <button type="button" @click="open = ! open"
                    :aria-expanded="open ? 'true' : 'false'" aria-controls="kata-laluan-panel"
                    style="width:100%;display:flex;align-items:center;gap:12px;background:none;border:none;padding:0;margin:0;cursor:pointer;text-align:left;font:inherit;color:inherit">
                <h2 style="{{ $h2Style }};flex:1">{{ __('Tukar kata laluan') }}</h2>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                     style="flex-shrink:0;color:var(--wl-muted);transition:transform .15s"
                     :style="{ transform: open ? 'rotate(180deg)' : 'none' }"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div id="kata-laluan-panel" x-show="open" x-cloak>
            <form method="POST" action="{{ route('password.update') }}" style="display:flex;flex-direction:column;gap:18px;margin-top:14px">
                @csrf
                @method('PUT')
                <div>
                    <label for="current_password" style="{{ $labelStyle }}">{{ __('Kata laluan semasa') }}</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" style="{{ $inputStyle }}">
                    @error('current_password', 'updatePassword')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="new_password" style="{{ $labelStyle }}">{{ __('Kata laluan baharu') }}</label>
                    <input id="new_password" name="password" type="password" autocomplete="new-password" style="{{ $inputStyle }}">
                    @error('password', 'updatePassword')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="new_password_confirmation" style="{{ $labelStyle }}">{{ __('Ulang kata laluan baharu') }}</label>
                    <input id="new_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" style="{{ $inputStyle }}">
                </div>
                <button type="submit" class="wl-btn-primary" style="align-self:flex-start;min-height:48px;border:none;cursor:pointer;border-radius:13px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;padding:0 24px">{{ __('Tukar Kata Laluan') }}</button>
            </form>
            </div>
        </section>

        {{-- Log out (moved here from the student sidebar) --}}
        <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm(@js(__("Log keluar daripada akaun anda?")))">
            @csrf
            <button type="submit" style="display:inline-flex;align-items:center;gap:10px;min-height:48px;border:1.5px solid rgba(194,73,54,.3);cursor:pointer;border-radius:13px;background:var(--wl-surface);color:#C24936;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;padding:0 22px">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                {{ __('Log Keluar') }}
            </button>
        </form>

    </div>
</x-dynamic-component>
