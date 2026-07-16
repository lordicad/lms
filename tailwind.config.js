import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * LMS MOE design tokens.
 *
 * Colours resolve to CSS custom properties (see resources/css/app.css) so one token set drives
 * both dark (primary) and light mode; markup never needs `dark:` variants. Borders, shadows and
 * radii are full CSS values behind their own variables. Every pairing was checked for WCAG AA.
 */
export default {
    // Theme is driven by CSS variables under a server-rendered `.theme-dark` class, not `dark:`
    // utilities. This keeps any stray `dark:` variant keyed to that class rather than the OS.
    darkMode: ['selector', '.theme-dark'],

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                // Default (teacher surface) stays Nunito; the student surface opts into Geist.
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
                display: ['Geist', ...defaultTheme.fontFamily.sans],
                reading: ['Nunito', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                bg: 'rgb(var(--c-bg) / <alpha-value>)',
                surface: 'rgb(var(--c-surface) / <alpha-value>)',
                'surface-2': 'rgb(var(--c-surface-2) / <alpha-value>)',
                'surface-3': 'rgb(var(--c-surface-3) / <alpha-value>)',
                ink: 'rgb(var(--c-ink) / <alpha-value>)',
                'ink-2': 'rgb(var(--c-ink-2) / <alpha-value>)',
                // Layered borders are alpha values, so they read correctly over any surface.
                line: 'var(--border-subtle)',
                'line-strong': 'var(--border-strong)',
                brand: 'rgb(var(--c-brand) / <alpha-value>)',
                'brand-strong': 'rgb(var(--c-brand-strong) / <alpha-value>)',
                'brand-soft': 'rgb(var(--c-brand-soft) / <alpha-value>)',
                'on-brand': 'rgb(var(--c-on-brand) / <alpha-value>)',
                success: 'rgb(var(--c-success) / <alpha-value>)',
                'success-soft': 'rgb(var(--c-success-soft) / <alpha-value>)',
                warn: 'rgb(var(--c-warn) / <alpha-value>)',
                'warn-soft': 'rgb(var(--c-warn-soft) / <alpha-value>)',
                danger: 'rgb(var(--c-danger) / <alpha-value>)',
                'danger-soft': 'rgb(var(--c-danger-soft) / <alpha-value>)',
                /* Per-subject accent. Injected inline as `--sc` (see Subject::rgb). Identity only. */
                subject: 'rgb(var(--sc, var(--c-brand)) / <alpha-value>)',
                /*
                 * The raw subject colour was tuned for dark backgrounds. On light it washes out,
                 * so text/icons use `subject-ink` (darkened toward near-black for AA on white) and
                 * chip/tile fills use `subject-wash` (tinted toward white). The mix target + amount
                 * come from the theme (--sc-ink-*, --sc-wash-* in app.css); --sc is per-element.
                 */
                'subject-ink': 'color-mix(in oklab, rgb(var(--sc, var(--c-brand))) var(--sc-ink-amt, 78%), var(--sc-ink-mix, #0b1220))',
                'subject-wash': 'color-mix(in oklab, rgb(var(--sc, var(--c-brand))) var(--sc-wash-amt, 12%), var(--sc-wash-mix, #ffffff))',
            },
            borderRadius: {
                control: 'var(--r-sm)',   // 10px
                card: 'var(--r-md)',      // 14px
                panel: 'var(--r-lg)',     // 20px
                hero: 'var(--r-xl)',      // 28px
            },
            boxShadow: {
                card: 'var(--shadow-card)',
                lift: 'var(--shadow-lift)',
                hero: 'var(--shadow-hero)',
            },
            transitionTimingFunction: {
                smooth: 'cubic-bezier(.2,.8,.2,1)',
            },
            maxWidth: {
                prose: '65ch',
                content: '1440px',
            },
        },
    },

    plugins: [forms],
};
