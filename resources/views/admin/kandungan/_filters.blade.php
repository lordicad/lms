{{-- Shared Subjek + Tahun filter for the three Kandungan tables. Needs $subjects, $grades, $action.
     Each select alone or together narrows the list; the counts above follow. --}}
<form method="GET" action="{{ $action }}" style="display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap">
    <div style="display:flex;flex-direction:column;gap:6px">
        <label style="font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;color:#6C6F87">{{ __('Subjek') }}</label>
        <select name="subjek" class="tp-filter-select" style="min-width:220px" onchange="this.form.submit()">
            <option value="">{{ __('Semua subjek') }}</option>
            @foreach ($subjects as $subject)
                <option value="{{ $subject->slug }}" @selected(request('subjek') === $subject->slug)>{{ $subject->displayName() }}</option>
            @endforeach
        </select>
    </div>

    <div style="display:flex;flex-direction:column;gap:6px">
        <label style="font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;color:#6C6F87">{{ __('Tahun') }}</label>
        <select name="tahun" class="tp-filter-select" style="min-width:150px" onchange="this.form.submit()">
            <option value="">{{ __('Semua tahun') }}</option>
            @foreach ($grades as $grade)
                <option value="{{ $grade->level }}" @selected((string) request('tahun') === (string) $grade->level)>{{ $grade->name }}</option>
            @endforeach
        </select>
    </div>

    <noscript><button type="submit" class="tp-btn-ghost">{{ __('Tapis') }}</button></noscript>

    @if (request('subjek') || request('tahun'))
        <a href="{{ $action }}" class="tp-btn-ghost">{{ __('Kosongkan') }}</a>
    @endif
</form>
