<x-dynamic-component :component="auth()->user()->isTeacher() ? 'app-layout' : 'student-layout'" :title="__('Profil Saya')">
    @php
        $cardStyle = 'background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:22px;padding:28px;box-shadow:0 4px 16px rgba(46,44,80,.04)';
        $labelStyle = 'display:block;margin-bottom:6px;font-family:\'Geist\',sans-serif;font-weight:700;font-size:14px;color:#4A5A4E';
        $inputStyle = 'min-height:48px;width:100%;border:1px solid var(--wl-line-3);border-radius:12px;padding:0 16px;font-family:\'Nunito\',sans-serif;font-size:15px;color:var(--wl-body);background:var(--wl-surface);box-sizing:border-box';
        $errStyle = 'margin:6px 0 0;font-weight:700;font-size:13px;color:#C24936';
        $h2Style = 'margin:0 0 6px;font-family:\'Geist\',sans-serif;font-size:20px;font-weight:800;color:var(--wl-ink)';
    @endphp

    <div style="display:flex;flex-direction:column;gap:32px;max-width:820px;margin:0 auto;width:100%">
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
                <a href="#akaun" class="wl-btn-secondary" style="min-height:44px;display:inline-flex;align-items:center;border-radius:12px;border:1.5px solid var(--wl-line-2);background:var(--wl-surface);color:var(--wl-ink);font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;padding:0 18px;flex-shrink:0;text-decoration:none">✏️&nbsp; {{ __('Sunting') }}</a>
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
                ['icon' => '🚀', 'name' => __('Top 10'), 'desc' => __('Capai ranking top 10'), 'got' => $stats['rank'] && $stats['rank'] <= 10, 'tint' => '#EDEDF1', 'ring' => '#B9B8C6', 'ribbon' => '#C9C8D4'],
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

        {{-- Account info form --}}
        <section id="akaun" style="{{ $cardStyle }};scroll-margin-top:24px">
            <h2 style="{{ $h2Style }}">{{ __('Maklumat akaun') }}</h2>
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:18px;margin-top:14px">
                @csrf
                @method('PATCH')
                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                    <x-avatar :user="$user" size="lg" />
                    <div style="flex:1;min-width:200px">
                        <label for="avatar" style="{{ $labelStyle }}">{{ __('Gambar profil') }}</label>
                        <input id="avatar" name="avatar" type="file" accept="image/*" style="{{ $inputStyle }};padding:10px 12px">
                        @error('avatar')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label for="name" style="{{ $labelStyle }}">{{ __('Nama penuh') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required style="{{ $inputStyle }}">
                    @error('name')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="username" style="{{ $labelStyle }}">{{ __('Nama pengguna') }}</label>
                    <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" required style="{{ $inputStyle }}">
                    @error('username')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" style="{{ $labelStyle }}">{{ __('Emel') }} {{ $user->isStudent() ? __('(pilihan)') : '' }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" style="{{ $inputStyle }}">
                    @error('email')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                </div>
                @if ($user->isStudent())
                    <div>
                        <label for="grade_level" style="{{ $labelStyle }}">{{ __('Tahun') }}</label>
                        <select id="grade_level" name="grade_level" style="{{ $inputStyle }};cursor:pointer;-webkit-appearance:none;-moz-appearance:none;appearance:none;background-image:url(&quot;data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20width='24'%20height='24'%20viewBox='0%200%2024%2024'%20fill='none'%20stroke='%232D2F44'%20stroke-width='2.5'%20stroke-linecap='round'%20stroke-linejoin='round'%3E%3Cpath%20d='M6%209l6%206%206-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 14px center;background-size:12px;padding-right:38px">
                            <option value="">{{ __('Sila pilih Tahun') }}</option>
                            @foreach ($grades as $grade)
                                <option value="{{ $grade->level }}" @selected(old('grade_level', $user->grade?->level) == $grade->level)>{{ $grade->name }}</option>
                            @endforeach
                        </select>
                        @error('grade_level')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                    </div>
                @endif
                <button type="submit" class="wl-btn-primary" style="align-self:flex-start;min-height:48px;border:none;cursor:pointer;border-radius:13px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;padding:0 24px">{{ __('Simpan') }}</button>
            </form>
        </section>

        @if ($user->isTeacher())
            <x-youtube-connect-card :user="$user" />
        @endif

        {{-- Change password --}}
        <section style="{{ $cardStyle }}">
            <h2 style="{{ $h2Style }}">{{ __('Tukar kata laluan') }}</h2>
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
        </section>

        {{-- Log out (moved here from the student sidebar) --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" style="display:inline-flex;align-items:center;gap:10px;min-height:48px;border:1.5px solid rgba(194,73,54,.3);cursor:pointer;border-radius:13px;background:var(--wl-surface);color:#C24936;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;padding:0 22px">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                {{ __('Log Keluar') }}
            </button>
        </form>

    </div>
</x-dynamic-component>
