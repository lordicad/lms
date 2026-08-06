@props([
    'id',
    'action',
    'method' => 'DELETE',
    'title' => '',
    'message' => '',
    'confirm' => null,
    'cancel' => null,
    'icon' => 'trash',
])

{{--
    A styled, screen-centred replacement for the browser's native confirm() on destructive actions
    (delete, disconnect). Mirrors <x-logout-confirm>: the trigger is whatever is passed in the slot;
    the real POST/DELETE form is hidden and submitted by the modal's confirm button via the HTML
    `form` attribute, so it works wherever the modal is teleported.

    Teleported to <body> (outside the .tp/.wl theme scopes), so themeable colours live in the classes
    below and flip on `html.theme-dark` - matching light and night mode alike.
--}}

@once
    <style>
        .cm-card    { background:#fff; box-shadow:0 24px 70px rgba(20,20,40,.35); }
        .cm-icon    { background:#FDE7E0; color:#C24936; }
        .cm-title   { color:#28293F; }
        .cm-desc    { color:#6C6F87; }
        .cm-cancel  { border:1.5px solid #E3E2EA; background:#fff; color:#28293F; }
        .cm-confirm { background:#C24936; color:#fff; }
        .cm-confirm:hover { background:#AB3E2D; }

        html.theme-dark .cm-card    { background:#1E2732; box-shadow:0 24px 70px rgba(0,0,0,.6); }
        html.theme-dark .cm-icon    { background:rgba(194,73,54,.22); color:#E8836E; }
        html.theme-dark .cm-title   { color:#EDF2F8; }
        html.theme-dark .cm-desc    { color:#A6AFBC; }
        html.theme-dark .cm-cancel  { border-color:rgba(255,255,255,.16); background:#26313E; color:#EDF2F8; }
        html.theme-dark .cm-confirm { background:#C24936; color:#fff; }
        html.theme-dark .cm-confirm:hover { background:#D2543F; }
    </style>
@endonce

<div x-data="{ open: false }" style="display:contents">
    <form method="POST" action="{{ $action }}" id="{{ $id }}" style="display:none">
        @csrf
        @if (strtoupper($method) !== 'POST') @method($method) @endif
    </form>

    <div @click="open = true" style="display:contents">{{ $slot }}</div>

    <template x-teleport="body">
        <div x-show="open" x-cloak x-transition.opacity
             @keydown.escape.window="open = false" @click="open = false"
             style="position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999;background:rgba(20,20,35,.55);backdrop-filter:blur(3px)">
            <div @click.stop x-show="open" x-transition.opacity class="cm-card"
                 style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);width:min(400px,calc(100vw - 48px));box-sizing:border-box;border-radius:22px;padding:30px 28px;display:flex;flex-direction:column;align-items:center;text-align:center">
                <span class="cm-icon" style="width:60px;height:60px;flex-shrink:0;margin:0 auto 14px;border-radius:50%;display:grid;place-items:center"><x-icon :name="$icon" class="h-7 w-7" /></span>
                <h3 class="cm-title" style="margin:0 0 6px;font-family:'Geist',sans-serif;font-weight:800;font-size:19px">{{ $title }}</h3>
                @if ($message)
                    <p class="cm-desc" style="margin:0 0 20px;font-size:14px;line-height:1.55">{{ $message }}</p>
                @endif
                <div style="display:flex;gap:10px;width:100%">
                    <button type="button" @click="open = false" class="cm-cancel"
                            style="flex:1;min-height:46px;border-radius:13px;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;cursor:pointer;transition:background .15s">{{ $cancel ?? __('Batal') }}</button>
                    <button type="submit" form="{{ $id }}" class="cm-confirm"
                            style="flex:1;min-height:46px;border-radius:13px;border:none;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;cursor:pointer;transition:background .15s">{{ $confirm ?? __('Padam') }}</button>
                </div>
            </div>
        </div>
    </template>
</div>
