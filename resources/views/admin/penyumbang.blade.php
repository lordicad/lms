<x-admin-layout :title="__('Penyumbang')"
                :heading="__('Ranking Penyumbang')"
                :sub="__('Guru mengikut jumlah kandungan yang dicipta')">

    <div style="display:flex;flex-direction:column;gap:16px">
        <a href="{{ route('admin.dashboard') }}" class="tp-btn-outline" style="align-self:flex-start;min-height:40px;border-radius:11px;font-size:13px;padding:0 14px;border-width:1.5px">← {{ __('Papan Pemuka') }}</a>

        <p style="margin:0;font-size:13px;color:var(--tp-muted)">{{ __('Sumbangan = bilangan Video + Bahan + Kuiz yang dicipta. Seri dipisahkan mengikut Video, Bahan, Kuiz, kemudian nama.') }}</p>

        <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:18px;box-shadow:0 2px 10px rgba(46,44,80,.04);overflow:hidden">
            <div style="overflow-x:auto">
                <div style="min-width:720px">
                    @php($cols = '56px minmax(0,2fr) minmax(0,1.4fr) 1fr 1fr 1fr 1fr')
                    <div style="display:grid;grid-template-columns:{{ $cols }};gap:12px;padding:14px 22px;border-bottom:1px solid var(--tp-line)">
                        @foreach (['#', __('Cikgu'), __('Sekolah'), __('Sumbangan'), __('Video'), __('Bahan'), __('Kuiz')] as $h)
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ $h }}</span>
                        @endforeach
                    </div>

                    @forelse ($contributors as $i => $c)
                        <div style="display:grid;grid-template-columns:{{ $cols }};gap:12px;padding:13px 22px;border-bottom:1px solid var(--tp-line)">
                            <span style="font-family:'Geist',sans-serif;font-weight:800;color:var(--tp-muted-2);font-variant-numeric:tabular-nums">{{ $contributors->firstItem() + $i }}</span>
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $c['name'] }}</span>
                            <span style="font-size:13px;color:var(--tp-muted-2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $c['school'] ?? '—' }}</span>
                            <span style="font-family:'Geist',sans-serif;font-weight:800;color:#17907B">{{ number_format($c['total']) }}</span>
                            <span style="color:var(--tp-muted-2)">{{ $c['videos'] }}</span>
                            <span style="color:var(--tp-muted-2)">{{ $c['materials'] }}</span>
                            <span style="color:var(--tp-muted-2)">{{ $c['quizzes'] }}</span>
                        </div>
                    @empty
                        <div style="padding:28px 22px;font-size:13.5px;color:var(--tp-muted)">{{ __('Belum ada penyumbang.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        @if ($contributors->hasPages())
            <div>{{ $contributors->links() }}</div>
        @endif
    </div>
</x-admin-layout>
