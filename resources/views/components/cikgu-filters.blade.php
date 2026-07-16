@props(['subjects', 'grades', 'action'])

{{-- Subject and Tahun filter, shared by the video, bahan and kuiz lists. --}}

<form method="GET" action="{{ $action }}" class="flex flex-wrap items-end gap-3">
    <div>
        <label for="filter-subjek" class="label mb-1">{{ __('Subjek') }}</label>

        <select id="filter-subjek" name="subjek" class="input min-h-[44px] py-2" onchange="this.form.submit()">
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

    <div>
        <label for="filter-tahun" class="label mb-1">{{ __('Tahun') }}</label>

        <select id="filter-tahun" name="tahun" class="input min-h-[44px] py-2" onchange="this.form.submit()">
            <option value="">{{ __('Semua tahun') }}</option>
            @foreach ($grades as $grade)
                <option value="{{ $grade->level }}" @selected((int) request('tahun') === $grade->level)>
                    {{ $grade->name }}
                </option>
            @endforeach
        </select>
    </div>

    <noscript>
        <button type="submit" class="btn-secondary btn-sm">{{ __('Tapis') }}</button>
    </noscript>

    @if (request()->hasAny(['subjek', 'tahun']))
        <a href="{{ $action }}" class="btn-ghost btn-sm">{{ __('Kosongkan') }}</a>
    @endif
</form>
