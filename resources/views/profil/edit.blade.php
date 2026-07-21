<x-dynamic-component :component="auth()->user()->isTeacher() ? 'app-layout' : 'student-layout'" :title="__('Profil Saya')">
    @php
        $cardStyle = 'background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:22px;padding:28px;box-shadow:0 4px 16px rgba(46,44,80,.04)';
        $labelStyle = 'display:block;margin-bottom:6px;font-family:\'Geist\',sans-serif;font-weight:700;font-size:14px;color:#4A5A4E';
        $inputStyle = 'min-height:48px;width:100%;border:1px solid var(--wl-line-3);border-radius:12px;padding:0 16px;font-family:\'Nunito\',sans-serif;font-size:15px;color:var(--wl-body);background:var(--wl-surface);box-sizing:border-box';
        $errStyle = 'margin:6px 0 0;font-weight:700;font-size:13px;color:#C24936';
        $h2Style = 'margin:0 0 6px;font-family:\'Geist\',sans-serif;font-size:20px;font-weight:800;color:var(--wl-ink)';
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
                     :style="open ? 'transform:rotate(180deg)' : ''"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div id="akaun-panel" x-show="open" x-cloak>
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:18px;margin-top:14px"
                  @if ($user->isStudent())
                  x-data="{
                      schoolId: '{{ old('school_id', $user->school_id) }}',
                      gradeLevel: '{{ old('grade_level', $user->grade?->level) }}',
                      schoolClass: '{{ old('school_class_id', $user->school_class_id) }}',
                      classes: {{ \Illuminate\Support\Js::from($allClasses) }},
                      gradeMap: {{ \Illuminate\Support\Js::from($grades->pluck('id', 'level')) }},
                      get gradeId() { return this.gradeMap[this.gradeLevel] ?? null; },
                      get availableClasses() { return this.classes.filter(c => String(c.school_id) === String(this.schoolId) && String(c.grade_id) === String(this.gradeId)); },
                      get homeroomName() { const c = this.classes.find(x => String(x.id) === String(this.schoolClass)); return (c && c.homeroom) ? c.homeroom : ''; },
                      onContextChange() { if (this.schoolClass && ! this.availableClasses.some(c => String(c.id) === String(this.schoolClass))) this.schoolClass = ''; },
                  }"
                  @endif>
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
                    <label for="name" style="{{ $labelStyle }}">{{ __('Nama penuh') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required style="{{ $inputStyle }}">
                    @error('name')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="username" style="{{ $labelStyle }}">{{ __('Nama pengguna (nama paparan)') }}</label>
                    <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" required style="{{ $inputStyle }}">
                    <span style="font-size:12.5px;color:var(--tp-muted-2)">{{ __('Nama ini dipaparkan di papan pemuka anda. Anda boleh menukarnya bila-bila masa.') }}</span>
                    @error('username')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                </div>
                {{-- Read-only: email is the sign-in identifier, set by the admin. --}}
                <div>
                    <label for="email" style="{{ $labelStyle }}">{{ __('E-mel (untuk log masuk)') }}</label>
                    <input id="email" type="email" value="{{ $user->email }}" readonly disabled style="{{ $inputStyle }};opacity:.65;cursor:not-allowed">
                    <span style="font-size:12.5px;color:var(--tp-muted-2)">{{ __('Emel log masuk anda tidak boleh diubah. Hubungi pentadbir sekolah jika ia perlu ditukar.') }}</span>
                </div>
                @if ($user->isStudent())
                    @php($selectStyle = $inputStyle.';cursor:pointer;-webkit-appearance:none;-moz-appearance:none;appearance:none;background-image:url(&quot;data:image/svg+xml,%3Csvg%20xmlns=\'http://www.w3.org/2000/svg\'%20width=\'24\'%20height=\'24\'%20viewBox=\'0%200%2024%2024\'%20fill=\'none\'%20stroke=\'%232D2F44\'%20stroke-width=\'2.5\'%20stroke-linecap=\'round\'%20stroke-linejoin=\'round\'%3E%3Cpath%20d=\'M6%209l6%206%206-6\'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 14px center;background-size:12px;padding-right:38px')

                    {{-- School --}}
                    <div>
                        <label for="school_id" style="{{ $labelStyle }}">{{ __('Sekolah') }}</label>
                        <select id="school_id" name="school_id" style="{{ $selectStyle }}" x-model="schoolId" @change="onContextChange()">
                            <option value="">{{ __('Tiada / Belum ditetapkan') }}</option>
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}" @selected((int) old('school_id', $user->school_id) === $school->id)>{{ $school->name }}</option>
                            @endforeach
                        </select>
                        @error('school_id')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                    </div>

                    {{-- Year (Tahun) --}}
                    <div>
                        <label for="grade_level" style="{{ $labelStyle }}">{{ __('Tahun') }}</label>
                        <select id="grade_level" name="grade_level" style="{{ $selectStyle }}" x-model="gradeLevel" @change="onContextChange()">
                            <option value="">{{ __('Sila pilih Tahun') }}</option>
                            @foreach ($grades as $grade)
                                <option value="{{ $grade->level }}" @selected(old('grade_level', $user->grade?->level) == $grade->level)>{{ $grade->name }}</option>
                            @endforeach
                        </select>
                        @error('grade_level')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                    </div>

                    {{-- Class — depends on School + Year --}}
                    <div>
                        <label for="school_class_id" style="{{ $labelStyle }}">{{ __('Kelas') }}</label>
                        <select id="school_class_id" name="school_class_id" style="{{ $selectStyle }}"
                                x-model="schoolClass" x-bind:disabled="! schoolId || ! gradeLevel">
                            <option value="">{{ __('Tiada / Belum ditetapkan') }}</option>
                            <template x-for="c in availableClasses" :key="c.id">
                                <option :value="c.id" x-text="c.label"></option>
                            </template>
                        </select>
                        <p style="margin:6px 0 0;font-size:12.5px;color:var(--wl-muted)" x-show="! schoolId || ! gradeLevel" x-cloak>{{ __('Pilih sekolah dan tahun dahulu.') }}</p>
                        @error('school_class_id')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                    </div>

                    {{-- Homeroom teacher — derived read-only from the chosen class --}}
                    <div>
                        <label style="{{ $labelStyle }}">{{ __('Guru kelas') }}</label>
                        <p style="{{ $inputStyle }};display:flex;align-items:center;background:var(--wl-surface-2, #F1F3F6);color:var(--wl-muted)">
                            <span x-text="homeroomName || '{{ $homeroomTeacher?->name ?? __('Belum ditetapkan') }}'"></span>
                        </p>
                        <p style="margin:6px 0 0;font-size:12.5px;color:var(--wl-muted)">{{ __('Ditentukan oleh kelas yang dipilih.') }}</p>
                    </div>

                    {{-- Guardian details --}}
                    <div>
                        <label for="guardian_name" style="{{ $labelStyle }}">{{ __('Nama penjaga') }}</label>
                        <input id="guardian_name" name="guardian_name" type="text" value="{{ old('guardian_name', $user->guardian_name) }}" style="{{ $inputStyle }}">
                        @error('guardian_name')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="guardian_phone" style="{{ $labelStyle }}">{{ __('Nombor telefon penjaga') }}</label>
                        <input id="guardian_phone" name="guardian_phone" type="tel" value="{{ old('guardian_phone', $user->guardian_phone) }}" placeholder="+60 12-345 6789" style="{{ $inputStyle }}">
                        @error('guardian_phone')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="guardian_email" style="{{ $labelStyle }}">{{ __('E-mel penjaga') }}</label>
                        <input id="guardian_email" name="guardian_email" type="email" value="{{ old('guardian_email', $user->guardian_email) }}" style="{{ $inputStyle }}">
                        @error('guardian_email')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                    </div>
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
                     :style="open ? 'transform:rotate(180deg)' : ''"><path d="M6 9l6 6 6-6"/></svg>
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
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" style="display:inline-flex;align-items:center;gap:10px;min-height:48px;border:1.5px solid rgba(194,73,54,.3);cursor:pointer;border-radius:13px;background:var(--wl-surface);color:#C24936;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;padding:0 22px">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                {{ __('Log Keluar') }}
            </button>
        </form>

    </div>
</x-dynamic-component>
