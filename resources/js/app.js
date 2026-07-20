import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

// A single shared chart integration (brief §6). Every chart in the app is an <x-chart> that renders
// a <canvas> plus an accessible table/list of the same values, and mounts through this one Alpine
// component. Respects prefers-reduced-motion, resizes responsively, and reads text/grid colours from
// the active theme so it works in light and dark. Pass a standard Chart.js config as `config`.
const prefersReducedMotion = () =>
    window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

Alpine.data('appChart', (config = {}) => ({
    chart: null,

    init() {
        const canvas = this.$refs.canvas;
        if (! canvas) return;

        const styles = getComputedStyle(document.documentElement);
        const ink = (styles.getPropertyValue('--c-ink-2') || styles.getPropertyValue('--c-ink') || '#5B6675').trim();
        const line = (styles.getPropertyValue('--c-line') || 'rgba(120,130,150,0.2)').trim();

        Chart.defaults.color = ink || '#5B6675';
        Chart.defaults.font.family = "'Nunito', system-ui, sans-serif";

        const base = {
            responsive: true,
            maintainAspectRatio: false,
            animation: prefersReducedMotion() ? false : { duration: 400 },
            plugins: {
                legend: { labels: { color: ink, usePointStyle: true, font: { weight: '600' } } },
                tooltip: { enabled: true },
            },
        };

        const merged = {
            ...config,
            options: this.deepMerge(base, config.options || {}),
        };

        // Colour the axis grid/ticks for scale-based charts without clobbering caller options.
        if (merged.options.scales) {
            for (const axis of Object.values(merged.options.scales)) {
                axis.grid = { color: line, ...(axis.grid || {}) };
                axis.ticks = { color: ink, ...(axis.ticks || {}) };
            }
        }

        this.chart = new Chart(canvas, merged);
    },

    deepMerge(target, source) {
        const out = { ...target };
        for (const key of Object.keys(source)) {
            const sv = source[key];
            if (sv && typeof sv === 'object' && ! Array.isArray(sv) && out[key] && typeof out[key] === 'object') {
                out[key] = this.deepMerge(out[key], sv);
            } else {
                out[key] = sv;
            }
        }
        return out;
    },

    // Swap the dataset/labels in place (used by the metric-switching charts) without a full rebuild.
    update(data) {
        if (! this.chart) return;
        this.chart.data = data;
        this.chart.update();
    },

    destroy() {
        if (this.chart) { this.chart.destroy(); this.chart = null; }
    },
}));

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

// Shared Year -> Subject -> Chapter dependent filter bar (see <x-year-subject-filter>).
// The server is authoritative: on every change the form submits and the page re-renders with the
// correct dependent options. Alpine only keeps the controls consistent before that submit —
// disabling Subject until a Year is chosen (outside an all-years view), greying out subjects not
// offered in the chosen Year, and clearing a now-invalid Subject/Chapter. Availability is keyed by
// subject slug: { slug: [levels] }.
Alpine.data('yearSubjectFilter', (config = {}) => ({
    level: config.level ?? '',
    subject: config.subject ?? '',
    chapter: config.chapter ?? '',
    availability: config.availability ?? {},
    allYears: config.allYears ?? false,

    subjectOffered(slug) {
        if (! this.level) return this.allYears;
        return (this.availability[slug] || []).includes(Number(this.level));
    },

    get subjectDisabled() {
        return ! this.level && ! this.allYears;
    },

    submit() {
        if (this.$el.form.requestSubmit) this.$el.form.requestSubmit();
        else this.$el.form.submit();
    },

    onYearChange() {
        // Drop a Subject the new Year does not offer, and any Chapter that hung off it.
        if (this.subject && ! this.subjectOffered(this.subject)) {
            this.subject = '';
            this.chapter = '';
        }
        this.submit();
    },

    onSubjectChange() {
        // A Chapter belongs to a Year+Subject pair, so changing Subject always invalidates it.
        this.chapter = '';
        this.submit();
    },

    onChapterChange() {
        this.submit();
    },
}));

