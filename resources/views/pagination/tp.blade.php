@php($en = app()->getLocale() === 'en')
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ $en ? 'Pagination Navigation' : 'Navigasi Halaman' }}" style="margin-top:20px">

        {{-- Mobile: Previous / Next as the same ghost buttons used elsewhere in the portal. --}}
        <div class="tp-pg-mobile" style="display:none;gap:10px;align-items:center;justify-content:space-between">
            @if ($paginator->onFirstPage())
                <span class="tp-btn-ghost" style="opacity:.45;cursor:not-allowed">{{ $en ? 'Previous' : 'Sebelumnya' }}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="tp-btn-ghost">{{ $en ? 'Previous' : 'Sebelumnya' }}</a>
            @endif
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="tp-btn-ghost">{{ $en ? 'Next' : 'Seterusnya' }}</a>
            @else
                <span class="tp-btn-ghost" style="opacity:.45;cursor:not-allowed">{{ $en ? 'Next' : 'Seterusnya' }}</span>
            @endif
        </div>

        {{-- Desktop: results summary + numbered links. --}}
        <div class="tp-pg-desktop" style="align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <p style="margin:0;font-size:13px;color:var(--tp-muted)">
                {{ $en ? 'Showing' : 'Memaparkan' }}
                @if ($paginator->firstItem())
                    <b style="color:var(--tp-ink)">{{ $paginator->firstItem() }}</b> {{ $en ? 'to' : 'hingga' }} <b style="color:var(--tp-ink)">{{ $paginator->lastItem() }}</b>
                @else
                    {{ $paginator->count() }}
                @endif
                {{ $en ? 'of' : 'daripada' }} <b style="color:var(--tp-ink)">{{ $paginator->total() }}</b> {{ $en ? 'results' : 'hasil' }}
            </p>

            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                @if ($paginator->onFirstPage())
                    <span class="tp-pg-num is-disabled" aria-hidden="true">‹</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="tp-pg-num" aria-label="{{ $en ? 'Previous' : 'Sebelumnya' }}">‹</a>
                @endif

                @isset($elements)
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="tp-pg-num is-disabled">{{ $element }}</span>
                        @endif
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span class="tp-pg-num is-active" aria-current="page">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="tp-pg-num" aria-label="{{ $en ? 'Go to page '.$page : 'Ke halaman '.$page }}">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                @endisset

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="tp-pg-num" aria-label="{{ $en ? 'Next' : 'Seterusnya' }}">›</a>
                @else
                    <span class="tp-pg-num is-disabled" aria-hidden="true">›</span>
                @endif
            </div>
        </div>
    </nav>
@endif
