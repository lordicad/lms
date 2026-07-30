@props([
    // Input id (pairs with the field's own <label for="...">) and submit name.
    'id' => null,
    'name',
    'accept' => '',
    // Button caption; defaults to the Malay "Pilih Fail".
    'button' => null,
])

{{--
    A localised stand-in for the native file input. The browser draws "Choose File / No file
    chosen" from its own UI language and neither CSS nor JS can retext it, so we hide the real
    input and paint our own Bahasa Melayu button + filename label on top. The input keeps its id,
    name, accept and any passed-through attributes (aria-describedby, aria-invalid), so the form
    and validation behave exactly as before; only the visible chrome changes.
--}}
<label class="wl-fileinput" @if ($id) for="{{ $id }}" @endif
       x-data="{ text: '{{ __('Tiada fail dipilih') }}' }">
    <span class="wl-fileinput-btn" aria-hidden="true">{{ $button ?? __('Pilih Fail') }}</span>
    <span class="wl-fileinput-name" aria-hidden="true" x-text="text"></span>
    <input type="file"
           @if ($id) id="{{ $id }}" @endif
           name="{{ $name }}"
           @if ($accept) accept="{{ $accept }}" @endif
           class="sr-only"
           x-on:change="const f = $event.target.files; text = f.length > 1 ? '{{ __(':n fail dipilih') }}'.replace(':n', f.length) : (f[0]?.name || '{{ __('Tiada fail dipilih') }}')"
           {{ $attributes }}>
</label>

@once
    <style>
        .wl-fileinput {
            display:flex; align-items:center; gap:14px; width:100%; min-height:46px;
            box-sizing:border-box; padding:5px; cursor:pointer;
            border:1.5px solid var(--tp-line-2, var(--wl-line-2, #DDE3DF));
            border-radius:12px; background:var(--tp-input, var(--wl-input, #fff));
        }
        .wl-fileinput-btn {
            display:inline-flex; align-items:center; min-height:36px; padding:0 16px;
            border-radius:9px; background:#17907B; color:#fff; white-space:nowrap;
            font-family:'Geist',sans-serif; font-weight:800; font-size:12.5px; transition:background .15s;
        }
        .wl-fileinput:hover .wl-fileinput-btn { background:#2BB39B; }
        .wl-fileinput-name {
            min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
            font-family:'Nunito',sans-serif; font-size:13.5px; color:var(--tp-muted, var(--wl-muted, #6B7280));
        }
        .wl-fileinput:focus-within { outline:2px solid rgba(23,144,123,.4); outline-offset:2px; }
    </style>
@endonce
