<x-app-layout :title="__('Skor Bakat Saya')">
    @php($references = auth()->user()->lessons()->where('ownership', \App\Models\Lesson::OWNERSHIP_REFERENCE)->with('chapter.subject')->get())

    <header>
        <h1 class="text-3xl font-extrabold text-ink">{{ __('Skor Bakat Saya') }}</h1>
        <p class="mt-1 max-w-prose text-ink-2">
            {{ __('Petunjuk penglibatan murid terhadap video anda sendiri (muat naik + YouTube yang disahkan milik anda). Ia membantu MOE mengenal pasti guru berpotensi untuk semakan lanjut.') }}
        </p>
    </header>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card card-pad">
                <x-talent-scorecard :result="$result" />
            </section>

            <section>
                <h2 class="text-xl font-extrabold text-ink">{{ __('Pecahan mengikut video') }}</h2>

                @if ($result->lessons->isEmpty())
                    <x-empty icon="video" :title="__('Belum ada video dikira')"
                             :text="__('Muat naik video, atau sambungkan channel YouTube anda supaya video YouTube anda dikira.')" />
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
                                            <a href="{{ route('cikgu.video.edit', $entry->lesson) }}" class="font-bold text-ink hover:text-brand">{{ $entry->lesson->title }}</a>
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

                @if ($references->isNotEmpty())
                    <div class="mt-4 rounded-card border border-line bg-surface-2 p-4">
                        <p class="text-sm font-bold text-ink">{{ __('Video rujukan (tidak dikira untuk skor)') }}</p>
                        <ul class="mt-2 space-y-1">
                            @foreach ($references as $reference)
                                <li class="flex flex-wrap items-center gap-2 text-sm text-ink-2">
                                    <x-ownership-badge :lesson="$reference" style="--sc: {{ $reference->chapter->subject->rgb }}" />
                                    <span>{{ $reference->title }}</span>
                                </li>
                            @endforeach
                        </ul>
                        @unless (auth()->user()->youtubeChannels()->exists())
                            <p class="mt-2 text-sm">
                                <a href="{{ route('oauth.youtube.redirect') }}" class="link-muted">{{ __('Sambungkan akaun YouTube anda supaya video anda dikira') }}</a>
                            </p>
                        @endunless
                    </div>
                @endif
            </section>
        </div>

        <div class="space-y-6">
            <x-youtube-connect-card :user="auth()->user()" />
        </div>
    </div>
</x-app-layout>
