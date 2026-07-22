{{--
    Single-file drag-and-drop zone wrapping a real <input type="file">.

    The real input stays in the page (screen-reader reachable, and still what gets
    submitted); the zone is only a nicer way to reach it — the same pattern as the
    video form. Extra attributes (aria-describedby, @error state) land on the input.

    Props:
      name     input name (and id, unless id is given)
      accept   the accept attribute; dropped files are checked against its extensions
      current  optional "keep existing file" line shown until a file is chosen
--}}
@props(['name' => 'file', 'id' => null, 'accept' => '', 'current' => null])

@php($id = $id ?? $name)

<div x-data="{
        dragging: false,
        fileName: '',
        fileSize: '',
        invalid: false,

        allowed(file) {
            const extensions = @js(collect(explode(',', $accept))->map(fn ($t) => ltrim(trim($t), '.'))->filter(fn ($t) => ! str_contains($t, '/'))->values());
            if (! extensions.length) return true;
            const extension = (file.name.split('.').pop() || '').toLowerCase();
            return extensions.includes(extension);
        },

        onDrop(event) {
            this.dragging = false;
            const file = event.dataTransfer?.files?.[0];
            if (! file || typeof DataTransfer === 'undefined') return;

            this.invalid = ! this.allowed(file);
            if (this.invalid) return;

            // Assigning .files does not fire change; dispatching keeps both routes identical.
            const transfer = new DataTransfer();
            transfer.items.add(file);
            this.$refs.input.files = transfer.files;
            this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }));
        },

        onChange(event) {
            this.invalid = false;
            const file = event.target.files?.[0] ?? null;
            this.fileName = file ? file.name : '';
            this.fileSize = file ? (file.size / (1024 * 1024)).toFixed(1) + ' MB' : '';
        },
     }"
     style="display:flex;flex-direction:column;gap:8px">

    <div class="tp-dropzone" :class="dragging && 'is-dragging'" style="padding:26px"
         role="button" tabindex="0" aria-controls="{{ $id }}"
         @click="$refs.input.click()"
         @keydown.enter.prevent="$refs.input.click()"
         @keydown.space.prevent="$refs.input.click()"
         @dragover.prevent="dragging = true"
         @dragenter.prevent="dragging = true"
         @dragleave.prevent="dragging = false"
         @drop.prevent="onDrop($event)">
        <span style="color:var(--tp-teal)" aria-hidden="true"><x-icon name="upload" class="h-7 w-7" /></span>

        <template x-if="! fileName">
            <span class="tp-g" style="font-weight:800;font-size:14.5px;color:var(--tp-ink)">{{ __('Seret fail ke sini') }}</span>
        </template>
        <template x-if="fileName">
            <span class="tp-g" style="font-weight:800;font-size:14.5px;color:var(--tp-ink);word-break:break-all" x-text="fileName"></span>
        </template>

        <span class="tp-hint" x-text="fileName ? fileSize : @js(__('atau'))"></span>

        <span class="tp-btn-outline" style="min-height:36px;padding:0 16px;font-size:13px;pointer-events:none">
            <span x-text="fileName ? @js(__('Tukar Fail')) : @js(__('Pilih Fail'))"></span>
        </span>
    </div>

    <input id="{{ $id }}" name="{{ $name }}" type="file" accept="{{ $accept }}"
           x-ref="input" @change="onChange($event)" class="sr-only" {{ $attributes }}>

    <p x-show="invalid" x-cloak class="tp-error" style="margin:0">{{ __('Jenis fail itu tidak disokong di sini.') }}</p>

    @if ($current)
        <p x-show="! fileName" style="display:flex;align-items:center;gap:8px;background:var(--tp-input);border-radius:12px;padding:12px 14px;font-size:13.5px;color:var(--tp-muted-2);margin:0">
            {{ $current }}
        </p>
    @endif
</div>
