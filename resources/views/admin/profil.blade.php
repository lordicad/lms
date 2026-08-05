<x-admin-layout :title="__('Profil Saya')"
                :heading="__('Profil Saya')"
                heading-icon="user"
                :sub="__('Kemas kini butiran akaun dan kata laluan anda')">
    @php
        $user = auth()->user();
        // Same WeLearn design tokens as the teacher profile — reused here so the two pages match.
        $card = "background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:18px;padding:24px;display:flex;flex-direction:column;box-shadow:0 2px 10px rgba(46,44,80,.04)";
        $h2 = "margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--tp-ink)";
        $label = "font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;color:var(--tp-muted-2)";
        // Trailing semicolon matters: these get concatenated below.
        $input = "min-height:46px;border:1.5px solid var(--tp-line-2);border-radius:12px;padding:0 14px;background:var(--tp-input);font-family:'Nunito',sans-serif;font-size:14.5px;color:var(--tp-ink);box-sizing:border-box;width:100%;";
        $field = "display:flex;flex-direction:column;gap:6px";
        $primary = "align-self:flex-start;min-height:46px;border:none;cursor:pointer;border-radius:12px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;padding:0 22px;display:inline-flex;align-items:center;gap:9px";
        $err = "margin:0;font-size:12.5px;font-weight:700;color:#C24936";
        $note = "font-size:12.5px;color:var(--tp-muted-2)";
        // Save icon reused on both primary buttons.
        $saveIcon = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>';
    @endphp

    <style>
        .wl-primary:hover { background:#2BB39B; }
        .wl-primary:active { transform:scale(.98); }
        .wl-avatarbtn { transition:background .15s; }
        .wl-avatarbtn:hover { background:#2BB39B; }
        .pf-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:18px 24px; align-items:start; }
        .pf-tile  { width:40px; height:40px; border-radius:12px; display:grid; place-items:center; flex-shrink:0; }
        .pf-head  { display:flex; align-items:center; gap:12px; }
        .pf-eye   { position:absolute; right:7px; top:50%; transform:translateY(-50%); width:32px; height:32px; border:none; background:transparent; cursor:pointer; color:var(--tp-muted-2); display:grid; place-items:center; }
        @media (max-width:820px) { .pf-grid2 { grid-template-columns:1fr; } }
    </style>

    <div style="display:flex;flex-direction:column;gap:20px;max-width:1040px">

        {{-- Account details --}}
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" style="{{ $card }};gap:20px">
            @csrf
            @method('PATCH')

            <div class="pf-head">
                <span class="pf-tile" style="background:#DCF2EE;color:#0F7A68"><x-icon name="user" class="h-5 w-5" /></span>
                <h2 style="{{ $h2 }}">{{ __('Butiran akaun') }}</h2>
            </div>

            {{-- Avatar: clicking the circle or the button opens the one hidden input; the chosen
                 image previews live in the circle before saving. --}}
            <div style="display:flex;align-items:center;gap:16px" x-data="{ name: '', preview: '' }">
                <label for="avatar" title="{{ $user->avatarUrl() ? __('Tukar gambar profil') : __('Tambah gambar profil') }}"
                       style="width:64px;height:64px;border-radius:50%;background:#DCF2EE;color:#0F7A68;display:flex;align-items:center;justify-content:center;font-family:'Geist',sans-serif;font-weight:800;font-size:20px;line-height:1;flex-shrink:0;overflow:hidden;cursor:pointer;position:relative">
                    <span x-show="! preview">{{ $user->initials() }}</span>
                    @if ($user->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}" alt="" x-show="! preview" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">
                    @endif
                    <template x-if="preview">
                        <img :src="preview" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">
                    </template>
                </label>

                <div style="display:flex;flex-direction:column;gap:6px;min-width:0">
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                        <label for="avatar" class="wl-avatarbtn tp-g" style="display:inline-flex;align-items:center;gap:7px;min-height:38px;border-radius:10px;background:#17907B;color:#fff;font-weight:800;font-size:13px;padding:0 16px;cursor:pointer">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 20h16"/></svg>
                            {{ $user->avatarUrl() ? __('Tukar gambar') : __('Tambah gambar') }}
                        </label>
                        <span style="font-size:13px;color:var(--tp-muted)" x-text="name || '{{ __('Tiada fail dipilih') }}'"></span>
                    </div>
                    <span style="{{ $note }}">{{ __('JPG atau PNG. Klik bulatan atau butang untuk pilih gambar.') }}</span>
                </div>

                <input type="file" id="avatar" name="avatar" accept="image/*" class="sr-only"
                       x-on:change="name = $event.target.files[0]?.name || ''; preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : ''"
                       @error('avatar') aria-invalid="true" @enderror>
            </div>
            @error('avatar')<p style="{{ $err }}">{{ $message }}</p>@enderror

            <div style="border-top:1px solid var(--tp-line)"></div>

            <div class="pf-grid2">
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
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" style="{{ $input }}">
                    @error('email')<p style="{{ $err }}">{{ $message }}</p>@enderror
                </div>
            </div>

            <button type="submit" class="wl-primary" style="{{ $primary }}">{!! $saveIcon !!} {{ __('Simpan') }}</button>
        </form>

        {{-- Change password --}}
        <form method="POST" action="{{ route('password.update') }}" style="{{ $card }};gap:16px">
            @csrf
            @method('PUT')
            <div class="pf-head">
                <span class="pf-tile" style="background:#DCF2EE;color:#0F7A68"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                <h2 style="{{ $h2 }}">{{ __('Tukar kata laluan') }}</h2>
            </div>

            @foreach ([
                ['id' => 'current_password', 'label' => __('Kata laluan semasa'), 'ac' => 'current-password'],
                ['id' => 'password', 'label' => __('Kata laluan baharu'), 'ac' => 'new-password'],
                ['id' => 'password_confirmation', 'label' => __('Ulang kata laluan baharu'), 'ac' => 'new-password'],
            ] as $pw)
                <div style="{{ $field }}" x-data="{ show: false }">
                    <label for="{{ $pw['id'] }}" style="{{ $label }}">{{ $pw['label'] }}</label>
                    <div style="position:relative">
                        <input id="{{ $pw['id'] }}" name="{{ $pw['id'] }}" :type="show ? 'text' : 'password'" autocomplete="{{ $pw['ac'] }}" style="{{ $input }};padding-right:46px">
                        <button type="button" class="pf-eye" @click="show = ! show" :aria-label="show ? '{{ __('Sembunyikan') }}' : '{{ __('Papar') }}'">
                            <span x-show="! show"><x-icon name="eye" class="h-[18px] w-[18px]" /></span>
                            <span x-show="show" x-cloak><x-icon name="eye-off" class="h-[18px] w-[18px]" /></span>
                        </button>
                    </div>
                    @error($pw['id'], 'updatePassword')<p style="{{ $err }}">{{ $message }}</p>@enderror
                </div>
            @endforeach

            <button type="submit" class="wl-primary" style="{{ $primary }}"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> {{ __('Tukar Kata Laluan') }}</button>
        </form>
    </div>
</x-admin-layout>
