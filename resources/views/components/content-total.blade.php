@props([
    'total',                  // all-time count of records owned by the signed-in teacher
    'label',                  // e.g. __('Jumlah video')
    'filtered' => null,       // count under the active filter
    'filteredActive' => false,
])

{{-- Teacher content-index total (brief §2.6). The primary number is always all owned records; a
     filtered count is shown alongside with its own label, never in place of the ownership total. --}}
<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <span style="display:inline-flex;align-items:center;gap:8px;background:#E4EEF9;color:#2E6CA8;border-radius:999px;padding:6px 14px;font-family:'Geist',sans-serif;font-weight:800;font-size:13px">
        {{ $label }}: {{ number_format($total) }}
    </span>

    @if ($filteredActive && $filtered !== null && (int) $filtered !== (int) $total)
        <span class="tp-meta">{{ __('Ditapis: :count', ['count' => number_format($filtered)]) }}</span>
    @endif
</div>
