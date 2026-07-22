{{--
    Light / dark toggle. A single icon button, server-rendered like the language pill it sits
    beside: the theme route flips the session + user preference and `back()`s, so a full reload
    applies the new `<html class="theme-dark">` — no JavaScript, no flash of the wrong theme.

    It is a toggle button (aria-pressed = dark active). The icon shows the mode you switch TO:
    a moon while in light, a sun while in dark. Labels are translated (Mod Terang / Mod Gelap).
--}}

@php($isDark = ($theme ?? 'light') === 'dark')
@php($label = $isDark ? __('Mod Terang') : __('Mod Gelap'))

<a href="{{ route('theme.switch', $isDark ? 'light' : 'dark') }}"
   role="button"
   aria-pressed="{{ $isDark ? 'true' : 'false' }}"
   aria-label="{{ $label }}"
   title="{{ $label }}"
   {{ $attributes->merge(['class' => 'inline-flex min-h-[44px] min-w-[44px] shrink-0 items-center justify-center rounded-full border border-line bg-surface text-ink-2 transition-colors duration-150 ease-smooth hover:bg-surface-2 hover:text-ink']) }}>
    <x-icon :name="$isDark ? 'sun' : 'moon'" class="h-5 w-5" />
    <span class="sr-only">{{ $label }}</span>
</a>
