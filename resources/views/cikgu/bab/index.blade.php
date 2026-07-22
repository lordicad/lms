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
            <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">{{ $subject->name }}. {{ $grade->name }}</h2>

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
                <div class="tp-list">
                    @foreach ($chapters as $chapter)
                        <div class="tp-listcard" style="{{ $chapter->is_active ? '' : 'opacity:.7' }}">
                            <span style="width:40px;height:40px;border-radius:12px;background:#E4EEF9;color:#2E6CA8;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;flex-shrink:0">{{ $chapter->number }}</span>

                            <div style="display:flex;flex-direction:column;gap:4px;min-width:0;flex:1">
                                <span class="tp-g" style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;font-weight:800;font-size:15px;color:var(--tp-ink)">
                                    {{ $chapter->title }}
                                    @unless ($chapter->is_active)
                                        <span class="tp-tag" style="background:#FEF0CE;color:#8A6A12">{{ __('Tidak aktif') }}</span>
                                    @endunless
                                </span>
                                <div style="display:flex;align-items:center;gap:12px">
                                    <span class="tp-meta">🎬 {{ $chapter->lessons_count }}</span>
                                    <span class="tp-meta">📄 {{ $chapter->materials_count }}</span>
                                    <span class="tp-meta">📝 {{ $chapter->quizzes_count }}</span>
                                </div>
                            </div>

                            <a href="{{ route('cikgu.bab.show', $chapter) }}" class="tp-btn-ghost" style="flex-shrink:0">👁 {{ __('Lihat') }}</a>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

</x-cikgu-layout>
