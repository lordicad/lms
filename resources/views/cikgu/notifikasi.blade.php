<x-cikgu-layout :title="__('Notifikasi')" :heading="__('Notifikasi')" heading-icon="bell" :sub="__('Aktiviti murid pada kandungan anda')">

    @php
        // Per-type presentation: emoji + a message template with :actor and :title.
        $meta = [
            \App\Models\TeacherNotification::TYPE_QUIZ => ['icon' => 'quiz', 'tint' => '#FEF0CE', 'fg' => '#8A6A12', 'text' => __(':actor menjawab kuiz ":title"')],
            \App\Models\TeacherNotification::TYPE_FAVOURITE => ['icon' => 'heart', 'tint' => '#FBE4ED', 'fg' => '#B84A75', 'text' => __(':actor menggemari video ":title"')],
            \App\Models\TeacherNotification::TYPE_DOWNLOAD => ['icon' => 'download', 'tint' => '#E4EEF9', 'fg' => '#2E6CA8', 'text' => __(':actor memuat turun bahan ":title"')],
        ];
    @endphp

    @if ($notifications->isEmpty())
        <div class="tp-empty">
            <img src="{{ asset('images/bell.png') }}" alt="" style="width:46px;height:46px;object-fit:contain">
            <h3 class="tp-g" style="font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Tiada notifikasi lagi') }}</h3>
            <p style="margin:0;font-size:14.5px;color:var(--tp-muted);max-width:420px">{{ __('Apabila murid menjawab kuiz, menggemari video atau memuat turun bahan anda, ia akan muncul di sini.') }}</p>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:18px">
            <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:18px;overflow:hidden;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                @foreach ($notifications as $n)
                    @php($m = $meta[$n->type] ?? ['icon' => 'bell', 'tint' => '#F1F0E8', 'text' => $n->title])
                    <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--tp-line);{{ $n->read_at ? '' : 'background:#FBFAF6' }}">
                        <span style="width:42px;height:42px;flex-shrink:0;border-radius:12px;background:{{ $m['tint'] }};color:{{ $m['fg'] ?? 'var(--tp-ink)' }};display:grid;place-items:center;font-size:19px">@if (preg_match('/^[a-z0-9-]+$/', $m['icon']))<x-icon :name="$m['icon']" class="h-[21px] w-[21px]" />@else{{ $m['icon'] }}@endif</span>

                        <div style="display:flex;flex-direction:column;gap:2px;min-width:0;flex:1">
                            <span style="font-family:'Geist',sans-serif;font-weight:700;font-size:14px;color:var(--tp-ink)">
                                {!! __($m['text'], ['actor' => '<strong>'.e($n->actor_name).'</strong>', 'title' => e($n->title)]) !!}
                            </span>
                            <span style="font-size:12px;color:var(--tp-muted)">{{ $n->created_at->diffForHumans() }}</span>
                        </div>

                        @unless ($n->read_at)
                            <span style="width:8px;height:8px;border-radius:50%;background:#17907B;flex-shrink:0" title="{{ __('Baharu') }}"></span>
                        @endunless

                        @if ($n->url)
                            <a href="{{ $n->url }}" class="tp-btn-outline tp-btn-sm" style="flex-shrink:0">{{ __('Lihat') }}</a>
                        @endif
                    </div>
                @endforeach
            </div>

            <div>{{ $notifications->links() }}</div>
        </div>
    @endif
</x-cikgu-layout>
