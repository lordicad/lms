<x-student-layout :title="__('Subjek')">
    <header class="mb-6">
        <h1 class="text-2xl font-extrabold text-ink">{{ __('Subjek') }}</h1>
        <p class="mt-1 text-ink-2">
            {{ $grade ? __('Subjek untuk :grade dalam Kurikulum 2027.', ['grade' => $grade->name]) : __('Tahun anda belum ditetapkan.') }}
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
