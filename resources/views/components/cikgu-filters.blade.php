@props(['subjects', 'grades', 'action'])

{{-- Subject and Tahun filter, shared by the video, bahan and kuiz lists (WeLearn Cikgu design). --}}

<form method="GET" action="{{ $action }}" style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:14px">
    <div class="tp-field">
        <label for="filter-subjek" class="tp-label">{{ __('Subjek') }}</label>

        <select id="filter-subjek" name="subjek" class="tp-filter-select" style="min-width:220px" onchange="this.form.submit()">
            <option value="">{{ __('Semua subjek') }}</option>
            @foreach ($subjects->groupBy('category') as $category => $group)
                <optgroup label="{{ \App\Models\Subject::categoryLabel($category) }}">
                    @foreach ($group as $subject)
                        <option value="{{ $subject->slug }}" @selected(request('subjek') === $subject->slug)>
                            {{ $subject->displayName() }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>

    <div class="tp-field">
        <label for="filter-tahun" class="tp-label">{{ __('Tahun') }}</label>

        <select id="filter-tahun" name="tahun" class="tp-filter-select" style="min-width:150px" onchange="this.form.submit()">
            <option value="">{{ __('Semua tahun') }}</option>
            @foreach ($grades as $grade)
                <option value="{{ $grade->level }}" @selected((int) request('tahun') === $grade->level)>
                    {{ $grade->name }}
                </option>
            @endforeach
        </select>
    </div>

    <noscript>
        <button type="submit" class="tp-btn-ghost">{{ __('Tapis') }}</button>
    </noscript>

    @if (request()->hasAny(['subjek', 'tahun']))
        <a href="{{ $action }}" style="min-height:46px;display:inline-flex;align-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:#6C6F87">{{ __('Kosongkan') }}</a>
    @endif

    {{ $slot }}
</form>
