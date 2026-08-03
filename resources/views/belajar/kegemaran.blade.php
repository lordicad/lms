<x-student-layout :title="__('Kegemaran Saya')">
    <div style="display:flex;flex-direction:column;gap:20px">
        <div style="display:flex;align-items:flex-start;gap:16px">
            <span style="width:48px;height:48px;border-radius:14px;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center;flex-shrink:0"><x-icon name="heart" style="width:24px;height:24px" /></span>
            <div style="display:flex;flex-direction:column;gap:2px;min-width:0">
                <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:22px;font-weight:800;letter-spacing:-.01em;color:var(--wl-ink)">{{ __('Kegemaran Saya') }}</h2>
                <span style="font-size:14px;font-weight:600;color:var(--wl-muted)">{{ $lessons->count() }} video</span>
            </div>
        </div>

        @if ($lessons->isEmpty())
            <div style="background:var(--wl-surface);border:1px dashed var(--wl-line-3);border-radius:22px;padding:56px;display:flex;flex-direction:column;align-items:center;gap:10px;text-align:center">
                <span style="font-size:32px">❤️</span>
                <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:var(--wl-ink)">{{ __('Tiada kegemaran lagi') }}</h3>
                <p style="margin:0;font-size:14.5px;color:var(--wl-muted);max-width:360px">{{ __('Klik ikon ♡ pada mana-mana video untuk menyimpannya di sini.') }}</p>
            </div>
        @else
            @foreach ($lessons->groupBy(fn ($l) => $l->chapter->subject->id) as $items)
                @php($sub = $items->first()->chapter->subject)
                @php($tagBg = 'color-mix(in oklab, '.($sub->color ?: '#17907B').' var(--pill-bw), var(--pill-bb))')
                <div style="display:flex;flex-direction:column;gap:14px">
                    <div style="display:flex;align-items:center;gap:10px">
                        <span style="width:34px;height:34px;border-radius:10px;background:{{ $tagBg }};display:grid;place-items:center;font-size:15px"><x-subject-emoji :subject="$sub" class="text-base" /></span>
                        <span style="font-family:'Geist',sans-serif;font-size:15px;font-weight:800;color:var(--wl-ink)">{{ $sub->displayName() }}</span>
                        <span style="font-size:12.5px;font-weight:700;color:var(--wl-muted)">{{ $items->count() }} video</span>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
                        @foreach ($items as $lesson)
                            <x-vid-card :lesson="$lesson" :thumbHeight="104" :showViews="true" />
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</x-student-layout>
