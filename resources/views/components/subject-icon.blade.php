@props(['subject', 'class' => 'h-6 w-6'])

{{-- A vector glyph per subject, rendered in the subject's colour. Replaces the emoji icons. --}}

@php
    $map = [
        'bahasa-melayu' => 'book',
        'bahasa-inggeris' => 'language',
        'bahasa-cina-sjk' => 'language',
        'bahasa-tamil-sjk' => 'language',
        'matematik' => 'calculator',
        'pendidikan-islam' => 'moon',
        'pendidikan-moral' => 'scale',
        'alam-dan-manusia-pembelajaran-bersepadu' => 'world',
        'eksplorasi-seni-dan-dunia-pembelajaran-bersepadu' => 'palette',
        'eksplorasi-sains-dan-teknologi-pembelajaran-bersepadu' => 'bulb',
        'sejarah' => 'history',
        'sains' => 'flask',
        'pendidikan-jasmani' => 'run',
        'pendidikan-jasmani-dan-pendidikan-kesihatan' => 'run',
        'pendidikan-seni-visual' => 'palette',
        'pendidikan-muzik' => 'music',
        'teknologi-dan-digital' => 'laptop',
        'pendidikan-asas-individu-ketidakupayaan-penglihatan' => 'accessible',
        'bahasa-isyarat-malaysia' => 'hand',
        'pengurusan-kehidupan-masalah-pembelajaran' => 'puzzle',
        'bahasa-cina-sk' => 'language',
        'bahasa-tamil-sk' => 'language',
        'bahasa-iban' => 'language',
        'bahasa-kadazandusun' => 'language',
        'bahasa-semai' => 'language',
        'bahasa-arab' => 'language',
        'pembentukan-karakter' => 'star',
    ];

    $categoryFallback = [
        'teras' => 'book',
        'wajib' => 'star',
        'wajib_mbpk' => 'accessible',
        'tambahan' => 'language',
        'program' => 'star',
    ];

    $name = $map[$subject->slug] ?? ($categoryFallback[$subject->category] ?? 'book');
@endphp

<span style="--sc: {{ $subject->rgb }}" class="inline-flex text-subject-ink" aria-hidden="true">
    <x-icon :name="$name" :class="$class" />
</span>
