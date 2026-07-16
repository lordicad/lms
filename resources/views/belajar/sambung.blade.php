<x-student-layout :title="__('Sambung Menonton')">
    <header class="mb-6">
        <h1 class="text-2xl font-extrabold text-ink">{{ __('Sambung Menonton') }}</h1>
        <p class="mt-1 text-ink-2">{{ __('Video yang belum habis anda tonton.') }}</p>
    </header>

    @if ($lessons->isEmpty())
        <x-empty icon="video" :title="__('Tiada video untuk disambung')"
                 :text="__('Mula tonton sesuatu, dan ia akan muncul di sini supaya anda boleh sambung kemudian.')">
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
