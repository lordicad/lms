@props([
    'action',                 // GET form target
    'grades',                 // Collection<Grade>
    'subjects',               // Collection<Subject> (all subjects; availability drives what's usable)
    'filter' => null,         // App\Support\ContentFilter (resolved selection + chapters)
    'withChapter' => false,   // render the dependent Bab dropdown
    'withDate' => false,      // render a From/To published-date range (params: dari, hingga)
    'allYears' => true,       // show a "Semua tahun" option and allow Subject without a Year
    'variant' => 'cikgu',     // 'cikgu' (teacher/admin) | 'student'
    'chapterParam' => 'bab',
    'resetUrl' => null,
])

{{--
    Shared Year -> Subject -> Chapter filter (brief §1). Order is always Tahun, then Subjek, then
    (optionally) Bab, each dependent on the one before it. The server (App\Support\ContentFilter)
    is the source of truth; this component only makes the picking pleasant. Works without
    JavaScript via the <noscript> submit button - every <select> keeps its server-rendered value.
--}}

@php
    use App\Models\Subject;

    $availabilityById = Subject::availabilityMap();
    $availabilityBySlug = $subjects->mapWithKeys(fn ($s) => [
        $s->slug => array_values($availabilityById[$s->id] ?? []),
    ]);

    $selLevel = $filter?->grade?->level ?? (request()->filled('tahun') ? request()->integer('tahun') : null);
    $selSubjek = $filter?->subject?->slug ?? (request()->filled('subjek') ? request()->string('subjek')->toString() : null);
    $selBab = $filter?->chapter?->id ?? (request()->filled($chapterParam) ? request()->integer($chapterParam) : null);

    $chapters = $withChapter ? ($filter?->chaptersForPair() ?? collect()) : collect();

    // Only the subjects offered in the chosen Year (all subjects when no Year is picked), so the
    // Subject list actually changes as the Year changes rather than greying options out.
    $visibleSubjects = $filter ? $filter->availableSubjects($subjects) : $subjects;

    // A pair someone has deliberately opened stays visible even when the Year does not offer it -
    // otherwise the dropdown would show a different subject than the page below is describing.
    if ($filter?->subject && ! $visibleSubjects->contains('id', $filter->subject->id)) {
        $visibleSubjects = $visibleSubjects->concat([$filter->subject])->values();
    }

    $reset = $resetUrl ?? $action;
    $hasDate = $withDate && request()->filled('tarikh');
    $hasActiveFilters = $selLevel || $selSubjek || $selBab || $hasDate;

    // Preset published-date ranges. The keys are matched server-side (AdminContentController::dateRange);
    // the labels live here so the dropdown reads naturally and translates.
    $datePresets = [
        'today' => __('Hari ini'),
        'yesterday' => __('Semalam'),
        'this_week' => __('Minggu ini'),
        'last_week' => __('Minggu lepas'),
        'last_7d' => __('7 hari lepas'),
        'this_month' => __('Bulan ini'),
        'last_month' => __('Bulan lepas'),
        'last_30d' => __('30 hari lepas'),
    ];
    $selTarikh = request()->string('tarikh')->toString();

    $cls = $variant === 'student'
        ? ['label' => 'ysf-label', 'select' => 'ysf-select']
        : ['label' => 'tp-label', 'select' => 'tp-filter-select'];
@endphp

