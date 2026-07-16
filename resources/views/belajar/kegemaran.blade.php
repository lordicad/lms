<x-student-layout :title="__('Kegemaran')">
    <header class="mb-6">
        <h1 class="text-2xl font-extrabold text-ink">{{ __('Kegemaran Saya') }}</h1>
        <p class="mt-1 text-ink-2">{{ __('Video yang anda simpan untuk ditonton semula.') }}</p>
    </header>

    @if ($lessons->isEmpty())
        <x-empty icon="heart" :title="__('Belum ada kegemaran')"
                 :text="__('Tekan ikon hati pada mana-mana video untuk menyimpannya di sini.')">
            <a href="{{ route('belajar.index') }}" class="btn-primary">{{ __('Cari Video') }}</a>
        </x-empty>
    @else
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            @foreach ($lessons as $lesson)
                <x-lesson-card :lesson="$lesson" grid />
            @endforeach
        </div>
    @endif
</x-student-layout>
