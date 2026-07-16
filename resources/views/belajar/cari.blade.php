<x-student-layout :title="__('Cari')">
    <header class="mb-6">
        <h1 class="text-2xl font-extrabold text-ink">{{ __('Cari Video') }}</h1>
        <p class="mt-1 text-ink-2">
            {{ $grade ? __('Mencari dalam :grade.', ['grade' => $grade->name]) : __('Tahun anda belum ditetapkan.') }}
        </p>

        <form method="GET" action="{{ route('cari.index') }}" class="relative mt-4 max-w-xl" role="search">
            <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-2" />
            <input type="search" name="q" value="{{ $query }}" autofocus
                   x-data @input.debounce.500ms="$el.form.requestSubmit()"
                   placeholder="{{ __('Tajuk video...') }}" class="input min-h-[48px] pl-11"
                   aria-label="{{ __('Cari video') }}">
        </form>
    </header>

    @if ($query === '')
        <x-empty icon="search" :title="__('Cari video')"
                 :text="__('Taip tajuk video yang anda cari.')" />
    @elseif ($results->isEmpty())
        <x-empty icon="inbox" :title="__('Tiada hasil')"
                 :text="__('Tiada video sepadan dengan :query. Cuba kata kunci lain.', ['query' => $query])" />
    @else
        <p class="mb-4 text-sm text-ink-2">
            {{ __(':count hasil untuk :query', ['count' => $results->count(), 'query' => $query]) }}
        </p>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            @foreach ($results as $lesson)
                <x-lesson-card :lesson="$lesson" grid />
            @endforeach
        </div>
    @endif
</x-student-layout>
