{{--
    Shared SEO / social-share head tags for the PUBLIC pages (landing + auth). Include inside <head>.

    Override any field by passing it in: @include('partials.seo', ['seoTitle' => '...', ...]).
    Absolute URLs are built from the live request (url()/url()->current()), not APP_URL, so they are
    correct on the server no matter what APP_URL is set to.
--}}
@php
    $seoTitle ??= __('WeLearn - Belajar di mana-mana, bila-bila masa.');
    $seoDescription ??= __('Platform pembelajaran untuk sekolah rendah. Murid menonton video kelas, mencuba kuiz dan naik ranking. Guru memuat naik rakaman kelas, bahan bantu mengajar dan kuiz.');
    $seoUrl ??= url()->current();
    $seoImage ??= url('/images/welearn-banner.png');
    $seoType ??= 'website';
    $seoLocale = app()->getLocale() === 'en' ? 'en_MY' : 'ms_MY';
    $seoThemeColor = ($theme ?? 'light') === 'dark' ? '#12181f' : '#ffffff';
@endphp
<link rel="canonical" href="{{ $seoUrl }}">
<meta name="robots" content="index, follow">
<meta name="theme-color" content="{{ $seoThemeColor }}">

{{-- Open Graph (Facebook, WhatsApp, LinkedIn, etc.) --}}
<meta property="og:type" content="{{ $seoType }}">
<meta property="og:site_name" content="WeLearn">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoUrl }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta property="og:locale" content="{{ $seoLocale }}">

{{-- Twitter / X card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">
