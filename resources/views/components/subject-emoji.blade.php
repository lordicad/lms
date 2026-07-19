@props(['subject', 'class' => 'text-2xl'])

{{--
    Emoji glyph per subject, matching the WeLearn prototype (which labels each subject with an
    emoji rather than a vector icon). Mapped by slug, with a per-category fallback so a new or
    unmapped subject still gets a sensible glyph. Decorative — every subject also carries its name.
--}}

@php
    $map = [
        'bahasa-melayu' => '📖',
        'bahasa-inggeris' => '🔤',
        'bahasa-cina-sjk' => '🀄',
        'bahasa-tamil-sjk' => '🌸',
        'matematik' => '🔢',
        'pendidikan-islam' => '🕌',
        'pendidikan-moral' => '🤝',
        'alam-dan-manusia-pembelajaran-bersepadu' => '🌍',
        'eksplorasi-seni-dan-dunia-pembelajaran-bersepadu' => '🎨',
        'eksplorasi-sains-dan-teknologi-pembelajaran-bersepadu' => '💡',
        'sejarah' => '📜',
        'sains' => '🔬',
        'pendidikan-jasmani' => '⚽',
        'pendidikan-jasmani-dan-pendidikan-kesihatan' => '⚽',
        'pendidikan-seni-visual' => '🎨',
        'pendidikan-muzik' => '🎵',
        'teknologi-dan-digital' => '💻',
        'pendidikan-asas-individu-ketidakupayaan-penglihatan' => '📿',
        'bahasa-isyarat-malaysia' => '🤟',
        'pengurusan-kehidupan-masalah-pembelajaran' => '🧩',
        'bahasa-cina-sk' => '🀄',
        'bahasa-tamil-sk' => '🌸',
        'bahasa-iban' => '🛶',
        'bahasa-kadazandusun' => '⛰️',
        'bahasa-semai' => '🌿',
        'bahasa-arab' => '🕋',
        'pembentukan-karakter' => '🌟',
    ];

    $categoryFallback = [
        'teras' => '📚',
        'wajib' => '⭐',
        'wajib_mbpk' => '♿',
        'tambahan' => '🗣️',
        'program' => '🌟',
    ];

    $emoji = $map[$subject->slug] ?? ($categoryFallback[$subject->category] ?? '📚');
@endphp

<span {{ $attributes->merge(['class' => $class]) }} aria-hidden="true">{{ $emoji }}</span>
