@php($editing = $user->exists)

<x-admin-layout :title="$editing ? __('Sunting Pengguna') : __('Pengguna Baharu')"
                :heading="$editing ? __('Sunting Pengguna') : __('Pengguna Baharu')"
                :sub="__('Akaun cikgu atau murid')">

    <style>
        .pg-form [x-cloak] { display:none !important; }
        .role-opt { display:flex;align-items:center;justify-content:center;gap:8px;min-height:50px;border:1.5px solid rgba(46,44,80,.12);border-radius:12px;background:#fff;cursor:pointer;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:#28293F; }
        .role-opt.is-on { border-color:#17907B;background:#E6F5F1;color:#0F7A68; }
        .pg-err { margin:0;font-size:12.5px;font-weight:700;color:#C24936; }
    </style>

    <div class="pg-form" x-data="{ role: '{{ old('role', $user->role ?: 'teacher') }}' }" style="max-width:640px">
        <a href="{{ route('admin.pengguna') }}" style="display:inline-flex;align-items:center;gap:6px;font-family:'Geist',sans-serif;font-size:13.5px;font-weight:800;color:#6C6F87;text-decoration:none;margin-bottom:16px">← {{ __('Semua pengguna') }}</a>

        <form method="POST" action="{{ $editing ? route('admin.pengguna.update', $user) : route('admin.pengguna.store') }}"
              style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:18px;padding:24px;display:flex;flex-direction:column;gap:16px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
            @csrf
            @if ($editing) @method('PUT') @endif

            {{-- Role --}}
            <div class="tp-field">
                <label class="tp-label">{{ __('Peranan') }}</label>
                @if ($editing)
                    <span style="align-self:flex-start;border-radius:999px;padding:6px 14px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;{{ $user->isTeacher() ? 'background:#E4EEF9;color:#2E6CA8' : 'background:#DCF2EE;color:#0F7A68' }}">{{ $user->isTeacher() ? __('Cikgu') : __('Murid') }}</span>
                    <span class="tp-hint">{{ __('Peranan tidak boleh ditukar selepas akaun dicipta.') }}</span>
                @else
                    <input type="hidden" name="role" :value="role">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <button type="button" class="role-opt" :class="{ 'is-on': role === 'teacher' }" @click="role = 'teacher'">🧑‍🏫 {{ __('Cikgu') }}</button>
                        <button type="button" class="role-opt" :class="{ 'is-on': role === 'student' }" @click="role = 'student'">🧑‍🎓 {{ __('Murid') }}</button>
                    </div>
                @endif
            </div>

            <div class="tp-field">
                <label for="name" class="tp-label">{{ __('Nama penuh') }}</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="tp-input">
                @error('name')<p class="pg-err">{{ $message }}</p>@enderror
            </div>

            <div class="tp-field">
                <label for="username" class="tp-label">{{ __('Nama pengguna') }}</label>
                <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" required class="tp-input">
                @error('username')<p class="pg-err">{{ $message }}</p>@enderror
            </div>

            <div class="tp-field">
                <label for="email" class="tp-label">
                    {{ __('Emel') }}
                    <span x-show="role === 'teacher'" style="font-weight:600;color:#8B8AA3">({{ __('diperlukan') }})</span>
                    <span x-show="role === 'student'" x-cloak style="font-weight:600;color:#8B8AA3">({{ __('pilihan') }})</span>
                </label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="tp-input">
                @error('email')<p class="pg-err">{{ $message }}</p>@enderror
            </div>

            {{-- Tahun — students only --}}
            <div class="tp-field" x-show="role === 'student'" x-cloak>
                <label for="grade_level" class="tp-label">{{ __('Tahun') }}</label>
                <select id="grade_level" name="grade_level" class="tp-filter-select" style="width:100%">
                    <option value="">{{ __('Pilih Tahun') }}</option>
                    @foreach ($grades as $grade)
                        <option value="{{ $grade->level }}" @selected(old('grade_level', $user->grade?->level) == $grade->level)>{{ $grade->name }}</option>
                    @endforeach
                </select>
                @error('grade_level')<p class="pg-err">{{ $message }}</p>@enderror
            </div>

            <div style="height:1px;background:rgba(46,44,80,.08);margin:2px 0"></div>

            <div class="tp-field">
                <label for="password" class="tp-label">{{ __('Kata laluan') }}</label>
                <input id="password" name="password" type="password" class="tp-input" autocomplete="new-password" @unless ($editing) required @endunless>
                @if ($editing)<span class="tp-hint">{{ __('Biarkan kosong untuk mengekalkan kata laluan semasa.') }}</span>@endif
                @error('password')<p class="pg-err">{{ $message }}</p>@enderror
            </div>

            <div class="tp-field">
                <label for="password_confirmation" class="tp-label">{{ __('Sahkan kata laluan') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="tp-input" autocomplete="new-password">
            </div>

            <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true)) style="width:20px;height:20px;accent-color:#17907B">
                <span style="font-family:'Geist',sans-serif;font-size:13.5px;font-weight:700;color:#28293F">{{ __('Akaun aktif (boleh log masuk)') }}</span>
            </label>

            <div style="display:flex;gap:12px;margin-top:4px">
                <button type="submit" class="tp-btn" style="min-height:48px">{{ $editing ? __('Simpan Perubahan') : __('Cipta Akaun') }}</button>
                <a href="{{ route('admin.pengguna') }}" class="tp-btn-outline" style="min-height:48px">{{ __('Batal') }}</a>
            </div>
        </form>
    </div>
</x-admin-layout>
