<x-cikgu-layout :title="__('Kuiz Baru')"
    :heading="__('Kuiz Baru')"
    :sub="__('Pilih jenis kuiz yang anda mahu cipta')">

    @php
        // Clipboard-with-checkmarks (interactive) and a document (printed file). Both use
        // stroke="currentColor", so the tile's text colour drives them.
        $iconInteractive = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="m8.3 12 1.1 1.1 2-2.1"/><path d="m8.3 16.4 1.1 1.1 2-2.1"/><line x1="13.6" y1="12" x2="16" y2="12"/><line x1="13.6" y1="16.5" x2="16" y2="16.5"/></svg>';
        $iconFile = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></svg>';

        $cards = [
            [
                'href' => route('cikgu.kuiz.create', ['jenis' => 'interactive']),
                'title' => __('Bina Kuiz Interaktif'),
                'desc' => __('Bina soalan aneka pilihan terus dalam sistem. Murid menjawab dalam talian dan mendapat markah serta-merta. Kuiz jenis ini memberi mata ranking.'),
                'icon' => $iconInteractive,
                'ink' => '#17907B', 'tile' => '#DCF2EE', 'blob' => '#B7E4D8', 'btn' => '#E7F4EF', 'leaf' => '',
            ],
            [
                'href' => route('cikgu.kuiz.create', ['jenis' => 'file']),
                'title' => __('Muat Naik Fail Kuiz'),
                'desc' => __('Muat naik kuiz sedia ada sebagai fail PDF atau Word untuk dicetak. Murid hanya boleh melihat dan memuat turun. Tiada penandaan automatik dan tiada mata ranking.'),
                'icon' => $iconFile,
                'ink' => '#BE922F', 'tile' => '#F3E7C9', 'blob' => '#EEDDB4', 'btn' => '#F7EFDA', 'leaf' => 'sepia(.7) saturate(1.5) hue-rotate(-18deg) brightness(1.02)',
            ],
        ];
    @endphp

    {{-- Centre the card block within the wide content column (the shared .tp-formwrap is normally
         left-aligned; centred here only). --}}
    <div class="tp-formwrap" style="margin:0 auto;width:100%">
        <a href="{{ route('cikgu.kuiz.index') }}" class="tp-back">← {{ __('Kuiz Saya') }}</a>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px">
            @foreach ($cards as $c)
                <a href="{{ $c['href'] }}" class="km-card" style="--ink:{{ $c['ink'] }}">
                    {{-- Decorative header: soft organic blob behind the icon tile, a botanical leaf in
                         the corner, and a sparkle + dot cluster. --}}
                    <div class="km-head">
                        <span class="km-leaf" @if ($c['leaf']) style="filter:{{ $c['leaf'] }}" @endif></span>
                        <svg class="km-spark" viewBox="0 0 24 24" fill="currentColor" style="color:{{ $c['blob'] }}"><path d="M12 0c.7 6.3 5.7 11.3 12 12-6.3.7-11.3 5.7-12 12-.7-6.3-5.7-11.3-12-12C6.3 11.3 11.3 6.3 12 0z"/></svg>
                        <span class="km-dots" style="color:{{ $c['blob'] }}">
                            <svg viewBox="0 0 30 30" fill="currentColor"><circle cx="3" cy="3" r="2"/><circle cx="15" cy="3" r="2"/><circle cx="27" cy="3" r="2"/><circle cx="3" cy="15" r="2"/><circle cx="15" cy="15" r="2"/><circle cx="27" cy="15" r="2"/><circle cx="3" cy="27" r="2"/><circle cx="15" cy="27" r="2"/><circle cx="27" cy="27" r="2"/></svg>
                        </span>
                        <span class="km-tile" style="background:{{ $c['tile'] }};color:{{ $c['ink'] }}">{!! $c['icon'] !!}</span>
                    </div>

                    <h2 class="tp-g km-title">{{ $c['title'] }}</h2>
                    <span class="km-bar" style="background:{{ $c['ink'] }}"></span>
                    <p class="km-desc">{{ $c['desc'] }}</p>

                    <span class="km-btn" style="background:{{ $c['btn'] }};color:{{ $c['ink'] }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="8" y1="12" x2="15" y2="12"/><polyline points="12.5 9 16 12 12.5 15"/></svg>
                        {{ __('Pilih ini') }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    @once
        <style>
            .km-card {
                position:relative; display:flex; flex-direction:column;
                background:var(--tp-surface); border:1px solid var(--tp-line);
                border-radius:22px; padding:0 24px 22px; overflow:hidden; text-decoration:none;
                box-shadow:0 2px 12px rgba(46,44,80,.05);
                transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease;
            }
            .km-card:hover { transform:translateY(-3px); box-shadow:0 12px 26px rgba(46,44,80,.10); border-color:var(--ink); }

            .km-head { position:relative; height:104px; margin:0 -24px 16px; padding:22px 24px; overflow:hidden; }
            .km-leaf {
                position:absolute; right:-14px; top:-16px; width:120px; height:120px; z-index:1;
                background:url('{{ asset('images/leaf.png') }}') center / contain no-repeat;
                opacity:.6; transform:scaleX(-1) rotate(8deg); pointer-events:none;
            }
            .km-spark { position:absolute; right:96px; top:34px; width:15px; height:15px; z-index:1; opacity:.9; }
            .km-dots  { position:absolute; right:60px; top:30px; width:26px; height:26px; z-index:1; opacity:.7; display:block; }
            .km-dots svg { width:100%; height:100%; }
            .km-tile {
                position:relative; z-index:2; width:60px; height:60px; border-radius:17px;
                display:grid; place-items:center; box-shadow:0 4px 12px rgba(46,44,80,.06);
            }
            .km-tile svg { width:29px; height:29px; }

            .km-title { font-size:19px; font-weight:800; color:var(--tp-ink); margin:0; letter-spacing:-.01em; }
            .km-bar   { display:block; width:42px; height:4px; border-radius:999px; margin:12px 0 14px; }
            .km-desc  { margin:0 0 20px; font-size:14px; color:var(--tp-muted-2); line-height:1.6; }

            .km-btn {
                margin-top:auto; display:inline-flex; align-items:center; gap:10px;
                font-family:'Geist',sans-serif; font-weight:800; font-size:14px;
                border-radius:14px; padding:13px 18px;
            }
            .km-btn svg { width:26px; height:26px; flex-shrink:0; }
        </style>
    @endonce
</x-cikgu-layout>
