<x-admin-layout :title="__('Pengurusan Pengguna')"
                :heading="__('Pengurusan Pengguna')"
                :sub="__('Cipta, sunting dan urus akaun cikgu dan murid')">

    {{-- When the admin filters to teachers, the "Year" column (a student attribute) becomes "Position". --}}
    @php($teacherView = $role === 'teacher')
    @php($cols = 'grid-template-columns:minmax(150px,1.7fr) 96px minmax(150px,1.5fr) '.($teacherView ? '130px' : '90px').' 118px 96px')

    {{-- The success banner is rendered once by <x-flash /> in the admin layout. --}}
    <div style="display:flex;flex-direction:column;gap:18px">

        {{-- Summary chips --}}
        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px">
            <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:14px;padding:16px 18px;display:flex;flex-direction:column;gap:4px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                <span style="font-size:12.5px;font-weight:700;color:#2E6CA8">🧑‍🏫 {{ __('Cikgu') }}</span>
                <span style="font-family:'Geist',sans-serif;font-size:24px;font-weight:800;color:var(--tp-ink)">{{ number_format($counts['teacher']) }}</span>
            </div>
            <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:14px;padding:16px 18px;display:flex;flex-direction:column;gap:4px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                <span style="font-size:12.5px;font-weight:700;color:#0F7A68">🧑‍🎓 {{ __('Murid') }}</span>
                <span style="font-family:'Geist',sans-serif;font-size:24px;font-weight:800;color:var(--tp-ink)">{{ number_format($counts['student']) }}</span>
            </div>
            <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:14px;padding:16px 18px;display:flex;flex-direction:column;gap:4px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                <span style="font-size:12.5px;font-weight:700;color:#C24936">✕ {{ __('Tidak aktif') }}</span>
                <span style="font-family:'Geist',sans-serif;font-size:24px;font-weight:800;color:var(--tp-ink)">{{ number_format($counts['inactive']) }}</span>
            </div>
        </div>

        {{-- Toolbar: search + filters + add --}}
        <div style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap">
            <form method="GET" action="{{ route('admin.pengguna') }}" style="display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap;flex:1;min-width:0">
                <div class="tp-field" style="flex:1;min-width:200px">
                    <label class="tp-label">{{ __('Cari') }}</label>
                    <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('Nama, nama pengguna atau emel') }}" class="tp-input">
                </div>
                <div class="tp-field">
                    <label class="tp-label">{{ __('Peranan') }}</label>
                    <select name="role" class="tp-filter-select" style="min-width:140px" onchange="this.form.submit()">
                        <option value="">{{ __('Semua peranan') }}</option>
                        <option value="teacher" @selected($role === 'teacher')>{{ __('Cikgu') }}</option>
                        <option value="student" @selected($role === 'student')>{{ __('Murid') }}</option>
                    </select>
                </div>
                <div class="tp-field">
                    <label class="tp-label">{{ __('Status') }}</label>
                    <select name="status" class="tp-filter-select" style="min-width:140px" onchange="this.form.submit()">
                        <option value="">{{ __('Semua status') }}</option>
                        <option value="active" @selected($status === 'active')>{{ __('Aktif') }}</option>
                        <option value="inactive" @selected($status === 'inactive')>{{ __('Tidak aktif') }}</option>
                    </select>
                </div>
            </form>
            <a href="{{ route('admin.pengguna.create') }}" class="tp-btn" style="min-height:46px;white-space:nowrap">+ {{ __('Tambah Pengguna') }}</a>
        </div>

        @if ($users->isEmpty())
            <div class="tp-empty">
                <span style="font-size:30px">👥</span>
                <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Tiada pengguna') }}</h3>
                <p style="margin:0;font-size:14.5px;color:var(--tp-muted);max-width:380px">{{ __('Tiada akaun yang sepadan. Laraskan carian anda atau tambah pengguna baharu.') }}</p>
            </div>
        @else
            <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:18px;overflow:hidden;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                <div style="overflow-x:auto">
                    <div style="min-width:820px">
                        <div style="display:grid;{{ $cols }};gap:12px;padding:14px 20px;border-bottom:1px solid var(--tp-line)">
                            {{-- Everything but Name is centred; names keep a common left edge to scan down. --}}
                            @foreach (['Nama', 'Peranan', 'Emel', $teacherView ? 'Jawatan' : 'Tahun', 'Status', 'Tindakan'] as $h)
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted){{ $loop->first ? '' : ';text-align:center' }}">{{ __($h) }}</span>
                            @endforeach
                        </div>

                        @foreach ($users as $u)
                            <div class="tp-tr" style="display:grid;{{ $cols }};gap:12px;align-items:center;padding:12px 20px;border-bottom:1px solid var(--tp-line)">
                                <div style="display:flex;flex-direction:column;gap:1px;min-width:0">
                                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $u->name }}</span>
                                    <span style="font-size:11.5px;color:var(--tp-muted)">{{ '@'.$u->username }}</span>
                                </div>

                                @if ($u->isTeacher())
                                    <span style="justify-self:center;background:#E4EEF9;color:#2E6CA8;border-radius:999px;padding:4px 11px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800">{{ __('Cikgu') }}</span>
                                @else
                                    <span style="justify-self:center;background:#DCF2EE;color:#0F7A68;border-radius:999px;padding:4px 11px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800">{{ __('Murid') }}</span>
                                @endif

                                <span style="font-size:13px;font-weight:600;color:var(--tp-muted-2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-align:center">{{ $u->email ?: '—' }}</span>
                                <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-align:center">{{ $teacherView ? ($u->position ?: '—') : ($u->grade?->name ?? '—') }}</span>

                                {{-- Status badge doubles as an activate/deactivate toggle. The pill
                                     carries a background, so it centres by justify-self rather than
                                     text-align — otherwise it would stretch the whole column. --}}
                                <form method="POST" action="{{ route('admin.pengguna.status', $u) }}" style="justify-self:center">
                                    @csrf
                                    <button type="submit" title="{{ $u->is_active ? __('Klik untuk nyahaktifkan') : __('Klik untuk aktifkan') }}"
                                            style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;border:none;border-radius:999px;padding:5px 11px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800;{{ $u->is_active ? 'background:#DCF2EE;color:#0F7A68' : 'background:#F1F0E8;color:var(--tp-muted)' }}">
                                        <span style="width:7px;height:7px;border-radius:50%;background:{{ $u->is_active ? '#17907B' : '#B9B8C6' }}"></span>
                                        {{ $u->is_active ? __('Aktif') : __('Tidak aktif') }}
                                    </button>
                                </form>

                                <div style="display:flex;align-items:center;gap:6px;justify-self:center">
                                    <a href="{{ route('admin.pengguna.edit', $u) }}" title="{{ __('Sunting') }}"
                                       style="width:34px;height:34px;border-radius:9px;border:1.5px solid var(--tp-line-2);background:var(--tp-surface);display:grid;place-items:center;color:#4A5A6B;text-decoration:none">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.pengguna.destroy', $u) }}"
                                          onsubmit='return confirm(@js(__("Padam akaun \":name\"? Tindakan ini kekal. Jika ini akaun cikgu, semua video, bahan dan kuiz mereka turut dipadam.", ["name" => $u->name])))'>
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="{{ __('Padam') }}"
                                                style="width:34px;height:34px;border-radius:9px;border:1.5px solid rgba(194,73,54,.25);background:var(--tp-surface);cursor:pointer;display:grid;place-items:center;color:#C24936">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div>{{ $users->links() }}</div>
        @endif
    </div>
</x-admin-layout>