<form method="GET" action="{{ $action }}" class="ysf-form-{{ $variant }}"
      x-data="yearSubjectFilter({
          level: '{{ $selLevel }}',
          subject: @js($selSubjek ?? ''),
          chapter: '{{ $selBab }}',
          availability: @js($availabilityBySlug),
          allYears: {{ $allYears ? 'true' : 'false' }},
      })"
      style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:14px">

    {{-- An optional search box, rendered first so it reads before the dropdowns. It lives inside
         this form on purpose: a separate one would reset the Tahun/Subjek pair on every search. --}}
    {{ $search ?? '' }}

    {{-- 1. Tahun (Year) --}}
    <div class="tp-field" style="display:flex;flex-direction:column;gap:6px">
        <label for="ysf-tahun" class="{{ $cls['label'] }}">{{ __('Tahun') }}</label>
        <select id="ysf-tahun" name="tahun" class="{{ $cls['select'] }}" style="min-width:150px"
                x-model="level" @change="onYearChange()">
            <option value="">{{ __('Semua tahun') }}</option>
            @foreach ($grades as $grade)
                <option value="{{ $grade->level }}" @selected((int) $selLevel === (int) $grade->level)>
                    {{ $grade->displayName() }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- 2. Subjek (Subject) - depends on the chosen Tahun --}}
    <div class="tp-field" style="display:flex;flex-direction:column;gap:6px">
        <label for="ysf-subjek" class="{{ $cls['label'] }}">{{ __('Subjek') }}</label>
        <select id="ysf-subjek" name="subjek" class="{{ $cls['select'] }}" style="min-width:220px"
                x-model="subject" @change="onSubjectChange()" x-bind:disabled="subjectDisabled">
            <option value="">{{ __('Semua subjek') }}</option>
            @foreach ($visibleSubjects->groupBy('category') as $category => $group)
                <optgroup label="{{ Subject::categoryLabel($category) }}">
                    @foreach ($group as $subject)
                        <option value="{{ $subject->slug }}"
                                @selected($selSubjek === $subject->slug)
                                x-bind:disabled="! subjectOffered('{{ $subject->slug }}') && subject !== '{{ $subject->slug }}'">
                            {{ $subject->displayName() }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @unless ($allYears)
            <p class="tp-hint" x-show="subjectDisabled" x-cloak>{{ __('Pilih tahun dahulu.') }}</p>
        @endunless
    </div>

    {{-- 3. Bab (Chapter) - depends on the chosen Tahun + Subjek --}}
    @if ($withChapter)
        <div class="tp-field" style="display:flex;flex-direction:column;gap:6px">
            <label for="ysf-bab" class="{{ $cls['label'] }}">{{ __('Bab') }}</label>
            <select id="ysf-bab" name="{{ $chapterParam }}" class="{{ $cls['select'] }}" style="min-width:200px"
                    x-model="chapter" @change="onChapterChange()" x-bind:disabled="! subject">
                <option value="">{{ __('Semua bab') }}</option>
                @foreach ($chapters as $chapter)
                    <option value="{{ $chapter->id }}" @selected((int) $selBab === (int) $chapter->id)>
                        {{ __('Bab :number: :title', ['number' => $chapter->number, 'title' => $chapter->title]) }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    {{-- Published-date range (video oversight): a preset picker, resolved to a from/to window on
         the server. A plain <select> so it submits on change and still works without Alpine. --}}
    @if ($withDate)
        <div class="tp-field" style="display:flex;flex-direction:column;gap:6px">
            <label for="ysf-tarikh" class="{{ $cls['label'] }}">{{ __('Tarikh terbit') }}</label>
            <select id="ysf-tarikh" name="tarikh" class="{{ $cls['select'] }}" style="min-width:170px" onchange="this.form.submit()">
                <option value="">{{ __('Semua tarikh') }}</option>
                @foreach ($datePresets as $key => $label)
                    <option value="{{ $key }}" @selected($selTarikh === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <noscript>
        <button type="submit" class="tp-btn-ghost">{{ __('Tapis') }}</button>
    </noscript>

    @if ($hasActiveFilters)
        <a href="{{ $reset }}" class="ysf-reset"
           style="min-height:46px;display:inline-flex;align-items:center;font-weight:800;font-size:13.5px;color:#C24936;text-decoration:none">
            {{ __('Kosongkan') }}
        </a>
    @endif

    {{ $slot }}
</form>