// Interactive metric-switching doughnut (teacher Home content-performance chart). Holds several
// named metrics and swaps the dataset in place when a tab is chosen — no page reload. Clicking a
// segment navigates to that item's destination. The accessible table binds to the `rows` getter,
// so it updates with the chart. Built on the same shared window.Chart integration.
const DONUT_PALETTE = ['#0F7A68', '#2E6CA8', '#B84A75', '#8A6A12', '#C24936', '#8B8AA3'];

Alpine.data('metricDoughnut', (config = {}) => ({
    metrics: config.metrics || {},
    current: config.initial || Object.keys(config.metrics || {})[0] || null,
    chart: null,

    init() {
        const canvas = this.$refs.canvas;
        if (! canvas || ! this.current) return;

        this.chart = new Chart(canvas, {
            type: 'doughnut',
            data: this.dataFor(this.current),
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                animation: prefersReducedMotion() ? false : { duration: 400 },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, color: this.ink(), font: { weight: '600' } } },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const row = this.rows[ctx.dataIndex];
                                return row ? ` ${row.label}: ${row.value} (${row.percent}%)` : '';
                            },
                        },
                    },
                },
                onClick: (event, elements) => {
                    if (! elements.length) return;
                    const row = this.rows[elements[0].index];
                    if (row && row.url) window.location.href = row.url;
                },
            },
        });
    },

    ink() {
        const styles = getComputedStyle(document.documentElement);
        return (styles.getPropertyValue('--c-ink-2') || styles.getPropertyValue('--c-ink') || '#5B6675').trim();
    },

    get rows() {
        const metric = this.metrics[this.current];
        if (! metric) return [];
        const total = metric.total || 1;
        return (metric.items || []).map((item, i) => ({
            ...item,
            percent: Math.round((item.value / total) * 100),
            color: DONUT_PALETTE[i % DONUT_PALETTE.length],
        }));
    },

    get hasData() {
        return this.rows.length > 0;
    },

    dataFor(key) {
        const metric = this.metrics[key] || { items: [], total: 1 };
        const total = metric.total || 1;
        const rows = (metric.items || []).map((item, i) => ({
            ...item,
            color: DONUT_PALETTE[i % DONUT_PALETTE.length],
        }));
        return {
            labels: rows.map((r) => r.label),
            datasets: [{ data: rows.map((r) => r.value), backgroundColor: rows.map((r) => r.color), borderWidth: 0 }],
        };
    },

    setMetric(key) {
        this.current = key;
        if (! this.chart) return;
        this.chart.data = this.dataFor(key);
        this.chart.update();
    },

    destroy() {
        if (this.chart) { this.chart.destroy(); this.chart = null; }
    },
}));

// Interactive Platform Activity line chart (admin Home §4.4). Multiple series over a time period,
// each toggled through an accessible checkbox control (not just the pointer-only Chart.js legend).
// The period itself is switched server-side via links, so the buckets are always real aggregates.
Alpine.data('activityChart', (config = {}) => ({
    labels: config.labels || [],
    series: config.series || [], // [{ key, label, data, color }]
    visible: {},
    chart: null,

    init() {
        this.series.forEach((s) => { this.visible[s.key] = true; });

        const canvas = this.$refs.canvas;
        if (! canvas) return;

        const styles = getComputedStyle(document.documentElement);
        const ink = (styles.getPropertyValue('--c-ink-2') || '#5B6675').trim();
        const line = (styles.getPropertyValue('--c-line') || 'rgba(120,130,150,0.2)').trim();

        this.chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: this.labels,
                datasets: this.series.map((s) => ({
                    label: s.label,
                    data: s.data,
                    borderColor: s.color,
                    backgroundColor: s.color,
                    tension: 0.3,
                    pointRadius: 2,
                    borderWidth: 2,
                    fill: false,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: prefersReducedMotion() ? false : { duration: 400 },
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: line }, ticks: { color: ink } },
                    y: { beginAtZero: true, grid: { color: line }, ticks: { color: ink, precision: 0 } },
                },
            },
        });
    },

    toggle(key) {
        const idx = this.series.findIndex((s) => s.key === key);
        if (idx < 0 || ! this.chart) return;
        this.visible[key] = ! this.visible[key];
        this.chart.setDatasetVisibility(idx, this.visible[key]);
        this.chart.update();
    },

    destroy() {
        if (this.chart) { this.chart.destroy(); this.chart = null; }
    },
}));

Alpine.start();
