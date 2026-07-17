@props(['label', 'active' => false])

{{--
    A top-bar menu that opens on hover. Click and keyboard focus open it too, so it still works
    on touch and for screen readers, where hover does not exist.

    The panel is positioned at `top-full` with its gap made of padding rather than a margin, so
    the space between the button and the menu is still inside the hover area. With a margin the
    cursor would leave the wrapper on the way down and the menu would close before it was reached.
--}}

<div class="relative"
     x-data="{ open: false }"
     @mouseenter="open = true"
     @mouseleave="open = false"
     @focusin="open = true"
     @focusout="open = false"
     @keydown.escape="open = false">
    <button type="button"
            @click="open = ! open"
            :aria-expanded="open ? 'true' : 'false'"
            aria-haspopup="true"
            @class([
                'inline-flex min-h-[44px] items-center gap-1 rounded-control px-3 text-[15px] font-bold transition-colors',
                'bg-brand-soft text-brand' => $active,
                'text-ink-2 hover:bg-surface-2 hover:text-ink' => ! $active,
            ])>
        {{ $label }}
        <x-icon name="chevron-down" class="h-4 w-4" />
    </button>

    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute start-0 top-full z-40 w-48 origin-top-left pt-2">
        <div class="overflow-hidden rounded-card border border-line bg-surface shadow-lift">
            {{ $slot }}
        </div>
    </div>
</div>
