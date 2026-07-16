@props(['user'])

{{-- "Sambungkan YouTube": prove channel ownership so a teacher's own YouTube videos count toward
     the talent signal. We read only the channel list — no tokens are ever stored. --}}

@php($channels = $user->youtubeChannels()->latest('verified_at')->get())

<section {{ $attributes->merge(['class' => 'card card-pad']) }}>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-ink">{{ __('Sambungkan YouTube') }}</h2>
            <p class="mt-1 max-w-prose text-sm text-ink-2">
                {{ __('Sahkan pemilikan channel YouTube anda supaya video YouTube anda sendiri dikira untuk skor bakat. Kami hanya membaca senarai channel anda — tiada token disimpan.') }}
            </p>
        </div>

        @if (\App\Http\Controllers\YoutubeConnectController::isConfigured())
            <a href="{{ route('oauth.youtube.redirect') }}" class="btn-primary btn-sm shrink-0">
                <x-icon name="youtube" class="h-4 w-4" />
                {{ $channels->isEmpty() ? __('Sambung Akaun') : __('Sambung Lagi') }}
            </a>
        @else
            <span class="chip shrink-0 bg-surface-2 text-ink-2">{{ __('Belum tersedia') }}</span>
        @endif
    </div>

    @if ($channels->isEmpty())
        <p class="mt-4 rounded-card bg-surface-2 p-4 text-sm text-ink-2">
            {{ __('Belum ada channel disambung. Video YouTube anda kini dikira sebagai rujukan sehingga anda menyambung.') }}
        </p>
    @else
        <ul class="mt-4 space-y-2">
            @foreach ($channels as $channel)
                <li class="flex items-center gap-3 rounded-card border border-line p-3">
                    @if ($channel->thumbnail_url)
                        <img src="{{ $channel->thumbnail_url }}" alt="" loading="lazy" class="h-10 w-10 shrink-0 rounded-full">
                    @else
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-soft text-brand">
                            <x-icon name="youtube" class="h-5 w-5" />
                        </span>
                    @endif

                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-bold text-ink">{{ $channel->title }}</span>
                        <span class="block text-xs text-ink-2">{{ __('Disahkan :date', ['date' => $channel->verified_at->translatedFormat('d M Y')]) }}</span>
                    </span>

                    <form method="POST" action="{{ route('oauth.youtube.disconnect', $channel) }}"
                          onsubmit='return confirm(@js(__("Putuskan sambungan channel ini? Video YouTube dari channel ini tidak akan lagi dikira untuk skor bakat anda.")))'>
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="btn-ghost btn-sm text-danger hover:bg-danger-soft">
                            {{ __('Putuskan') }}
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif
</section>
