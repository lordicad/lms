@props([
    // Name of the file input that submits, e.g. "files[]" or "videos[]".
    'name',
    // Name of the per-row text input, e.g. "titles[]". Paired to the file by position.
    'titleName',
    'accept' => '',
    'maxMb' => 30,
    'maxFiles' => 20,
    // Extensions this zone accepts, lowercase and without the dot.
    'extensions' => [],
    'heading' => null,
    'hint' => null,
    'titleLabel' => null,
    'titleMax' => 255,
])

{{--
    Drop zone plus the list of what was chosen, each row carrying a display name.

    The list on screen is the source of truth: the hidden multi-file input is rebuilt from it on
    every add and remove, because a FileList cannot be edited in place and cannot carry a name per
    file. Row order is submission order, so files[i] always pairs with titles[i] on the server.
--}}

<div x-data="fileDropzone({{ Js::from([
    'maxMb' => $maxMb,
    'maxFiles' => $maxFiles,
    'extensions' => array_map('strtolower', $extensions),
    'labels' => [
        'badType' => __('":name" bukan jenis fail yang dibenarkan.'),
        'tooBig' => __('":name" melebihi had :max MB.'),
        'tooMany' => __('Had :max fail sekali muat naik.'),
        'count' => __(':count fail dipilih'),
    ],
]) }})" class="tp-field">

    <div class="tp-dropzone" :class="dragging && 'is-dragging'"
         role="button" tabindex="0"
         @click="$refs.picker.click()"
         @keydown.enter.prevent="$refs.picker.click()"
         @keydown.space.prevent="$refs.picker.click()"
         @dragover.prevent="dragging = true"
         @dragenter.prevent="dragging = true"
         @dragleave.prevent="dragging = false"
         @drop.prevent="dragging = false; take($event.dataTransfer?.files)">
        <x-icon name="upload" class="h-7 w-7" style="color:var(--tp-teal)" />
        <span class="tp-g" style="font-weight:800;font-size:14.5px;color:var(--tp-ink)">{{ $heading ?? __('Seret & lepaskan fail di sini') }}</span>
        <span class="tp-hint">{{ __('atau') }}</span>
        <span class="tp-btn-outline" style="min-height:38px;padding:0 16px;font-size:13px;pointer-events:none">{{ __('Tambah Fail') }}</span>
    </div>

    {{-- The picker holds nothing itself: take() moves what it collects into the input below and
         clears it, so choosing twice adds rather than replaces. --}}
    <input type="file" multiple x-ref="picker" @change="take($event.target.files); $event.target.value = ''"
           class="sr-only" @if ($accept) accept="{{ $accept }}" @endif>

    <input type="file" name="{{ $name }}" multiple x-ref="files" class="sr-only" tabindex="-1" aria-hidden="true">

    @if ($hint)
        <p class="tp-hint">{{ $hint }}</p>
    @endif

    <p x-show="error" x-cloak class="tp-error" x-text="error"></p>

    <div x-show="rows.length" x-cloak style="border:1px solid var(--tp-line-2);border-radius:14px;overflow:hidden;margin-top:4px">
        <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-bottom:1px solid var(--tp-line)">
            <span class="tp-g" style="font-weight:800;font-size:14px;color:var(--tp-ink)"
                  x-text="labels.count.replace(':count', rows.length)"></span>
            <span class="tp-hint" style="margin-left:auto">{{ __('Maks. saiz setiap fail: :max MB', ['max' => $maxMb]) }}</span>
        </div>

        <div style="display:grid;grid-template-columns:1fr 88px minmax(180px,1.2fr) 44px;gap:12px;padding:9px 16px;border-bottom:1px solid var(--tp-line)">
            <span class="tp-g tp-hint" style="font-weight:800">{{ __('Fail') }}</span>
            <span class="tp-g tp-hint" style="font-weight:800">{{ __('Saiz') }}</span>
            <span class="tp-g tp-hint" style="font-weight:800">{{ $titleLabel ?? __('Nama paparan (untuk pelajar)') }}</span>
            <span></span>
        </div>

        <template x-for="(row, index) in rows" :key="row.key">
            <div style="display:grid;grid-template-columns:1fr 88px minmax(180px,1.2fr) 44px;gap:12px;align-items:center;padding:12px 16px;border-bottom:1px solid var(--tp-line)">
                <span style="display:flex;align-items:center;gap:10px;min-width:0">
                    <span style="width:34px;height:34px;flex-shrink:0;border-radius:9px;background:#FBE4ED;color:#B84A75;display:grid;place-items:center">
                        <x-icon name="file-text" class="h-4 w-4" />
                    </span>
                    <span style="min-width:0;display:flex;flex-direction:column">
                        <span class="tp-g" style="font-weight:800;font-size:13.5px;color:var(--tp-ink);word-break:break-all" x-text="row.name"></span>
                        <span class="tp-hint" x-text="row.ext"></span>
                    </span>
                </span>
                <span class="tp-hint" x-text="row.size"></span>
                <span style="display:flex;flex-direction:column;gap:3px;min-width:0">
                    <input type="text" name="{{ $titleName }}" maxlength="{{ $titleMax }}"
                           class="tp-input" style="min-height:38px;font-size:13.5px"
                           :placeholder="row.name"
                           aria-label="{{ $titleLabel ?? __('Nama paparan (untuk pelajar)') }}"
                           x-model="row.title">
                    <span class="tp-hint" style="align-self:flex-end" x-text="(row.title || '').length + '/{{ $titleMax }}'"></span>
                </span>
                <button type="button" class="tp-icon-action tp-icon-danger" @click="remove(index)" title="{{ __('Buang') }}">
                    <x-icon name="trash" class="h-4 w-4" />
                    <span class="sr-only">{{ __('Buang fail') }}</span>
                </button>
            </div>
        </template>
    </div>
</div>

@once
    @push('scripts')
        <script>
            function fileDropzone({ maxMb, maxFiles, extensions, labels }) {
                return {
                    maxMb, maxFiles, extensions, labels,
                    dragging: false, error: '', rows: [], nextKey: 0,

                    take(fileList) {
                        if (! fileList || typeof DataTransfer === 'undefined') return;

                        this.error = '';

                        for (const file of Array.from(fileList)) {
                            if (this.rows.length >= this.maxFiles) {
                                this.error = this.labels.tooMany.replace(':max', this.maxFiles);
                                break;
                            }
                            if (! this.extensions.includes(this.extensionOf(file.name))) {
                                this.error = this.labels.badType.replace(':name', file.name);
                                continue;
                            }
                            if (file.size / (1024 * 1024) > this.maxMb) {
                                this.error = this.labels.tooBig.replace(':name', file.name).replace(':max', this.maxMb);
                                continue;
                            }

                            this.rows.push({
                                key: this.nextKey++,
                                file,
                                name: file.name,
                                ext: this.extensionOf(file.name).toUpperCase(),
                                size: (file.size / (1024 * 1024)).toFixed(1) + ' MB',
                                title: '',
                            });
                        }

                        this.sync();
                    },

                    remove(index) {
                        this.rows.splice(index, 1);
                        this.error = '';
                        this.sync();
                    },

                    extensionOf(name) {
                        const dot = name.lastIndexOf('.');
                        return dot < 0 ? '' : name.slice(dot + 1).toLowerCase();
                    },

                    /** Rebuild the hidden input so it matches the list on screen, in order. */
                    sync() {
                        const transfer = new DataTransfer();
                        this.rows.forEach((row) => transfer.items.add(row.file));
                        this.$refs.files.files = transfer.files;
                    },
                };
            }
        </script>
    @endpush
@endonce
