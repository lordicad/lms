<x-dynamic-component :component="auth()->user()->isTeacher() ? 'app-layout' : 'student-layout'" :title="$subject->name.' '.$grade->name">
    @php($col = $subject->color ?: '#17907B')
    @php($selGrad = "linear-gradient(135deg, color-mix(in oklab, {$col} 30%, #fff), color-mix(in oklab, {$col} 12%, #fff))")

    <div style="display:flex;flex-direction:column;gap:20px">
        <a href="{{ route('subjek.index', ['tahun' => $grade->level]) }}" class="wl-back">← {{ __('Semua subjek') }}</a>

        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
            <span style="width:56px;height:56px;border-radius:16px;background:{{ $selGrad }};display:grid;place-items:center"><x-subject-icon :subject="$subject" class="h-7 w-7" /></span>
            <div style="display:flex;flex-direction:column;gap:4px;margin-right:auto">
                <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:26px;font-weight:800;letter-spacing:-.01em;color:var(--wl-ink)">{{ $subject->name }}. {{ $grade->name }}</h2>
                <span style="display:inline-flex;align-items:center;gap:7px;font-size:14px;font-weight:700;color:var(--wl-muted)">
                    <span style="width:8px;height:8px;border-radius:50%;background:#2BB39B;flex-shrink:0"></span>
                    {{ __(':count bab tersedia', ['count' => $chapters->count()]) }}
                </span>
            </div>
            <label style="display:flex;flex-direction:column;gap:5px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;color:var(--wl-ink)">
                {{ __('Tukar Tahun') }}
                <select onchange="if (this.value) window.location = '{{ url('/belajar/'.$subject->slug) }}/' + this.value" class="js-styled-select"
                        style="min-height:44px;border:1px solid var(--wl-line-2);border-radius:12px;padding:0 36px 0 14px;-webkit-appearance:none;-moz-appearance:none;appearance:none;background:var(--wl-surface) url(&quot;data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20width='24'%20height='24'%20viewBox='0%200%2024%2024'%20fill='none'%20stroke='%2328293F'%20stroke-width='2.5'%20stroke-linecap='round'%20stroke-linejoin='round'%3E%3Cpath%20d='M6%209l6%206%206-6'/%3E%3C/svg%3E&quot;) no-repeat right 12px center;background-size:12px;font-family:'Geist',sans-serif;font-weight:700;font-size:14px;color:var(--wl-ink);cursor:pointer">
                    @foreach ($grades as $option)
                        <option value="{{ $option->level }}" @selected($option->level === $grade->level)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        @if ($chapters->isEmpty())
            <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:18px;padding:44px;display:flex;flex-direction:column;align-items:center;gap:8px;text-align:center">
                <span style="width:44px;height:44px;border-radius:50%;background:#F1F0E8;display:grid;place-items:center;font-size:18px">📚</span>
                <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:var(--wl-ink)">{{ __('Belum ada bab untuk subjek ini') }}</span>
                <span style="font-size:13.5px;color:var(--wl-muted)">{{ __('Cikgu belum menyediakan bab untuk :subject :grade.', ['subject' => $subject->name, 'grade' => $grade->name]) }}</span>
            </div>
        @else
            {{-- A colour cycles per chapter (accent bar, number badge and the Lihat button all
                 share it); the meta icons keep a fixed colour per content type. --}}
            @php($palette = [
                ['accent' => '#17907B', 'tint' => '#DCF2EE'],
                ['accent' => '#2E6CA8', 'tint' => '#E4EEF9'],
                ['accent' => '#7C5CBF', 'tint' => '#EDE7F9'],
                ['accent' => '#D9862B', 'tint' => '#FBEAD3'],
                ['accent' => '#D9548A', 'tint' => '#FBE0EC'],
            ])
            <div style="display:flex;flex-direction:column;gap:14px">
                @foreach ($chapters as $chapter)
                    @php($total = $chapter->lessons_count)
                    @php($watched = (int) ($watchedByChapter[$chapter->id] ?? 0))
                    @php($wpct = $total > 0 ? (int) round($watched / $total * 100) : 0)
                    @php($c = $palette[$loop->index % count($palette)])
                    @php($ringOffset = 113.097 * (1 - $wpct / 100))
                    <a href="{{ route('bab.show', $chapter) }}" class="wl-row-lift"
                       style="background:var(--wl-surface);border:1px solid var(--wl-line);border-left:4px solid {{ $c['accent'] }};border-radius:16px;padding:16px 20px;display:flex;align-items:center;gap:18px;box-shadow:0 3px 12px rgba(46,44,80,.04);cursor:pointer;text-decoration:none">
                        <span style="width:52px;height:52px;border-radius:14px;background:{{ $c['tint'] }};display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:20px;color:{{ $c['accent'] }};flex-shrink:0">{{ $chapter->number }}</span>
                        <div style="display:flex;flex-direction:column;gap:7px;margin-right:auto;min-width:0">
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:17px;color:var(--wl-ink)">{{ $chapter->title }}</span>
                            <div style="display:flex;gap:18px;font-size:13px;font-weight:700;color:var(--wl-muted-2);flex-wrap:wrap">
                                <span style="display:inline-flex;align-items:center;gap:6px"><x-icon name="video" class="h-4 w-4" style="color:#17907B" />{{ $chapter->lessons_count }} video</span>
                                <span style="display:inline-flex;align-items:center;gap:6px"><x-icon name="file" class="h-4 w-4" style="color:#2E6CA8" />{{ $chapter->materials_count }} {{ __('bahan') }}</span>
                                <span style="display:inline-flex;align-items:center;gap:6px"><x-icon name="help-circle" class="h-4 w-4" style="color:#7C5CBF" />{{ $chapter->quizzes_count }} {{ __('kuiz') }}</span>
                            </div>
                        </div>
                        @if (auth()->user()->isStudent())
                            <div style="display:flex;flex-direction:column;align-items:center;gap:4px;flex-shrink:0">
                                <div style="position:relative;width:44px;height:44px">
                                    <svg width="44" height="44" viewBox="0 0 44 44">
                                        <circle cx="22" cy="22" r="18" fill="none" stroke="#EFEEE6" stroke-width="4" />
                                        <circle cx="22" cy="22" r="18" fill="none" stroke="{{ $c['accent'] }}" stroke-width="4" stroke-linecap="round"
                                                stroke-dasharray="113.097" stroke-dashoffset="{{ $ringOffset }}" transform="rotate(-90 22 22)" />
                                    </svg>
                                    <span style="position:absolute;inset:0;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:11px;color:var(--wl-ink)">{{ $wpct }}%</span>
                                </div>
                                <span style="font-size:11px;font-weight:700;color:var(--wl-muted)">{{ __('Progres') }}</span>
                            </div>
                        @endif
                        <span style="display:inline-flex;align-items:center;gap:8px;min-height:44px;padding:0 18px;border-radius:12px;border:1.5px solid {{ $c['accent'] }};color:{{ $c['accent'] }};background:var(--wl-surface);font-family:'Geist',sans-serif;font-weight:800;font-size:14px;flex-shrink:0">
                            <x-icon name="eye" class="h-4 w-4" />{{ __('Lihat') }}<x-icon name="chevron-right" class="h-4 w-4" />
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-dynamic-component>
