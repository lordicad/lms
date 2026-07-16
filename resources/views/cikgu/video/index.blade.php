<x-app-layout :title="__('Video Saya')">
    <header class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-ink">{{ __('Video Saya') }}</h1>
            <p class="mt-1 text-ink-2">{{ __('Rakaman kelas yang anda muat naik atau pautkan dari YouTube.') }}</p>
        </div>

        <a href="{{ route('cikgu.video.create') }}" class="btn-primary">
            <x-icon name="plus" class="h-5 w-5" />
            {{ __('Video Baharu') }}
        </a>
    </header>

    <div class="mt-6">
        <x-cikgu-filters :subjects="$subjects" :grades="$grades" :action="route('cikgu.video.index')" />
    </div>

    <section class="mt-6">
        <h2 class="sr-only">{{ __('Senarai video') }}</h2>

        @if ($lessons->isEmpty())
            <x-empty emoji="🎬" :title="__('Belum ada video')"
                     :text="__('Muat naik rakaman kelas anda, atau tampal pautan YouTube dari akaun anda sendiri.')">
                <a href="{{ route('cikgu.video.create') }}" class="btn-primary">{{ __('Tambah Video Pertama') }}</a>
            </x-empty>
        @else
            @php($hasConnectedChannel = auth()->user()->youtubeChannels()->exists())
            <ul class="space-y-3">
                @foreach ($lessons as $lesson)
                    <li class="card flex flex-wrap items-center gap-4 p-4"
                        style="--sc: {{ $lesson->chapter->subject->rgb }}">

                        <span class="relative block h-16 w-28 shrink-0 overflow-hidden rounded-control bg-surface-2">
                            @if ($lesson->thumbnailUrl())
                                <img src="{{ $lesson->thumbnailUrl() }}" alt="" loading="lazy"
                                     class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-2xl" aria-hidden="true">🎬</span>
                            @endif
                        </span>

                        <span class="min-w-0 flex-1">
                            <a href="{{ route('video.show', $lesson) }}"
                               class="block truncate font-extrabold text-ink hover:text-brand">
                                {{ $lesson->title }}
                            </a>

                            <span class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink-2">
                                <span class="chip bg-subject-wash text-subject-ink">
                                    {{ $lesson->chapter->subject->name }}
                                </span>

                                <span>{{ $lesson->chapter->grade->name }}</span>
                                <span>Bab {{ $lesson->chapter->number }}</span>

                                @unless ($lesson->chapter->is_active)
                                    <span class="chip bg-warn-soft text-warn">{{ __('Bab tidak lagi dalam kurikulum — sila pindahkan') }}</span>
                                @endunless

                                <x-ownership-badge :lesson="$lesson" />

                                @if ($lesson->isReference() && ! $hasConnectedChannel)
                                    <a href="{{ route('oauth.youtube.redirect') }}" class="link-muted">{{ __('Sambungkan akaun YouTube') }}</a>
                                @endif

                                <span class="flex items-center gap-1">
                                    <x-icon name="eye" class="h-4 w-4" />
                                    {{ $lesson->views_count }}
                                </span>
                            </span>
                        </span>

                        <span class="flex shrink-0 flex-wrap items-center gap-2">
                            {{-- Publish toggle. A POST, because it changes state. --}}
                            <form method="POST" action="{{ route('cikgu.video.terbit', $lesson) }}">
                                @csrf

                                <button type="submit"
                                        class="chip min-h-[38px] px-3 {{ $lesson->is_published ? 'bg-success-soft text-success' : 'bg-surface-2 text-ink-2' }}">
                                    <x-icon :name="$lesson->is_published ? 'eye' : 'eye-off'" class="h-4 w-4" />
                                    {{ $lesson->is_published ? __('Terbit') : __('Draf') }}
                                </button>
                            </form>

                            <a href="{{ route('cikgu.video.edit', $lesson) }}" class="btn-secondary btn-sm">
                                <x-icon name="pencil" class="h-4 w-4" />
                                {{ __('Sunting') }}
                            </a>

                            <form method="POST" action="{{ route('cikgu.video.destroy', $lesson) }}"
                                  onsubmit='return confirm(@js(__("Padam video \":title\"? Fail video juga akan dipadam. Tindakan ini tidak boleh dibatalkan.", ["title" => $lesson->title])))'>
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="btn-ghost btn-sm text-danger hover:bg-danger-soft">
                                    <x-icon name="trash" class="h-4 w-4" />
                                    <span class="sr-only">{{ __('Padam :title', ['title' => $lesson->title]) }}</span>
                                </button>
                            </form>
                        </span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">
                {{ $lessons->links() }}
            </div>
        @endif
    </section>
</x-app-layout>
