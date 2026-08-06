@props(['id' => 'logout-form'])

{{--
    A styled, screen-centred replacement for the browser's native logout confirm().

    The trigger is whatever is passed in the slot (a button/link styled by each layout); clicking it
    opens the modal. The real POST form is hidden and submitted by the modal's confirm button via the
    HTML `form` attribute, so it works no matter where the modal is teleported.

    The card is teleported to <body>, outside the .tp/.wl theme scopes, so it can't read those layout
    vars. Instead the themeable colours live in the classes below and flip on `html.theme-dark` - the
    class the harness puts on <html> in every shell - so the modal matches light and night mode alike.
--}}

@once
    <style>
        .lc-card    { background:#fff; box-shadow:0 24px 70px rgba(20,20,40,.35); }
        .lc-icon    { background:#FDE7E0; color:#C24936; }
        .lc-title   { color:#28293F; }
        .lc-desc    { color:#6C6F87; }
        .lc-cancel  { border:1.5px solid #E3E2EA; background:#fff; color:#28293F; }
        .lc-confirm { background:#C24936; color:#fff; }

        html.theme-dark .lc-card    { background:#1E2732; box-shadow:0 24px 70px rgba(0,0,0,.6); }
        html.theme-dark .lc-icon    { background:rgba(194,73,54,.22); color:#E8836E; }
        html.theme-dark .lc-title   { color:#EDF2F8; }
        html.theme-dark .lc-desc    { color:#A6AFBC; }
        html.theme-dark .lc-cancel  { border-color:rgba(255,255,255,.16); background:#26313E; color:#EDF2F8; }
        html.theme-dark .lc-confirm { background:#C24936; color:#fff; }
    </style>
@endonce

<div x-data="{ open: false }" style="display:contents">
    <form method="POST" action="{{ route('logout') }}" id="{{ $id }}" style="display:none">
        @csrf
    </form>

    <div @click="open = true" style="display:contents">{{ $slot }}</div>

    <template x-teleport="body">
        {{-- Backdrop fills the viewport; the card centres itself with top/left 50% + translate,
             which does not depend on the backdrop's height resolving to the full screen. --}}
        <div x-show="open" x-cloak x-transition.opacity
             @keydown.escape.window="open = false" @click="open = false"
             style="position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999;background:rgba(20,20,35,.55);backdrop-filter:blur(3px)">
            <div @click.stop x-show="open" x-transition.opacity class="lc-card"
                 style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);width:min(380px,calc(100vw - 48px));box-sizing:border-box;border-radius:22px;padding:30px 28px;display:flex;flex-direction:column;align-items:center;text-align:center">
                <span class="lc-icon" style="width:60px;height:60px;flex-shrink:0;margin:0 auto 14px;border-radius:50%;display:grid;place-items:center"><x-icon name="logout" class="h-7 w-7" /></span>
                <h3 class="lc-title" style="margin:0 0 6px;font-family:'Geist',sans-serif;font-weight:800;font-size:19px">{{ __('Log keluar?') }}</h3>
                <p class="lc-desc" style="margin:0 0 20px;font-size:14px;line-height:1.5">{{ __('Anda akan dilog keluar daripada akaun anda.') }}</p>
                <div style="display:flex;gap:10px;width:100%">
                    <button type="button" @click="open = false" class="lc-cancel"
                            style="flex:1;min-height:46px;border-radius:13px;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;cursor:pointer">{{ __('Batal') }}</button>
                    <button type="submit" form="{{ $id }}" class="lc-confirm"
                            style="flex:1;min-height:46px;border-radius:13px;border:none;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;cursor:pointer">{{ __('Log Keluar') }}</button>
                </div>
            </div>
        </div>
    </template>
</div>
