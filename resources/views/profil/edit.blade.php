<x-dynamic-component :component="auth()->user()->isTeacher() ? 'app-layout' : 'student-layout'" :title="__('Profil Saya')">
    @php
        $cardStyle = 'background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:22px;padding:28px;box-shadow:0 4px 16px rgba(46,44,80,.04)';
        $labelStyle = 'display:block;margin-bottom:6px;font-family:\'Geist\',sans-serif;font-weight:700;font-size:14px;color:#4A5A4E';
        $inputStyle = 'min-height:48px;width:100%;border:1px solid rgba(46,44,80,.15);border-radius:12px;padding:0 16px;font-family:\'Nunito\',sans-serif;font-size:15px;color:#2D2F44;background:#fff;box-sizing:border-box';
        $errStyle = 'margin:6px 0 0;font-weight:700;font-size:13px;color:#C24936';
        $h2Style = 'margin:0 0 6px;font-family:\'Geist\',sans-serif;font-size:20px;font-weight:800;color:#28293F';
    @endphp

    <div style="display:flex;flex-direction:column;gap:22px;max-width:820px;margin:0 auto;width:100%">
        @if ($stats)
            {{-- Header --}}
            <div style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:22px;padding:28px;display:flex;align-items:center;gap:22px;box-shadow:0 8px 24px rgba(46,44,80,.06);flex-wrap:wrap">
                <span style="width:84px;height:84px;border-radius:50%;background:#17907B;color:#fff;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:32px;flex-shrink:0;border:4px solid #DCF2EE;overflow:hidden">
                    @if ($user->avatarUrl())<img src="{{ $user->avatarUrl() }}" alt="" style="width:100%;height:100%;object-fit:cover">@else{{ $user->initials() }}@endif
                </span>
                <div style="display:flex;flex-direction:column;gap:6px;min-width:0;flex:1">
                    <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:24px;font-weight:800;letter-spacing:-.01em;color:#28293F">{{ $user->name }}</h2>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        @if ($user->grade)<span style="background:#E4EEF9;color:#2E6CA8;border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:12px;font-weight:800">{{ $user->grade->name }}</span>@endif
                    </div>
                    @if ($user->email)<span style="font-size:13px;color:#8B8AA3">{{ $user->email }}</span>@endif
                </div>
                <a href="#akaun" class="wl-btn-secondary" style="min-height:44px;display:inline-flex;align-items:center;border-radius:12px;border:1.5px solid rgba(46,44,80,.12);background:#fff;color:#28293F;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;padding:0 18px;flex-shrink:0;text-decoration:none">✏️&nbsp; {{ __('Sunting') }}</a>
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
                ['icon' => '🔥', 'name' => __('Rajin Belajar'), 'desc' => __('5 kuiz selesai'), 'got' => $stats['quizzes'] >= 5],
                ['icon' => '🎯', 'name' => __('Markah Penuh'), 'desc' => __('100% dalam kuiz'), 'got' => $stats['perfect']],
                ['icon' => '🎬', 'name' => __('Penonton Setia'), 'desc' => __('25 video ditonton'), 'got' => $stats['videos'] >= 25],
                ['icon' => '🚀', 'name' => __('Top 10'), 'desc' => __('Capai ranking top 10'), 'got' => $stats['rank'] && $stats['rank'] <= 10],
            ])
            <div style="display:flex;flex-direction:column;gap:12px">
                <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:#28293F">{{ __('Lencana Saya') }}</h3>
                <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px">
                    @foreach ($badges as $b)
                        <div style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:18px;padding:18px 14px;display:flex;flex-direction:column;align-items:center;gap:6px;box-shadow:0 4px 16px rgba(46,44,80,.04);{{ $b['got'] ? '' : 'opacity:.7' }}">
                            <span style="font-size:28px;filter:{{ $b['got'] ? 'none' : 'grayscale(1) opacity(.45)' }}">{{ $b['icon'] }}</span>
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13px;color:#28293F;text-align:center">{{ $b['name'] }}</span>
                            <span style="font-size:11.5px;font-weight:700;color:#8B8AA3;text-align:center">{{ $b['desc'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:24px;font-weight:800;color:#28293F">{{ __('Profil Saya') }}</h2>
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
                        <select id="grade_level" name="grade_level" style="{{ $inputStyle }};cursor:pointer">
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

        {{-- Delete account --}}
        <section style="{{ $cardStyle }};border-color:rgba(194,73,54,.3)">
            <h2 style="{{ $h2Style }};color:#C24936">{{ __('Padam akaun') }}</h2>
            <p style="margin:0 0 14px;font-size:14px;color:#8B8AA3;max-width:60ch">{{ __('Akaun dan semua rekod anda akan dipadam sepenuhnya. Tindakan ini tidak boleh dibatalkan.') }}</p>
            <form method="POST" action="{{ route('profile.destroy') }}" x-data="{ confirming: false }" style="display:flex;flex-direction:column;gap:16px">
                @csrf
                @method('DELETE')
                <button type="button" x-show="! confirming" @click="confirming = true" style="align-self:flex-start;min-height:48px;border:none;cursor:pointer;border-radius:13px;background:#C24936;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;padding:0 22px">🗑 {{ __('Padam Akaun Saya') }}</button>
                <div x-show="confirming" x-cloak style="display:flex;flex-direction:column;gap:14px">
                    <div>
                        <label for="delete_password" style="{{ $labelStyle }}">{{ __('Masukkan kata laluan untuk mengesahkan') }}</label>
                        <input id="delete_password" name="password" type="password" style="{{ $inputStyle }}">
                        @error('password', 'userDeletion')<p style="{{ $errStyle }}">{{ $message }}</p>@enderror
                    </div>
                    <div style="display:flex;gap:12px">
                        <button type="submit" style="min-height:48px;border:none;cursor:pointer;border-radius:13px;background:#C24936;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;padding:0 22px">{{ __('Ya, padam akaun saya') }}</button>
                        <button type="button" @click="confirming = false" class="wl-btn-secondary" style="min-height:48px;cursor:pointer;border-radius:13px;border:1.5px solid rgba(46,44,80,.12);background:#fff;color:#28293F;font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;padding:0 22px">{{ __('Batal') }}</button>
                    </div>
                </div>
            </form>
        </section>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {}); // Alpine present via app.js
        </script>
    @endpush
</x-dynamic-component>
