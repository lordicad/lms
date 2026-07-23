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

    <style>
        /* Two columns on a wide screen, one on a narrow one. The side column carries what a
           student reads; the main column carries what they change. */
        .pf-grid { display:grid; grid-template-columns:minmax(0,1.9fr) minmax(0,1fr); gap:22px; align-items:start; }
        .pf-col  { display:flex; flex-direction:column; gap:22px; min-width:0; }
        .pf-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; }
        .pf-badges { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; }
        @media (max-width: 1080px) {
            .pf-grid { grid-template-columns:1fr; }
        }
        @media (max-width: 560px) {
            .pf-stats, .pf-badges { grid-template-columns:repeat(2,minmax(0,1fr)); }
        }
    </style>

    <div style="display:flex;flex-direction:column;gap:22px;width:100%;margin:0 auto">
        <div class="pf-grid">
        <div class="pf-col">
        @if ($stats)
            {{-- Identity and the numbers behind it, in one card: they answer the same question. --}}
            <div style="{{ $cardStyle }};display:flex;flex-direction:column;gap:22px">
                <div style="display:flex;align-items:center;gap:22px;flex-wrap:wrap">
                    <span style="width:84px;height:84px;border-radius:50%;background:#17907B;color:#fff;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:32px;flex-shrink:0;border:4px solid #DCF2EE;overflow:hidden">
                        @if ($user->avatarUrl())<img src="{{ $user->avatarUrl() }}" alt="" style="width:100%;height:100%;object-fit:cover">@else{{ $user->initials() }}@endif
                    </span>
                    <div style="display:flex;flex-direction:column;gap:7px;min-width:0;flex:1">
                        <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:24px;font-weight:800;letter-spacing:-.01em;color:var(--wl-ink)">{{ $user->name }}</h2>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                            @if ($user->grade)<span style="background:#E4EEF9;color:#2E6CA8;border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:12px;font-weight:800">{{ $user->grade->name }}</span>@endif
                            @if ($user->schoolClass)<span style="background:#DCF2EE;color:#0F7A68;border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:12px;font-weight:800">{{ $user->schoolClass->label() }}</span>@endif
                        </div>
                        @if ($user->email)
                            <span style="display:flex;align-items:center;gap:7px;font-size:13px;color:var(--wl-muted)">
                                <x-icon name="inbox" class="h-4 w-4" />{{ $user->email }}
                            </span>
                        @endif
                        <span style="display:flex;align-items:center;gap:7px;font-size:13px;color:var(--wl-muted)">
                            <x-icon name="clock" class="h-4 w-4" />{{ __('Menyertai :date', ['date' => $user->created_at->translatedFormat('F Y')]) }}
                        </span>
                    </div>
                </div>

                <div style="border-top:1px solid var(--wl-line)"></div>

                {{-- Stats --}}
                <div class="pf-stats">
                <div style="background:#DCF2EE;border-radius:18px;padding:18px;display:flex;flex-direction:column;gap:4px">
                    <x-icon name="star" class="h-6 w-6" style="color:#0F7A68" />
                    <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#0F7A68">{{ number_format($stats['points']) }}</span>
                    <span style="font-size:12.5px;font-weight:700;color:#0F7A68">{{ __('Jumlah mata') }}</span>
                </div>
                <div style="background:#FEF0CE;border-radius:18px;padding:18px;display:flex;flex-direction:column;gap:4px">
                    <x-icon name="quiz" class="h-6 w-6" style="color:#8A6A12" />
                    <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#8A6A12">{{ $stats['quizzes'] }}</span>
                    <span style="font-size:12.5px;font-weight:700;color:#8A6A12">{{ __('Kuiz selesai') }}</span>
                </div>
                <div style="background:#E4EEF9;border-radius:18px;padding:18px;display:flex;flex-direction:column;gap:4px">
                    <x-icon name="video" class="h-6 w-6" style="color:#2E6CA8" />
                    <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#2E6CA8">{{ $stats['videos'] }}</span>
                    <span style="font-size:12.5px;font-weight:700;color:#2E6CA8">{{ __('Video ditonton') }}</span>
                </div>
                <div style="background:#FBE4ED;border-radius:18px;padding:18px;display:flex;flex-direction:column;gap:4px">
                    <x-icon name="trophy" class="h-6 w-6" style="color:#B84A75" />
                    <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#B84A75">{{ $stats['rank'] ? '#'.$stats['rank'] : '—' }}</span>
                    <span style="font-size:12.5px;font-weight:700;color:#B84A75">Ranking</span>
                </div>
                </div>
            </div>

            {{-- Badges --}}
            @php($badges = [
                ['icon' => '🔥', 'name' => __('Rajin Belajar'), 'desc' => __('5 kuiz selesai'), 'got' => $stats['quizzes'] >= 5, 'tint' => '#FEF0CE', 'ring' => '#E8A33D', 'ribbon' => '#F3B94C'],
                ['icon' => '🎯', 'name' => __('Markah Penuh'), 'desc' => __('100% dalam kuiz'), 'got' => $stats['perfect'], 'tint' => '#FBE4ED', 'ring' => '#D96A96', 'ribbon' => '#E886AC'],
                ['icon' => '🎬', 'name' => __('Penonton Setia'), 'desc' => __('25 video ditonton'), 'got' => $stats['videos'] >= 25, 'tint' => '#E9E4F9', 'ring' => '#8A6FD0', 'ribbon' => '#A48CE0'],
                ['icon' => '🚀', 'name' => __('10 Teratas'), 'desc' => __('Capai ranking top 10'), 'got' => $stats['rank'] && $stats['rank'] <= 10, 'tint' => '#EDEDF1', 'ring' => '#B9B8C6', 'ribbon' => '#C9C8D4'],
            ])
            <div style="{{ $cardStyle }};display:flex;flex-direction:column;gap:16px">
                <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--wl-ink)">{{ __('Lencana Saya') }}</h3>
                <div class="pf-badges">
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
                        <p style="{{ $noteStyle }}">{{ __('Ditentukan oleh kelas anda.') }}</p>
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

        {{-- Log out lives in the shell chrome now — the student sidebar and the teacher app-layout
             header both carry it — so the page itself no longer repeats it. --}}
        </div>{{-- /.pf-col --}}

        {{-- Side column: what the student reads rather than changes. Both panels show only what
             the app actually records — there is no per-student download log, so downloads are
             absent rather than invented. --}}
        <div class="pf-col">
            @if ($stats)
                <div style="{{ $cardStyle }};display:flex;flex-direction:column;gap:14px">
                    <div style="display:flex;align-items:center;gap:10px">
                        <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--wl-ink)">{{ __('Aktiviti terkini') }}</h3>
                    </div>

                    @if ($activity->isEmpty())
                        <p style="margin:0;font-size:13.5px;color:var(--wl-muted);line-height:1.6">{{ __('Belum ada aktiviti. Tonton video atau jawab kuiz untuk bermula.') }}</p>
                    @else
                        <div style="display:flex;flex-direction:column">
                            @foreach ($activity as $item)
                                <{{ $item['url'] ? 'a' : 'div' }}
                                    @if ($item['url']) href="{{ $item['url'] }}" @endif
                                    style="display:flex;align-items:flex-start;gap:11px;padding:11px 0;text-decoration:none;{{ ! $loop->last ? 'border-bottom:1px solid var(--wl-line)' : '' }}">
                                    <span style="width:36px;height:36px;flex-shrink:0;border-radius:50%;background:{{ $item['tint'] }};color:{{ $item['ink'] }};display:grid;place-items:center">
                                        <x-icon :name="$item['icon']" class="h-4 w-4" />
                                    </span>
                                    <span style="min-width:0;flex:1;display:flex;flex-direction:column;gap:2px">
                                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:var(--wl-ink);word-break:break-word">{{ $item['title'] }}</span>
                                        <span style="font-size:12px;color:var(--wl-muted)">
                                            {{ $item['meta'] }}@if ($item['meta']) · @endif{{ $item['at']->diffForHumans() }}
                                        </span>
                                    </span>
                                    <span style="flex-shrink:0;background:{{ $item['tint'] }};color:{{ $item['ink'] }};border-radius:999px;padding:4px 10px;font-family:'Geist',sans-serif;font-size:11px;font-weight:800">{{ $item['tag'] }}</span>
                                </{{ $item['url'] ? 'a' : 'div' }}>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div style="{{ $cardStyle }};display:flex;flex-direction:column;gap:14px">
                    <div style="display:flex;align-items:center;gap:10px">
                        <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--wl-ink)">{{ __('Untuk anda') }}</h3>
                        @if ($recommended->isNotEmpty())
                            <a href="{{ route('subjek.index') }}" style="margin-left:auto;font-family:'Geist',sans-serif;font-size:13px;font-weight:800">{{ __('Lihat semua') }}</a>
                        @endif
                    </div>
                    <p style="margin:0;font-size:12.5px;color:var(--wl-muted)">{{ __('Video dalam Tahun anda yang belum ditonton.') }}</p>

                    @if ($recommended->isEmpty())
                        <p style="margin:0;font-size:13.5px;color:var(--wl-muted);line-height:1.6">{{ __('Anda sudah menonton semua video untuk Tahun anda. Syabas!') }}</p>
                    @else
                        <div style="display:flex;flex-direction:column;gap:10px">
                            @foreach ($recommended as $lesson)
                                @php($subject = $lesson->chapter->subject)
                                <a href="{{ route('video.show', $lesson) }}" class="wl-row-lift"
                                   style="display:flex;align-items:center;gap:11px;background:var(--wl-surface-2, #FBFAF6);border-radius:14px;padding:10px;text-decoration:none">
                                    <span style="width:64px;height:42px;flex-shrink:0;border-radius:9px;overflow:hidden;background:rgb({{ $subject->rgb }} / .14);display:grid;place-items:center;color:rgb({{ $subject->rgb }})">
                                        @if ($lesson->thumbnailUrl())
                                            <img src="{{ $lesson->thumbnailUrl() }}" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover">
                                        @else
                                            <x-icon name="play" class="h-4 w-4" />
                                        @endif
                                    </span>
                                    <span style="min-width:0;flex:1;display:flex;flex-direction:column;gap:2px">
                                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:var(--wl-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $lesson->title }}</span>
                                        <span style="font-size:12px;color:var(--wl-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $subject->name }} · {{ __('Bab :n', ['n' => $lesson->chapter->number]) }}</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>{{-- /.pf-col --}}
        </div>{{-- /.pf-grid --}}

        @if ($stats)
            {{-- Encouragement, full width under both columns. --}}
            <div style="background:#DCF2EE;border:1px solid rgba(15,122,104,.2);border-radius:22px;padding:22px 26px;display:flex;align-items:center;gap:18px;flex-wrap:wrap">
                <span style="font-size:30px" aria-hidden="true">🚀</span>
                <span style="min-width:0;flex:1;display:flex;flex-direction:column;gap:4px">
                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:16px;color:#0F7A68">{{ __('Teruskan usaha, :name!', ['name' => $user->username ?: $user->name]) }}</span>
                    <span style="font-size:13.5px;color:#0F7A68;line-height:1.55">{{ __('Tonton video dan jawab kuiz untuk mengumpul mata, membuka lencana dan menaiki ranking.') }}</span>
                </span>
                <a href="{{ route('ranking.index') }}" class="wl-btn-primary"
                   style="flex-shrink:0;min-height:46px;display:inline-flex;align-items:center;gap:8px;border-radius:13px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;padding:0 20px;text-decoration:none">
                    <x-icon name="trophy" class="h-4 w-4" />{{ __('Pergi ke Ranking') }}
                </a>
            </div>
        @endif


    </div>
</x-dynamic-component>
