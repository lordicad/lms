@props([
    // Collection of TeacherNotification, or null where the role has no notifications yet.
    'notifications' => null,
    'unread' => 0,
    // Where "Lihat semua" goes, and where opening the panel reports the items as read. Both are
    // teacher-only routes today, so both are optional: without them the panel still opens and
    // shows its empty state.
    'allUrl' => null,
    'markReadUrl' => null,
    // The portals style their header buttons differently (.tp-iconbtn vs the student's .wl-icbtn).
    'triggerClass' => 'tp-iconbtn',
    'triggerStyle' => '',
    'meta' => [],
])

{{--
    Notification bell with a dropdown panel anchored to the button.

    One component for all three portals. The panel reads its colours from the teacher tokens where
    they exist and falls back to the student ones, then to a literal — the student layout defines
    --wl-* and no --tp-*, so a bare var(--tp-surface) would resolve to nothing there and leave the
    panel transparent.
--}}

@php
    $surface = 'var(--tp-surface, var(--wl-surface, #fff))';
    $ink = 'var(--tp-ink, var(--wl-ink, #28293F))';
    $muted = 'var(--tp-muted, var(--wl-muted, #8B8AA3))';
    $brand = 'var(--tp-teal, #17907B)';
    $items = $notifications ?? collect();
@endphp

<div x-data="notifBell({ unread: {{ (int) $unread }}, markReadUrl: @js($markReadUrl) })" style="position:relative">
    <button type="button" @click="toggle()" class="{{ $triggerClass }}" title="{{ __('Notifikasi') }}"
            style="position:relative;{{ $triggerStyle }}" :aria-expanded="open ? 'true' : 'false'">
        <x-icon name="bell" class="h-[19px] w-[19px]" />
        <span x-show="unread > 0" x-cloak x-text="unread > 9 ? '9+' : unread"
              style="position:absolute;top:-4px;right:-4px;min-width:17px;height:17px;padding:0 4px;border-radius:999px;background:#EB5E5A;color:#fff;font-family:'Geist',sans-serif;font-size:10.5px;font-weight:800;display:grid;place-items:center;box-sizing:border-box"></span>
    </button>

    <div x-show="open" x-cloak x-transition.origin.top.right
         @click.outside="open = false" @keydown.escape.window="open = false"
         style="position:absolute;top:calc(100% + 10px);right:0;width:344px;max-width:calc(100vw - 40px);background:{{ $surface }};border:1px solid rgba(46,44,80,.1);border-radius:16px;box-shadow:0 16px 44px rgba(46,44,80,.22);z-index:60;overflow:hidden">
        <div style="padding:14px 18px;border-bottom:1px solid rgba(46,44,80,.07)">
            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:{{ $ink }}">{{ __('Notifikasi') }}</span>
        </div>

        <div style="max-height:360px;overflow-y:auto">
            @forelse ($items as $n)
                @php($m = $meta[$n->type] ?? ['icon' => '🔔', 'tint' => '#F1F0E8', 'text' => $n->title])
                <a href="{{ $n->url ?: $allUrl }}"
                   style="display:flex;align-items:flex-start;gap:11px;padding:12px 18px;border-bottom:1px solid rgba(46,44,80,.05);text-decoration:none;{{ $n->read_at ? '' : 'background:var(--tp-surface-2, var(--wl-surface-2, #FBFAF6))' }}">
                    <span style="width:36px;height:36px;flex-shrink:0;border-radius:10px;background:{{ $m['tint'] }};display:grid;place-items:center;font-size:16px">{{ $m['icon'] }}</span>
                    <span style="min-width:0;flex:1;display:flex;flex-direction:column;gap:2px">
                        <span style="font-family:'Nunito',sans-serif;font-size:13px;line-height:1.4;color:{{ $ink }}">{!! __($m['text'], ['actor' => '<strong>'.e($n->actor_name).'</strong>', 'title' => e($n->title)]) !!}</span>
                        <span style="font-size:11.5px;color:{{ $muted }}">{{ $n->created_at->diffForHumans() }}</span>
                    </span>
                </a>
            @empty
                <div style="padding:30px 18px;text-align:center;color:{{ $muted }}">
                    <div style="font-size:24px;margin-bottom:6px">🔔</div>
                    <span style="font-size:13px">{{ __('Tiada notifikasi lagi') }}</span>
                </div>
            @endforelse
        </div>

        @if ($allUrl && $items->isNotEmpty())
            <a href="{{ $allUrl }}" style="display:block;text-align:center;padding:11px;font-family:'Geist',sans-serif;font-weight:800;font-size:13px;color:{{ $brand }};border-top:1px solid rgba(46,44,80,.07);text-decoration:none">{{ __('Lihat semua') }}</a>
        @endif
    </div>
</div>

@once
    @push('scripts')
        <script>
            function notifBell({ unread, markReadUrl }) {
                return {
                    open: false,
                    unread,
                    toggle() {
                        this.open = ! this.open;
                        if (this.open && this.unread > 0) this.markRead();
                    },
                    markRead() {
                        // Only the teacher portal has somewhere to report this to.
                        if (! markReadUrl) return;

                        const was = this.unread;
                        this.unread = 0;   // clear the badge immediately
                        const token = document.querySelector('meta[name=csrf-token]')?.content;
                        fetch(markReadUrl, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                        }).catch(() => { this.unread = was; });
                    },
                };
            }
        </script>
    @endpush
@endonce
