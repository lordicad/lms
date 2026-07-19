@php
    $user = auth()->user();

    // Avatar tint palette, rotated per row — the exact five pairs from the prototype.
    $pal = [['#DCF2EE', '#0F7A68'], ['#E4EEF9', '#2E6CA8'], ['#FBE4ED', '#B84A75'], ['#FEF0CE', '#8A6A12'], ['#FDE7E0', '#C24936']];

    $stats = [
        ['icon' => '👨‍🎓', 'tint' => '#E4EEF9', 'label' => __('Jumlah murid'),  'value' => $totalStudents, 'delta' => $newStudents],
        ['icon' => '🧑‍🏫', 'tint' => '#DCF2EE', 'label' => __('Jumlah cikgu'),  'value' => $totalTeachers, 'delta' => $newTeachers],
        ['icon' => '🎬',   'tint' => '#FEF0CE', 'label' => __('Jumlah video'),  'value' => $totalVideos,   'delta' => $newVideos],
        ['icon' => '📝',   'tint' => '#FBE4ED', 'label' => __('Jumlah kuiz'),   'value' => $totalQuizzes,  'delta' => $newQuizzes],
    ];
@endphp

<x-admin-layout :title="__('Utama')"
                :heading="__('Selamat datang, :name', ['name' => $user->name])"
                :sub="__('Gambaran keseluruhan platform WeLearn pada hari ini')">

    {{-- Stats --}}
    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px">
        @foreach ($stats as $s)
            <div style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:16px;padding:20px 22px;display:flex;flex-direction:column;gap:6px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                <div style="display:flex;align-items:center;gap:10px">
                    <span style="width:40px;height:40px;border-radius:12px;background:{{ $s['tint'] }};display:grid;place-items:center;font-size:17px">{{ $s['icon'] }}</span>
                    <span style="font-size:13.5px;font-weight:700;color:#8B8AA3">{{ $s['label'] }}</span>
                </div>
                <span style="font-family:'Geist',sans-serif;font-size:28px;font-weight:800;color:#28293F">{{ number_format($s['value']) }}</span>
                <span style="font-size:12.5px;font-weight:700;color:#8B8AA3">+{{ number_format($s['delta']) }} {{ __('minggu ini') }}</span>
            </div>
        @endforeach
    </div>

    <div style="display:grid;grid-template-columns:minmax(0,1.5fr) minmax(0,1fr);gap:20px;align-items:start">
        {{-- Recent registrations --}}
        <div style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:18px;box-shadow:0 2px 10px rgba(46,44,80,.04);overflow:hidden">
            <div style="display:flex;align-items:center;gap:12px;padding:18px 22px;border-bottom:1px solid rgba(46,44,80,.07)">
                <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:#28293F;flex:1">{{ __('Pendaftaran Terkini') }}</h2>
                <a href="{{ route('admin.pengguna') }}" class="tp-btn-outline" style="min-height:42px;border-radius:11px;font-size:13px;padding:0 16px;border-width:1.5px">{{ __('Lihat Semua') }}</a>
            </div>

            @forelse ($recentUsers as $i => $u)
                @php($p = $pal[$i % count($pal)])
                <div class="tp-tr" style="display:flex;align-items:center;gap:14px;padding:13px 22px;border-bottom:1px solid rgba(46,44,80,.05)">
                    <span style="width:36px;height:36px;border-radius:10px;background:{{ $p[0] }};color:{{ $p[1] }};display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:12px;flex-shrink:0">{{ $u->initials() }}</span>
                    <div style="display:flex;flex-direction:column;gap:1px;min-width:0;flex:1">
                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:#28293F">{{ $u->name }}</span>
                        <span style="font-size:12px;color:#8B8AA3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $u->isStudent() && $u->grade ? $u->grade->name.' · ' : '' }}{{ $u->email ?? $u->username }}</span>
                    </div>
                    @if ($u->isTeacher())
                        <span style="flex-shrink:0;border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800;background:#DCF2EE;color:#0F7A68">{{ __('Cikgu') }}</span>
                    @else
                        <span style="flex-shrink:0;border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800;background:#E4EEF9;color:#2E6CA8">{{ __('Murid') }}</span>
                    @endif
                    <span style="font-size:12.5px;font-weight:700;color:#8B8AA3;flex-shrink:0">
                        @if ($u->created_at->isToday()) {{ __('Hari ini') }}
                        @elseif ($u->created_at->isYesterday()) {{ __('Semalam') }}
                        @else {{ $u->created_at->translatedFormat('j M') }}
                        @endif
                    </span>
                </div>
            @empty
                <div style="padding:28px 22px;font-size:13.5px;color:#8B8AA3">{{ __('Belum ada pendaftaran.') }}</div>
            @endforelse
        </div>

        {{-- Right column --}}
        <div style="display:flex;flex-direction:column;gap:20px;min-width:0">
            {{-- Platform activity --}}
            <div style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:18px;padding:22px;box-shadow:0 2px 10px rgba(46,44,80,.04);display:flex;flex-direction:column;gap:16px">
                <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:16px;font-weight:800;color:#28293F">📊 {{ __('Aktiviti Platform') }}</h2>
                @foreach ($activity as $a)
                    <div style="display:flex;flex-direction:column;gap:6px">
                        <div style="display:flex;align-items:center;justify-content:space-between">
                            <span style="font-size:13px;font-weight:700;color:#6C6F87">{{ $a['label'] }}</span>
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:#28293F">{{ number_format($a['value']) }}</span>
                        </div>
                        <div style="height:8px;border-radius:999px;background:rgba(46,44,80,.07);overflow:hidden">
                            <div style="height:100%;border-radius:999px;background:{{ $a['color'] }};width:{{ $a['width'] }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            {{-- Pending actions --}}
            <div style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:18px;padding:22px;box-shadow:0 2px 10px rgba(46,44,80,.04);display:flex;flex-direction:column;gap:14px">
                <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:16px;font-weight:800;color:#28293F">⏳ {{ __('Tindakan Menunggu') }}</h2>
                @foreach ($pending as $p)
                    <div style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border-radius:12px;background:{{ $p['bg'] }}">
                        <span style="font-size:15px;flex-shrink:0">{{ $p['icon'] }}</span>
                        <div style="display:flex;flex-direction:column;gap:2px;min-width:0">
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:#28293F">{{ $p['title'] }}</span>
                            <span style="font-size:12.5px;color:#6C6F87;line-height:1.45">{{ $p['desc'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-admin-layout>
