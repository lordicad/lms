import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Sliding highlight for the primary nav: one shared pill that glides between
// tabs on hover/focus and settles on the active tab. The links themselves carry
// no background (see the `pill` variant of the nav-link component) so this pill
// is the only highlight, and CSS handles the movement via a transition.
Alpine.data('navPill', () => ({
    active: null,
    show: false,
    left: 0,
    top: 0,
    width: 0,
    height: 0,

    init() {
        this.active = this.$el.querySelector('a[aria-current="page"]');

        this.$nextTick(() => {
            this.settle();
            // Reveal only after the pill is positioned, so it fades in on the
            // active tab instead of flashing at the top-left corner.
            requestAnimationFrame(() => { this.show = !! this.active; });
        });

        this._onResize = () => this.settle();
        window.addEventListener('resize', this._onResize);
    },

    destroy() {
        window.removeEventListener('resize', this._onResize);
    },

    moveTo(el) {
        if (! el) return;
        this.left = el.offsetLeft;
        this.top = el.offsetTop;
        this.width = el.offsetWidth;
        this.height = el.offsetHeight;
    },

    // Glide back to the tab the server marked active.
    settle() {
        this.moveTo(this.active);
    },

    // Move to whichever nav link the pointer/focus is on (event delegation).
    // Only tabs sitting directly in the bar count: a link inside a dropdown panel
    // is offset from that panel's own wrapper, not from the bar, so following one
    // would throw the pill to a meaningless position.
    follow(event) {
        const link = event.target.closest('a');
        if (link && link.parentElement === this.$el) this.moveTo(link);
    },

    pillStyle() {
        return `left:${this.left}px; top:${this.top}px; width:${this.width}px; height:${this.height}px;`;
    },
}));

Alpine.start();
