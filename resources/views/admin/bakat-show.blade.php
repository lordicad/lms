<x-app-layout :title="$result->teacher->name">
    <a href="{{ route('admin.bakat') }}" class="inline-flex items-center gap-2 text-sm font-bold text-ink-2 hover:text-ink">
        <x-icon name="arrow-left" class="h-4 w-4" />
        {{ __('Kembali ke senarai') }}
    </a>

    <header class="mt-4 flex flex-wrap items-center gap-4">
        <x-avatar :user="$result->teacher" size="lg" />
        <div>
            <h1 class="text-3xl font-extrabold text-ink">{{ $result->teacher->name }}</h1>
            <p class="text-ink-2">
                {{ $result->teacher->email ?? '—' }}
                @if ($result->channels > 0)
                    · {{ __(':n channel YouTube disahkan', ['n' => $result->channels]) }}
                @endif
            </p>
        </div>
    </header>

    <section class="card card-pad mt-6">
        <x-talent-scorecard :result="$result" />
    </section>

    <section class="mt-6">
        <h2 class="text-xl font-extrabold text-ink">{{ __('Pecahan mengikut video') }}</h2>

        @if ($result->lessons->isEmpty())
            <x-empty icon="video" :title="__('Belum ada video dikira')"
                     :text="__('Guru ini belum ada video yang dikira untuk skor bakat.')" />
        @else
            <div class="card mt-3 overflow-x-auto p-2">
                <table class="w-full min-w-[36rem] text-sm">
                    <thead>
                        <tr class="border-b border-line text-left text-ink-2">
                            <th class="px-3 py-2 font-semibold">{{ __('Video') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('Milik') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Jangkauan') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Kegemaran') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Tamat tonton') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($result->lessons as $entry)
                            <tr class="border-b border-line/60 last:border-0" style="--sc: {{ $entry->lesson->chapter->subject->rgb }}">
                                <td class="px-3 py-2">
                                    <span class="font-bold text-ink">{{ $entry->lesson->title }}</span>
                                    <span class="block text-xs text-ink-2">{{ $entry->lesson->chapter->subject->name }} · Bab {{ $entry->lesson->chapter->number }}</span>
                                </td>
                                <td class="px-3 py-2"><x-ownership-badge :lesson="$entry->lesson" /></td>
                                <td class="px-3 py-2 text-right tabular-nums text-ink">{{ $entry->reach }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-ink">{{ $entry->favourites }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-ink">{{ $entry->completion }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-app-layout>
