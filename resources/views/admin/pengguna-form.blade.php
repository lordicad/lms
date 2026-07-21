@php($editing = $user->exists)

<x-admin-layout :title="$editing ? __('Sunting Pengguna') : __('Pengguna Baharu')"
                :heading="$editing ? __('Sunting Pengguna') : __('Pengguna Baharu')"
                :sub="__('Akaun cikgu atau murid')">

    <style>
        .pg-form [x-cloak] { display:none !important; }
        .role-opt { display:flex;align-items:center;justify-content:center;gap:8px;min-height:50px;border:1.5px solid var(--tp-line-2);border-radius:12px;background:var(--tp-surface);cursor:pointer;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:var(--tp-ink); }
        .role-opt.is-on { border-color:#17907B;background:#E6F5F1;color:#0F7A68; }
        .pg-err { margin:0;font-size:12.5px;font-weight:700;color:#C24936; }
    </style>

    <div class="pg-form" style="max-width:640px;margin-left:auto;margin-right:auto;width:100%"
         x-data="{
             role: '{{ old('role', $user->role ?: 'teacher') }}',
             schoolId: '{{ old('school_id', $user->school_id) }}',
             gradeLevel: '{{ old('grade_level', $user->grade?->level) }}',
             schoolClass: '{{ old('school_class_id', $user->school_class_id) }}',
             autoPassword: {{ old('auto_password', $editing ? 0 : 1) ? 'true' : 'false' }},
             classes: {{ \Illuminate\Support\Js::from($allClasses) }},
             gradeMap: {{ \Illuminate\Support\Js::from($grades->pluck('id', 'level')) }},
             get gradeId() { return this.gradeMap[this.gradeLevel] ?? null; },
             get availableClasses() { return this.classes.filter(c => String(c.school_id) === String(this.schoolId) && String(c.grade_id) === String(this.gradeId)); },
             onContextChange() { if (this.schoolClass && ! this.availableClasses.some(c => String(c.id) === String(this.schoolClass))) this.schoolClass = ''; },
         }">
        <a href="{{ route('admin.pengguna') }}" style="display:inline-flex;align-items:center;gap:6px;font-family:'Geist',sans-serif;font-size:13.5px;font-weight:800;color:var(--tp-muted-2);text-decoration:none;margin-bottom:16px">← {{ __('Semua pengguna') }}</a>

        <form method="POST" action="{{ $editing ? route('admin.pengguna.update', $user) : route('admin.pengguna.store') }}"
              style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:18px;padding:24px;display:flex;flex-direction:column;gap:16px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
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
                <label for="username" class="tp-label">{{ __('Nama pengguna (nama paparan)') }}</label>
                <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" required class="tp-input">
                {{-- Everyone signs in with their email, so this is purely a display name. --}}
                <span class="tp-hint">{{ __('Dipaparkan di papan pemuka mereka, dan boleh ditukar sendiri. Log masuk menggunakan emel, bukan nama ini.') }}</span>
                @error('username')<p class="pg-err">{{ $message }}</p>@enderror
            </div>

            <div class="tp-field">
                {{-- Required for both roles now: this is what the account signs in with. --}}
                <label for="email" class="tp-label">
                    {{ __('Emel') }}
                    <span style="font-weight:600;color:var(--tp-muted)">({{ __('untuk log masuk') }})</span>
                </label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="tp-input">
                <span class="tp-hint">{{ __('Pengguna log masuk dengan emel ini dan tidak boleh menukarnya sendiri.') }}</span>
                @error('email')<p class="pg-err">{{ $message }}</p>@enderror
            </div>

            {{-- School — both roles --}}
            <div class="tp-field">
                <label for="school_id" class="tp-label">{{ __('Sekolah') }}</label>
                <select id="school_id" name="school_id" class="tp-filter-select" style="width:100%" x-model="schoolId" @change="onContextChange()">
                    <option value="">{{ __('Tiada / Belum ditetapkan') }}</option>
                    @foreach ($schools as $school)
                        <option value="{{ $school->id }}" @selected((int) old('school_id', $user->school_id) === $school->id)>{{ $school->name }}</option>
                    @endforeach
                </select>
                @error('school_id')<p class="pg-err">{{ $message }}</p>@enderror
            </div>

            {{-- Teacher: phone, position, subjects --}}
            <template x-if="role === 'teacher'">
                <div style="display:flex;flex-direction:column;gap:16px">
                    <div class="tp-field">
                        <label for="phone" class="tp-label">{{ __('Nombor telefon') }}</label>
                        <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" class="tp-input">
                        @error('phone')<p class="pg-err">{{ $message }}</p>@enderror
                    </div>
                    <div class="tp-field">
                        <label for="position" class="tp-label">{{ __('Jawatan') }}</label>
                        <input id="position" name="position" type="text" value="{{ old('position', $user->position) }}" class="tp-input">
                        @error('position')<p class="pg-err">{{ $message }}</p>@enderror
                    </div>
                    <fieldset class="tp-field" style="border:1.5px solid var(--tp-line-2);border-radius:12px;padding:12px 14px">
                        <legend class="tp-label" style="padding:0 6px">{{ __('Subjek diajar') }}</legend>
                        <div style="display:flex;flex-wrap:wrap;gap:8px 16px;margin-top:6px">
                            @php($chosen = old('subjects', $user->exists ? $user->subjects->pluck('id')->all() : []))
                            @foreach ($subjects as $subject)
                                <label style="display:flex;align-items:center;gap:7px;font-size:13.5px;color:var(--tp-ink);cursor:pointer">
                                    <input type="checkbox" name="subjects[]" value="{{ $subject->id }}" @checked(in_array($subject->id, $chosen)) style="width:17px;height:17px;accent-color:#17907B">
                                    {{ $subject->displayName() }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                </div>
            </template>

            {{-- Tahun — students only --}}
            <div class="tp-field" x-show="role === 'student'" x-cloak>
                <label for="grade_level" class="tp-label">{{ __('Tahun') }}</label>
                <select id="grade_level" name="grade_level" class="tp-filter-select" style="width:100%" x-model="gradeLevel" @change="onContextChange()">
                    <option value="">{{ __('Pilih Tahun') }}</option>
                    @foreach ($grades as $grade)
                        <option value="{{ $grade->level }}" @selected(old('grade_level', $user->grade?->level) == $grade->level)>{{ $grade->name }}</option>
                    @endforeach
                </select>
                @error('grade_level')<p class="pg-err">{{ $message }}</p>@enderror
            </div>

            {{-- Student: class + guardian --}}
            <template x-if="role === 'student'">
                <div style="display:flex;flex-direction:column;gap:16px">
                    <div class="tp-field">
                        <label for="school_class_id" class="tp-label">{{ __('Kelas') }}</label>
                        <select id="school_class_id" name="school_class_id" class="tp-filter-select" style="width:100%" x-model="schoolClass" x-bind:disabled="! schoolId || ! gradeLevel">
                            <option value="">{{ __('Tiada / Belum ditetapkan') }}</option>
                            <template x-for="c in availableClasses" :key="c.id">
                                <option :value="c.id" x-text="c.label"></option>
                            </template>
                        </select>
                        @error('school_class_id')<p class="pg-err">{{ $message }}</p>@enderror
                    </div>
                    <div class="tp-field">
                        <label for="guardian_name" class="tp-label">{{ __('Nama penjaga') }}</label>
                        <input id="guardian_name" name="guardian_name" type="text" value="{{ old('guardian_name', $user->guardian_name) }}" class="tp-input">
                        @error('guardian_name')<p class="pg-err">{{ $message }}</p>@enderror
                    </div>
                    <div class="tp-field">
                        <label for="guardian_phone" class="tp-label">{{ __('Telefon penjaga') }}</label>
                        <input id="guardian_phone" name="guardian_phone" type="tel" value="{{ old('guardian_phone', $user->guardian_phone) }}" class="tp-input">
                        @error('guardian_phone')<p class="pg-err">{{ $message }}</p>@enderror
                    </div>
                    <div class="tp-field">
                        <label for="guardian_email" class="tp-label">{{ __('E-mel penjaga') }}</label>
                        <input id="guardian_email" name="guardian_email" type="email" value="{{ old('guardian_email', $user->guardian_email) }}" class="tp-input">
                        @error('guardian_email')<p class="pg-err">{{ $message }}</p>@enderror
                    </div>
                </div>
            </template>

            <div style="height:1px;background:var(--tp-line);margin:2px 0"></div>

            {{-- Password status. The password itself is stored as a one-way hash and cannot be read
                 back by anyone, so what is shown is whether the owner has chosen their own yet. --}}
            @if ($editing)
                @php($ownsPassword = $user->password_changed_at !== null)
                <div style="border-radius:12px;padding:12px 14px;display:flex;flex-direction:column;gap:3px;{{ $ownsPassword ? 'background:#DCF2EE;border:1px solid rgba(15,122,104,.25)' : 'background:#FEF0CE;border:1px solid rgba(138,106,18,.25)' }}">
                    <span style="font-family:'Geist',sans-serif;font-size:13px;font-weight:800;color:{{ $ownsPassword ? '#0F7A68' : '#8A6A12' }}">
                        {{ $ownsPassword ? '✓ '.__('Kata laluan sendiri') : '⏳ '.__('Masih guna kata laluan yang anda beri') }}
                    </span>
                    <span style="font-size:12.5px;color:var(--tp-muted-2)">
                        @if ($ownsPassword)
                            {{ __('Ditetapkan sendiri oleh pengguna pada :date.', ['date' => $user->password_changed_at->translatedFormat('j F Y, g:ia')]) }}
                        @else
                            {{ __('Pengguna akan diminta menetapkan kata laluan sendiri pada log masuk seterusnya.') }}
                        @endif
                    </span>
                </div>
            @endif

            {{-- Auto-generate is the default for a new account: it saves the admin inventing one,
                 and the result is readable enough to pass on by phone or on paper. --}}
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                <input type="checkbox" name="auto_password" value="1" x-model="autoPassword"
                       style="width:20px;height:20px;accent-color:#17907B">
                <span style="font-family:'Geist',sans-serif;font-size:13.5px;font-weight:700;color:var(--tp-ink)">
                    {{ $editing ? __('Jana kata laluan baharu secara automatik') : __('Jana kata laluan secara automatik') }}
                </span>
            </label>

            <p class="tp-hint" x-show="autoPassword" style="margin:-6px 0 0">
                {{ __('Kata laluan akan dijana dan dipaparkan sekali selepas disimpan, serta dihantar kepada pengguna atau penjaga.') }}
            </p>

            <div class="tp-field" x-show="! autoPassword" x-cloak>
                <label for="password" class="tp-label">{{ $editing ? __('Set semula kata laluan') : __('Kata laluan') }}</label>
                <input id="password" name="password" type="password" class="tp-input" autocomplete="new-password">
                @if ($editing)<span class="tp-hint">{{ __('Biarkan kosong untuk mengekalkan kata laluan semasa. Jika diisi, pengguna perlu menetapkan kata laluan sendiri semula pada log masuk seterusnya.') }}</span>@endif
                @error('password')<p class="pg-err">{{ $message }}</p>@enderror
            </div>

            <div class="tp-field" x-show="! autoPassword" x-cloak>
                <label for="password_confirmation" class="tp-label">{{ __('Sahkan kata laluan') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="tp-input" autocomplete="new-password">
            </div>

            <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true)) style="width:20px;height:20px;accent-color:#17907B">
                <span style="font-family:'Geist',sans-serif;font-size:13.5px;font-weight:700;color:var(--tp-ink)">{{ __('Akaun aktif (boleh log masuk)') }}</span>
            </label>

            <div style="display:flex;gap:12px;margin-top:4px">
                <button type="submit" class="tp-btn" style="min-height:48px">{{ $editing ? __('Simpan Perubahan') : __('Cipta Akaun') }}</button>
                <a href="{{ route('admin.pengguna') }}" class="tp-btn-outline" style="min-height:48px">{{ __('Batal') }}</a>
            </div>
        </form>
    </div>
</x-admin-layout>
