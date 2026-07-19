{{-- Kandungan sub-tabs. In the prototype these switch client-side; here each is its own route, so the
     pills are links that carry their active state. `$active` is one of 'video' | 'bahan' | 'kuiz'. --}}
@php
    $tabs = [
        ['id' => 'video', 'label' => __('Video'), 'route' => 'admin.kandungan.video'],
        ['id' => 'bahan', 'label' => __('Bahan'), 'route' => 'admin.kandungan.bahan'],
        ['id' => 'kuiz',  'label' => __('Kuiz'),  'route' => 'admin.kandungan.kuiz'],
    ];
@endphp
<div style="display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap">
    @foreach ($tabs as $t)
        @php($on = $active === $t['id'])
        <a href="{{ route($t['route']) }}"
           style="min-height:44px;display:inline-flex;align-items:center;cursor:pointer;border-radius:999px;padding:0 20px;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;transition:all .15s;{{ $on ? 'border:none;background:#17907B;color:#fff' : 'border:1.5px solid rgba(46,44,80,.12);background:#fff;color:#28293F' }}">{{ $t['label'] }}</a>
    @endforeach
</div>
