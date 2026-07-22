{{--
    Log-out button with a styled confirmation dialog, replacing the native confirm().

    Renders the trigger (slot = its inner content, attributes land on the button) plus
    the modal. Colours are literal, not var(--tp-*): this is used on the teacher, admin
    AND student layouts, and only the first two define those variables.

    x-if for the overlay, not x-show: an x-show overlay has reproducibly failed to
    re-hide in this codebase (see HANDOVER) and an invisible overlay swallows clicks.
--}}

<div x-data="{ open: false }" style="display:contents">
    <button type="button" @click="open = true" {{ $attributes }}>
        {{ $slot }}
    </button>

    <template x-if="open">
        <div @keydown.escape.window="open = false" style="position:fixed;inset:0;z-index:95;display:grid;place-items:center;padding:20px;font-family:'Geist',sans-serif">
            <div @click="open = false" style="position:absolute;inset:0;background:rgba(46,44,80,.45)"></div>

            <div role="dialog" aria-modal="true" aria-labelledby="logout-confirm-title"
                 x-init="$nextTick(() => $refs.cancel.focus())"
                 style="position:relative;background:#fff;border-radius:20px;max-width:360px;width:100%;padding:26px;display:flex;flex-direction:column;gap:8px;box-shadow:0 24px 60px rgba(46,44,80,.3)">
                <span style="width:44px;height:44px;border-radius:14px;background:#FDE7E0;color:#C24936;display:grid;place-items:center" aria-hidden="true">
                    <x-icon name="logout" class="h-5 w-5" />
                </span>
                <h2 id="logout-confirm-title" style="margin:0;font-size:18px;font-weight:800;color:#2E2C50">{{ __('Log keluar?') }}</h2>
                <p style="margin:0;font-size:14px;color:#6B6A85">{{ __('Anda pasti mahu log keluar daripada akaun anda?') }}</p>

                <div style="display:flex;gap:10px;margin-top:12px">
                    <button type="button" x-ref="cancel" @click="open = false"
                            style="flex:1;min-height:44px;border:1.5px solid rgba(46,44,80,.15);border-radius:12px;background:#fff;color:#2E2C50;font-family:inherit;font-weight:800;font-size:14px;cursor:pointer">
                        {{ __('Batal') }}
                    </button>
                    <form method="POST" action="{{ route('logout') }}" style="flex:1;display:flex">
                        @csrf
                        <button type="submit"
                                style="flex:1;min-height:44px;border:none;border-radius:12px;background:#C24936;color:#fff;font-family:inherit;font-weight:800;font-size:14px;cursor:pointer">
                            {{ __('Log Keluar') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
