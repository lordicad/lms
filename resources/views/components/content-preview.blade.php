@props(['obj', 'pill'])

{{--
    WeLearn Admin preview modal shell: blurred overlay + a 920px card with a blue gradient
    header (kind pill + white title + subtitle) and a styled close button. The body is the
    slot. `obj` is the Alpine object holding .title / .subtitle (e.g. "quiz", "item", "lesson").
--}}

<div @click="close()" role="dialog" aria-modal="true" :aria-label="{{ $obj }}.title"
     style="position:fixed;inset:0;z-index:100;background:rgba(30,30,45,.55);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;padding:32px">

    <div @click.stop
         style="width:min(920px,100%);max-height:90vh;overflow:hidden;background:#fff;border-radius:20px;box-shadow:0 24px 70px rgba(20,20,40,.4);display:flex;flex-direction:column">

        {{-- Header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:13px 22px;background:linear-gradient(120deg,#5A9BD8,#7DB4E6);flex-shrink:0">
            <div style="display:flex;flex-direction:column;gap:4px;min-width:0">
                <div style="display:flex;align-items:center;gap:10px;min-width:0">
                    <span style="flex-shrink:0;background:rgba(255,255,255,.28);color:#fff;border-radius:999px;padding:3px 11px;font-family:'Geist',sans-serif;font-size:11px;font-weight:800;letter-spacing:.02em">{{ $pill }}</span>
                    <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:18px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-shadow:0 1px 2px rgba(0,0,0,.1)" x-text="{{ $obj }}.title"></h2>
                </div>
                <span style="font-size:12.5px;color:rgba(255,255,255,.92);font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" x-text="{{ $obj }}.subtitle"></span>
            </div>

            <button type="button" @click="close()" x-init="$el.focus()" title="{{ __('Tutup') }}"
                    style="flex-shrink:0;width:36px;height:36px;border-radius:10px;border:none;cursor:pointer;background:rgba(255,255,255,.22);color:#fff;display:grid;place-items:center">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>

        {{ $slot }}
    </div>
</div>
