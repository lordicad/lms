@props([
    'config',                 // Chart.js config (array) — required
    'id' => null,
    'title' => null,          // accessible name for the canvas
    'height' => 280,
    'rows' => [],             // accessible table rows: [['label' => ..., 'value' => ...], ...]
    'columns' => null,        // table headers, e.g. [__('Item'), __('Jumlah')]
    'empty' => null,          // message shown when there is no data
    'table' => true,          // show the "view data as a table" fallback
])

@php
    $chartId = $id ?? 'chart-'.\Illuminate\Support\Str::random(6);
    $cols = $columns ?? [__('Item'), __('Jumlah')];
    $hasData = ! empty($rows) || filled(trim($slot->toHtml()));
@endphp

{{--
    Shared chart component (brief §6): a <canvas> plus an accessible table of the same values, so the
    chart is never the only way to read the data. Resizes inside a fixed-height, overflow-safe box.
    Mounts through the one `appChart` Alpine integration (reduced-motion aware, theme-aware).
--}}
<div {{ $attributes }}>
    @if ($hasData)
        <div x-data="appChart(@js($config))">
            <div style="position:relative;width:100%;height:{{ $height }}px;overflow:hidden">
                <canvas x-ref="canvas" id="{{ $chartId }}"
                        role="img"
                        @if ($title) aria-label="{{ $title }}" @endif></canvas>
            </div>
        </div>

        @if ($table)
            <details class="chart-data" style="margin-top:12px">
                <summary style="cursor:pointer;font-size:13px;font-weight:700;color:#6C6F87">
                    {{ __('Lihat data sebagai jadual') }}
                </summary>

                <div style="overflow-x:auto;margin-top:8px">
                    @if (filled(trim($slot->toHtml())))
                        {{ $slot }}
                    @else
                        <table class="chart-table" style="width:100%;border-collapse:collapse;font-size:14px">
                            <thead>
                                <tr>
                                    @foreach ($cols as $col)
                                        <th scope="col" style="text-align:left;padding:6px 10px;border-bottom:1px solid rgba(120,130,150,0.25)">{{ $col }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        <td style="padding:6px 10px;border-bottom:1px solid rgba(120,130,150,0.12)">{{ $row['label'] ?? '' }}</td>
                                        <td style="padding:6px 10px;border-bottom:1px solid rgba(120,130,150,0.12)">{{ $row['value'] ?? '' }}</td>
                                        @if (isset($row['extra']))
                                            <td style="padding:6px 10px;border-bottom:1px solid rgba(120,130,150,0.12)">{{ $row['extra'] }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </details>
        @endif
    @else
        <x-empty emoji="📊" :title="$empty ?? __('Tiada data untuk dipaparkan lagi.')" />
    @endif
</div>
