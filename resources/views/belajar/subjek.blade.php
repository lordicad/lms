<x-dynamic-component :component="auth()->user()->isTeacher() ? 'app-layout' : 'student-layout'" :title="$subject->name.' '.$grade->name">
    @php($col = $subject->color ?: '#17907B')
    @php($selGrad = "linear-gradient(135deg, color-mix(in oklab, {$col} 30%, #fff), color-mix(in oklab, {$col} 12%, #fff))")

    <div style="display:flex;flex-direction:column;gap:20px">
        <a href="{{ route('belajar.index', ['tahun' => $grade->level]) }}" class="wl-back"
           style="align-self:flex-start;display:flex;align-items:center;gap:8px;font-family:'Geist',sans-serif;font-size:14px;font-weight:800;color:#6C6F87;text-decoration:none;padding:6px 0">← {{ __('Semua subjek') }}</a>

        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
            <span style="width:56px;height:56px;border-radius:16px;background:{{ $selGrad }};display:grid;place-items:center;font-size:25px"><x-subject-emoji :subject="$subject" style="font-size:25px" /></span>
            <div style="display:flex;flex-direction:column;gap:2px;margin-right:auto">
                <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:26px;font-weight:800;letter-spacing:-.01em;color:#28293F">{{ $subject->name }}</h2>
                <span style="font-size:14px;font-weight:700;color:#8B8AA3">{{ $grade->name }}</span>
            </div>
            <label style="display:flex;flex-direction:column;gap:5px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;color:#28293F">
                {{ __('Tukar Tahun') }}
                <select onchange="if (this.value) window.location = '{{ url('/belajar/'.$subject->slug) }}/' + this.value"
                        style="min-height:44px;border:1px solid rgba(46,44,80,.12);border-radius:12px;padding:0 36px 0 14px;-webkit-appearance:none;-moz-appearance:none;appearance:none;background:#fff url(&quot;data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20width='24'%20height='24'%20viewBox='0%200%2024%2024'%20fill='none'%20stroke='%2328293F'%20stroke-width='2.5'%20stroke-linecap='round'%20stroke-linejoin='round'%3E%3Cpath%20d='M6%209l6%206%206-6'/%3E%3C/svg%3E&quot;) no-repeat right 12px center;background-size:12px;font-family:'Geist',sans-serif;font-weight:700;font-size:14px;color:#28293F;cursor:pointer">
                    @foreach ($grades as $option)
                        <option value="{{ $option->level }}" @selected($option->level === $grade->level)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        @if ($chapters->isEmpty())
            <div style="background:#fff;border:1px solid rgba(46,44,80,.07);border-radius:18px;padding:44px;display:flex;flex-direction:column;align-items:center;gap:8px;text-align:center">
                <span style="width:44px;height:44px;border-radius:50%;background:#F1F0E8;display:grid;place-items:center;font-size:18px">📚</span>
                <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:#28293F">{{ __('Belum ada bab untuk subjek ini') }}</span>
                <span style="font-size:13.5px;color:#8B8AA3">{{ __('Cikgu belum menyediakan bab untuk :subject :grade.', ['subject' => $subject->name, 'grade' => $grade->name]) }}</span>
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:14px">
                @foreach ($chapters as $chapter)
                    @php($total = $chapter->lessons_count)
                    @php($watched = (int) ($watchedByChapter[$chapter->id] ?? 0))
                    @php($wpct = $total > 0 ? (int) round($watched / $total * 100) : 0)
                    <a href="{{ route('bab.show', $chapter) }}" class="wl-row-lift"
                       style="background:#fff;border:1px solid rgba(46,44,80,.07);border-radius:16px;padding:18px 22px;display:flex;align-items:center;gap:18px;box-shadow:0 3px 12px rgba(46,44,80,.04);cursor:pointer;text-decoration:none">
                        <span style="width:44px;height:44px;border-radius:12px;background:#E4EEF9;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:16px;color:#2E6CA8;flex-shrink:0">{{ $chapter->number }}</span>
                        <div style="display:flex;flex-direction:column;gap:4px;margin-right:auto;min-width:0">
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15.5px;color:#28293F">Bab {{ $chapter->number }}: {{ $chapter->title }}</span>
                            <div style="display:flex;gap:16px;font-size:13px;color:#6C6F87;flex-wrap:wrap">
                                <span>🎬 {{ $chapter->lessons_count }} video</span>
                                <span>📄 {{ $chapter->materials_count }} {{ __('bahan') }}</span>
                                <span>📝 {{ $chapter->quizzes_count }} {{ __('kuiz') }}</span>
                            </div>
                        </div>
                        @if (auth()->user()->isStudent() && $total > 0)
                            <div style="display:flex;flex-direction:column;gap:6px;width:160px;flex-shrink:0">
                                <div style="display:flex;justify-content:space-between;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;color:#6C6F87">
                                    <span>{{ __('Ditonton') }}</span><span>{{ $watched }}/{{ $total }}</span>
                                </div>
                                <div style="height:6px;border-radius:999px;background:#EFEEE6;overflow:hidden">
                                    <div style="height:100%;border-radius:999px;background:#2BB39B;width:{{ $wpct }}%"></div>
                                </div>
                            </div>
                        @endif
                        <span style="color:#8B8AA3;font-size:16px;flex-shrink:0">›</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-dynamic-component>
