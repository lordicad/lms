@props(['block' => false])

{{--
    BM / EN segmented toggle. Two plain links styled as one pill, no JavaScript: the pages are
    server-rendered, so a full reload is the correct behaviour. The active language is filled
    and carries aria-current. Each segment is a full 44px tall tap target.

    `back()` on the switch route returns the visitor to the page they were on.
--}}

@php($current = app()->getLocale())

<nav aria-label="Tukar bahasa / Switch language"
     {{ $attributes->merge(['class' => 'inline-flex '.($block ? 'w-full ' : '').'rounded-control border border-line bg-surface p-1']) }}>
    @foreach (['ms' => 'BM', 'en' => 'EN'] as $code => $label)
        <a href="{{ route('locale.switch', $code) }}"
           @if ($current === $code) aria-current="true" @endif
           class="inline-flex min-h-[40px] {{ $block ? 'flex-1 ' : 'min-w-[52px] ' }}items-center justify-center rounded-[calc(var(--r-control)-0.25rem)] px-3 text-sm font-extrabold transition-colors
                  {{ $current === $code
                      ? 'bg-brand text-on-brand'
                      : 'text-ink-2 hover:bg-surface-2 hover:text-ink' }}">
            {{ $label }}
            <span class="sr-only">{{ $code === 'ms' ? 'Bahasa Melayu' : 'English' }}</span>
        </a>
    @endforeach
</nav>
