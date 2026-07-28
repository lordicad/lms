<x-dynamic-component :component="auth()->user()->isTeacher() ? 'app-layout' : 'student-layout'" :title="$quiz->title">
    <div class="mx-auto max-w-2xl" style="--sc: {{ $subject->rgb }}">
        <a href="{{ route('bab.show', $chapter) }}"
           class="inline-flex items-center gap-2 text-sm font-bold text-ink-2 hover:text-ink">
            <x-icon name="arrow-left" class="h-4 w-4" />
            Bab {{ $chapter->number }}: {{ $chapter->title }}
        </a>

        <div class="card card-pad mt-4">
            <div class="flex items-start justify-between gap-6">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="chip bg-subject-wash text-subject-ink"><x-subject-icon :subject="$subject" class="h-4 w-4" /> {{ $subject->name }}</span>
                        <span class="chip bg-surface-2 text-ink-2">{{ __('Kuiz Bercetak') }}</span>
                    </div>

                    <h1 class="mt-3 text-3xl font-extrabold text-ink">{{ $quiz->title }}</h1>

                    @if ($quiz->description)
                        <p class="mt-3 max-w-prose text-ink-2">{{ $quiz->description }}</p>
                    @endif
                </div>

                {{-- Decorative clipboard-and-clock, in the subject teal. Hidden on narrow screens. --}}
                <div class="hidden shrink-0 sm:block" style="width:150px;margin-right:24px;transform:rotate(14deg)" aria-hidden="true">
                    <svg viewBox="0 0 210 180" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:100%;height:auto">
                        <circle cx="150" cy="56" r="44" fill="#E7F4EF" />
                        <circle cx="96" cy="122" r="32" fill="#EDF7F3" />
                        <g fill="#C7E3DA">
                            @for ($r = 0; $r < 4; $r++)
                                @for ($c = 0; $c < 4; $c++)
                                    <circle cx="{{ 42 + $c * 8 }}" cy="{{ 108 + $r * 8 }}" r="2.1" />
                                @endfor
                            @endfor
                        </g>
                        <rect x="76" y="34" width="94" height="120" rx="12" fill="#fff" stroke="#17907B" stroke-width="3.5" />
                        <rect x="102" y="26" width="42" height="18" rx="6" fill="#17907B" />
                        <circle cx="123" cy="30" r="4.5" fill="#fff" />
                        @foreach ([62, 92, 122] as $y)
                            <rect x="90" y="{{ $y }}" width="16" height="16" rx="4" fill="#DCF2EE" stroke="#17907B" stroke-width="2.5" />
                            <rect x="114" y="{{ $y + 3 }}" width="42" height="4" rx="2" fill="#7CC4B3" />
                            <rect x="114" y="{{ $y + 11 }}" width="30" height="4" rx="2" fill="#C7E3DA" />
                        @endforeach
                        <circle cx="170" cy="132" r="30" fill="#fff" stroke="#17907B" stroke-width="3.5" />
                        <path d="M170 116 v16 h11" stroke="#17907B" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />
                        <circle cx="170" cy="132" r="2.6" fill="#17907B" />
                    </svg>
                </div>
            </div>

            {{-- File facts: format, page count (when the PDF exposes it) and when it was created. --}}
            @php($pages = $quiz->pageCount())
            <div class="mt-6 flex flex-wrap items-center gap-x-7 gap-y-5 border-t border-line pt-6">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-surface-2 text-ink"><x-icon name="file" class="h-5 w-5" /></span>
                    <span class="flex flex-col gap-0.5">
                        <span class="text-xs font-semibold text-ink-2">{{ __('Format') }}</span>
                        <span class="text-base font-extrabold text-ink">{{ $quiz->extension() }}</span>
                    </span>
                </div>

                @if ($pages)
                    <span class="hidden h-9 w-px bg-line sm:block"></span>
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-surface-2 text-ink"><x-icon name="printer" class="h-5 w-5" /></span>
                        <span class="flex flex-col gap-0.5">
                            <span class="text-xs font-semibold text-ink-2">{{ __('Halaman') }}</span>
                            <span class="text-base font-extrabold text-ink">{{ __(':count halaman', ['count' => $pages]) }}</span>
                        </span>
                    </div>
                @endif

                <span class="hidden h-9 w-px bg-line sm:block"></span>
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-surface-2 text-ink"><x-icon name="clock" class="h-5 w-5" /></span>
                    <span class="flex flex-col gap-0.5">
                        <span class="text-xs font-semibold text-ink-2">{{ __('Dicipta') }}</span>
                        <span class="text-base font-extrabold text-ink">{{ $quiz->created_at->translatedFormat('j M Y') }}</span>
                    </span>
                </div>
            </div>

            <div class="mt-6 flex items-start gap-4" style="background:#EEF2FB;border-radius:16px;padding:18px 22px;color:#3F4A5F">
                <span style="flex-shrink:0;color:#2E6CA8;margin-top:1px"><x-icon name="info-circle" style="width:24px;height:24px" /></span>
                <p style="margin:0;font-size:15px;line-height:1.6">
                    {{ __('Kuiz ini disediakan sebagai fail untuk dicetak atau dijawab di atas kertas.') }}
                    {{ __('Ia tidak disemak secara automatik dan tidak memberi mata ranking.') }}
                </p>
            </div>

            @if ($quiz->file_path)
                <a href="{{ route('muat-turun.kuiz', $quiz) }}" class="btn-primary mt-6 w-full">
                    <x-icon name="download" class="h-5 w-5" />
                    {{ __('Muat Turun Kuiz') }}
                </a>
            @else
                <x-alert type="warn" class="mt-6">{{ __('Fail kuiz tidak dijumpai. Sila hubungi cikgu anda.') }}</x-alert>
            @endif
        </div>
    </div>
</x-dynamic-component>
