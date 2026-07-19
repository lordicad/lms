<x-student-layout :title="__('Subjek')">
    <header class="mb-6 flex flex-wrap items-baseline gap-x-3 gap-y-1">
        <h1 class="text-[22px] font-extrabold text-ink">{{ $grade ? __('Subjek — :grade', ['grade' => $grade->name]) : __('Subjek') }}</h1>
        <p class="text-sm text-ink-2">
            {{ $grade ? __('Pilih subjek untuk melihat bab dan video') : __('Tahun anda belum ditetapkan.') }}
        </p>
    </header>

    @if ($grade && $subjectsByCategory->isNotEmpty())
        <div class="space-y-10">
            @foreach (\App\Models\Subject::CATEGORIES as $category)
                @php($group = $subjectsByCategory[$category] ?? collect())

                @if ($group->isNotEmpty())
                    <section>
                        <h2 class="mb-4 text-xs font-extrabold uppercase tracking-widest text-ink-2">
                            {{ \App\Models\Subject::categoryLabel($category) }}
                        </h2>

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($group as $subject)
                                <x-subject-tile :subject="$subject" :grade="$grade" />
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        </div>
    @else
        <x-empty icon="book" :title="__('Tiada subjek')"
                 :text="__('Sila kemas kini profil anda dan pilih Tahun.')">
            <a href="{{ route('profile.edit') }}" class="btn-primary">{{ __('Kemas Kini Profil') }}</a>
        </x-empty>
    @endif
</x-student-layout>
