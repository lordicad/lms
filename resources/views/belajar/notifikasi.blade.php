<x-student-layout :title="__('Notifikasi')">
    @php
        // Per-type presentation: icon + tint + a message template with :actor (the teacher) and :title.
        $meta = [
            \App\Models\ContentNotification::TYPE_VIDEO    => ['icon' => 'video', 'tint' => '#E4EEF9', 'fg' => '#2E6CA8', 'text' => __(':actor memuat naik video ":title"')],
            \App\Models\ContentNotification::TYPE_MATERIAL => ['icon' => 'file',  'tint' => '#DCF2EE', 'fg' => '#0F7A68', 'text' => __(':actor memuat naik bahan ":title"')],
            \App\Models\ContentNotification::TYPE_QUIZ     => ['icon' => 'quiz',  'tint' => '#FEF0CE', 'fg' => '#8A6A12', 'text' => __(':actor menambah kuiz ":title"')],
        ];
    @endphp

    <div style="display:flex;flex-direction:column;gap:24px">
        <div style="display:flex;align-items:flex-start;gap:16px">
            <span class="hi-tile" style="width:48px;height:48px;border-radius:14px;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center;flex-shrink:0"><x-icon name="bell" style="width:24px;height:24px" /></span>
            <div style="display:flex;flex-direction:column;gap:2px;min-width:0">
                <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:22px;font-weight:800;letter-spacing:-.01em;color:var(--wl-ink)">{{ __('Notifikasi') }}</h2>
                <span style="font-size:14px;font-weight:600;color:var(--wl-muted)">{{ __('Kandungan baharu daripada cikgu anda') }}</span>
            </div>
        </div>

        @if ($notifications->isEmpty())
            <div style="background:var(--wl-surface);border:1px dashed var(--wl-line-3);border-radius:22px;padding:56px;display:flex;flex-direction:column;align-items:center;gap:10px;text-align:center">
                <span style="font-size:32px">🔔</span>
                <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:var(--wl-ink)">{{ __('Tiada notifikasi lagi') }}</h3>
                <p style="margin:0;font-size:14.5px;color:var(--wl-muted);max-width:420px">{{ __('Apabila cikgu anda memuat naik video, bahan atau kuiz baharu untuk Tahun anda, ia akan muncul di sini.') }}</p>
            </div>
        @else
            <div style="display:flex;flex-direction:column;background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:22px;overflow:hidden;box-shadow:0 4px 16px rgba(46,44,80,.04)">
                @foreach ($notifications as $n)
                    @php($m = $meta[$n->type] ?? ['icon' => 'bell', 'tint' => '#F1F0E8', 'fg' => 'var(--wl-muted-2)', 'text' => $n->title])
                    <a href="{{ $n->url ?: route('belajar.index') }}"
                       style="display:flex;align-items:center;gap:14px;padding:16px 20px;border-bottom:1px solid var(--wl-line);text-decoration:none;{{ $n->read_at ? '' : 'background:var(--wl-surface-2)' }}">
                        <span style="width:44px;height:44px;flex-shrink:0;border-radius:13px;background:{{ $m['tint'] }};color:{{ $m['fg'] }};display:grid;place-items:center"><x-icon :name="$m['icon']" class="h-5 w-5" /></span>

                        <div style="display:flex;flex-direction:column;gap:2px;min-width:0;flex:1">
                            <span style="font-family:'Nunito',sans-serif;font-size:14.5px;line-height:1.4;color:var(--wl-ink)">
                                {!! __($m['text'], ['actor' => '<strong>'.e($n->actor_name).'</strong>', 'title' => e($n->title)]) !!}
                            </span>
                            <span style="font-size:12px;color:var(--wl-muted)">{{ $n->created_at->diffForHumans() }}</span>
                        </div>

                        @unless ($n->read_at)
                            <span style="width:8px;height:8px;border-radius:50%;background:#17907B;flex-shrink:0" title="{{ __('Baharu') }}"></span>
                        @endunless
                    </a>
                @endforeach
            </div>

            <div>{{ $notifications->links() }}</div>
        @endif
    </div>
</x-student-layout>
