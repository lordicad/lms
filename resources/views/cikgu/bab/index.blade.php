<x-cikgu-layout
    :title="__('Pengurusan Bab')"
    :heading="__('Bab')"
    :sub="__('Bab dikongsi oleh semua guru mengikut sukatan Kurikulum 2027.')">

    <div style="display:flex;flex-direction:column;gap:18px;max-width:860px">
        {{-- The shared Tahun -> Subjek filter, same as the Video, Bahan and Kuiz pages: the Subjek
             list holds only the subjects that Tahun actually offers. A Tahun is always chosen here,
             so there is no "Semua tahun" — a Bab list needs a definite Subject and Year. --}}
        <x-year-subject-filter
            :action="route('cikgu.bab.index')"
            :grades="$grades"
            :subjects="$subjects"
            :filter="$filter"
            :all-years="false" />

        @if ($subject && $grade)
            <div style="display:flex;align-items:center;gap:14px">
                <span style="width:52px;height:52px;border-radius:14px;background:rgb({{ $subject->rgb }} / .14);color:rgb({{ $subject->rgb }});display:grid;place-items:center;flex-shrink:0"><x-icon :name="$subject->iconName()" class="h-6 w-6" /></span>
                <div style="display:flex;flex-direction:column;gap:3px">
                    <h2 class="tp-g" style="font-size:22px;font-weight:800;letter-spacing:-.01em;color:var(--tp-ink)">{{ $subject->name }}. {{ $grade->name }}</h2>
                    <span style="display:inline-flex;align-items:center;gap:7px;font-size:13.5px;font-weight:700;color:var(--tp-muted)">
                        <span style="width:8px;height:8px;border-radius:50%;background:#2BB39B;flex-shrink:0"></span>
                        {{ __(':count bab tersedia', ['count' => $chapters->count()]) }}
                    </span>
                </div>
            </div>

            @unless ($isOffered)
                <div style="display:flex;gap:10px;background:#FEF0CE;border:1px solid rgba(138,106,18,.25);border-radius:14px;padding:14px 18px;font-size:13.5px;color:#8A6A12">
                    <span>ℹ️</span>
                    <div>{{ __(':subject tidak ditawarkan untuk :grade dalam Kurikulum 2027. Anda tidak boleh menambah bab baharu di sini. Bab lama yang masih mengandungi kandungan ditandakan tidak aktif — sila pindahkan kandungannya ke Tahun yang betul.', ['subject' => $subject->name, 'grade' => $grade->name]) }}</div>
                </div>
            @endunless

            @if ($chapters->isEmpty())
                <div class="tp-empty">
                    <span style="font-size:30px">📚</span>
                    <h3 class="tp-g" style="font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Belum ada bab') }}</h3>
                    <p style="margin:0;font-size:14.5px;color:var(--tp-muted);max-width:420px">{{ __('Tiada bab untuk :subject :grade lagi.', ['subject' => $subject->name, 'grade' => $grade->name]) }}</p>
                </div>
            @else
                {{-- A colour cycles per chapter — the accent bar, number badge and Lihat button
                     all share it, while the meta icons keep a fixed colour per content type. --}}
                @php($palette = [
                    ['accent' => '#17907B', 'tint' => '#DCF2EE'],
                    ['accent' => '#2E6CA8', 'tint' => '#E4EEF9'],
                    ['accent' => '#7C5CBF', 'tint' => '#EDE7F9'],
                    ['accent' => '#D9862B', 'tint' => '#FBEAD3'],
                    ['accent' => '#D9548A', 'tint' => '#FBE0EC'],
                ])
                <div class="tp-list">
                    @foreach ($chapters as $chapter)
                        @php($c = $palette[$loop->index % count($palette)])
                        <div class="tp-listcard" style="border-left:4px solid {{ $c['accent'] }};{{ $chapter->is_active ? '' : 'opacity:.7' }}">
                            <span style="width:52px;height:52px;border-radius:14px;background:{{ $c['tint'] }};color:{{ $c['accent'] }};display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:20px;flex-shrink:0">{{ $chapter->number }}</span>

                            <div style="display:flex;flex-direction:column;gap:7px;min-width:0;flex:1">
                                <span class="tp-g" style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;font-weight:800;font-size:17px;color:var(--tp-ink)">
                                    {{ $chapter->title }}
                                    @unless ($chapter->is_active)
                                        <span class="tp-tag" style="background:#FEF0CE;color:#8A6A12">{{ __('Tidak aktif') }}</span>
                                    @endunless
                                </span>
                                <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap">
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="video" class="h-4 w-4" style="color:#17907B" />{{ $chapter->lessons_count }} video</span>
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="file" class="h-4 w-4" style="color:#2E6CA8" />{{ $chapter->materials_count }} {{ __('bahan') }}</span>
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="help-circle" class="h-4 w-4" style="color:#7C5CBF" />{{ $chapter->quizzes_count }} {{ __('kuiz') }}</span>
                                </div>
                            </div>

                            <a href="{{ route('cikgu.bab.show', $chapter) }}" style="flex-shrink:0;display:inline-flex;align-items:center;gap:8px;min-height:44px;padding:0 18px;border-radius:12px;border:1.5px solid {{ $c['accent'] }};color:{{ $c['accent'] }};background:var(--tp-surface);font-family:'Geist',sans-serif;font-weight:800;font-size:14px;text-decoration:none"><x-icon name="eye" class="h-4 w-4" />{{ __('Lihat') }}<x-icon name="chevron-right" class="h-4 w-4" /></a>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

</x-cikgu-layout>
