@props(['id' => 'logout-form'])

{{--
    A styled, screen-centred replacement for the browser's native logout confirm().

    The trigger is whatever is passed in the slot (a button/link styled by each layout); clicking it
    opens the modal. The real POST form is hidden and submitted by the modal's confirm button via the
    HTML `form` attribute, so it works no matter where the modal is teleported. Colours are literal
    (not layout vars) so it looks the same in the teacher, admin, student and auth layouts.
--}}

<div x-data="{ open: false }" style="display:contents">
    <form method="POST" action="{{ route('logout') }}" id="{{ $id }}" style="display:none">
        @csrf
    </form>

    <div @click="open = true" style="display:contents">{{ $slot }}</div>

    <template x-teleport="body">
        <div x-show="open" x-cloak x-transition.opacity
             @keydown.escape.window="open = false" @click="open = false"
             style="position:fixed;top:0;left:0;right:0;bottom:0;width:100vw;height:100vh;box-sizing:border-box;z-index:9999;background:rgba(20,20,35,.55);backdrop-filter:blur(3px);display:flex;align-items:center;justify-content:center;padding:24px">
            <div @click.stop x-show="open" x-transition
                 style="width:min(380px,100%);max-width:380px;margin:auto;box-sizing:border-box;background:#fff;border-radius:22px;box-shadow:0 24px 70px rgba(20,20,40,.35);padding:30px 28px;display:flex;flex-direction:column;align-items:center;text-align:center">
                <span style="width:60px;height:60px;flex-shrink:0;margin:0 auto 14px;border-radius:50%;background:#FDE7E0;color:#C24936;display:grid;place-items:center"><x-icon name="logout" class="h-7 w-7" /></span>
                <h3 style="margin:0 0 6px;font-family:'Geist',sans-serif;font-weight:800;font-size:19px;color:#28293F">{{ __('Log keluar?') }}</h3>
                <p style="margin:0 0 20px;font-size:14px;color:#6C6F87;line-height:1.5">{{ __('Anda akan dilog keluar daripada akaun anda.') }}</p>
                <div style="display:flex;gap:10px;width:100%">
                    <button type="button" @click="open = false"
                            style="flex:1;min-height:46px;border-radius:13px;border:1.5px solid #E3E2EA;background:#fff;color:#28293F;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;cursor:pointer">{{ __('Batal') }}</button>
                    <button type="submit" form="{{ $id }}"
                            style="flex:1;min-height:46px;border-radius:13px;border:none;background:#C24936;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;cursor:pointer">{{ __('Log Keluar') }}</button>
                </div>
            </div>
        </div>
    </template>
</div>
