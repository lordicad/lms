<x-student-layout :title="__('Kegemaran Saya')">
    <div style="display:flex;flex-direction:column;gap:20px">
        <div style="display:flex;align-items:baseline;gap:12px;flex-wrap:wrap">
            <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#28293F">{{ __('Kegemaran Saya') }}</h2>
            <span style="font-size:14px;color:#8B8AA3">{{ $lessons->count() }} video</span>
        </div>

        @if ($lessons->isEmpty())
            <div style="background:#fff;border:1px dashed rgba(46,44,80,.2);border-radius:22px;padding:56px;display:flex;flex-direction:column;align-items:center;gap:10px;text-align:center">
                <span style="font-size:32px">❤️</span>
                <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:#28293F">{{ __('Tiada kegemaran lagi') }}</h3>
                <p style="margin:0;font-size:14.5px;color:#8B8AA3;max-width:360px">{{ __('Klik ikon ♡ pada mana-mana video untuk menyimpannya di sini.') }}</p>
            </div>
        @else
            @foreach ($lessons->groupBy(fn ($l) => $l->chapter->subject->id) as $items)
                @php($sub = $items->first()->chapter->subject)
                @php($tagBg = 'color-mix(in oklab, '.($sub->color ?: '#17907B').' 15%, #fff)')
                <div style="display:flex;flex-direction:column;gap:14px">
                    <div style="display:flex;align-items:center;gap:10px">
                        <span style="width:34px;height:34px;border-radius:10px;background:{{ $tagBg }};display:grid;place-items:center;font-size:15px"><x-subject-emoji :subject="$sub" class="text-base" /></span>
                        <span style="font-family:'Geist',sans-serif;font-size:15px;font-weight:800;color:#28293F">{{ $sub->displayName() }}</span>
                        <span style="font-size:12.5px;font-weight:700;color:#8B8AA3">{{ $items->count() }} video</span>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
                        @foreach ($items as $lesson)
                            <x-vid-card :lesson="$lesson" :thumbHeight="104" />
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</x-student-layout>
