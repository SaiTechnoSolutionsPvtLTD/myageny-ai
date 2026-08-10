@php
    $startPage = max(1, $paginator->currentPage() - 2);
    $endPage   = min($paginator->lastPage(), $paginator->currentPage() + 2);

    $pageName  = $paginator->getPageName();
    
    // Collect all request parameters, converting nulls to empty strings ''
    // so http_build_query retains keys like date_from=, search=, branch_id= etc.
    $allParams = collect(request()->all())
        ->except(['_token', '_method'])
        ->map(fn($val) => $val ?? '')
        ->toArray();

    $getPageUrl = function($page) use ($allParams, $pageName) {
        $params = array_merge($allParams, [$pageName => $page]);
        return url()->current() . '?' . http_build_query($params);
    };
@endphp

<div class="app-pagination">
    <div class="app-pagination__info">
        Showing
        <strong>{{ $paginator->firstItem() ?? 0 }}-{{ $paginator->lastItem() ?? 0 }}</strong>
        of
        <strong>{{ $paginator->total() }}</strong>
    </div>

    <div class="app-pagination__links">
        {{-- Prev --}}
        @if($paginator->onFirstPage())
            <span class="app-pagination__link is-disabled">Prev</span>
        @else
            <a href="{{ $getPageUrl($paginator->currentPage() - 1) }}" class="app-pagination__link">Prev</a>
        @endif

        {{-- First page + ellipsis --}}
        @if($startPage > 1)
            <a href="{{ $getPageUrl(1) }}" class="app-pagination__link">1</a>
            @if($startPage > 2)
                <span class="app-pagination__ellipsis">...</span>
            @endif
        @endif

        {{-- Page number links --}}
        @foreach(range($startPage, $endPage) as $pg)
            <a href="{{ $getPageUrl($pg) }}"
               class="app-pagination__link {{ $pg === $paginator->currentPage() ? 'is-active' : '' }}">
                {{ $pg }}
            </a>
        @endforeach

        {{-- Last page + ellipsis --}}
        @if($endPage < $paginator->lastPage())
            @if($endPage < $paginator->lastPage() - 1)
                <span class="app-pagination__ellipsis">...</span>
            @endif
            <a href="{{ $getPageUrl($paginator->lastPage()) }}" class="app-pagination__link">{{ $paginator->lastPage() }}</a>
        @endif

        {{-- Next --}}
        @if($paginator->hasMorePages())
            <a href="{{ $getPageUrl($paginator->currentPage() + 1) }}" class="app-pagination__link">Next</a>
        @else
            <span class="app-pagination__link is-disabled">Next</span>
        @endif
    </div>
</div>
