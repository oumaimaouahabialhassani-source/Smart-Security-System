@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Pagination">
        <span class="pagination-info">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </span>

        <ul class="pagination-list">
            {{-- Previous --}}
            <li>
                @if ($paginator->onFirstPage())
                    <span class="page-link is-disabled" aria-hidden="true">‹</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" class="page-link" rel="prev" aria-label="Previous page">‹</a>
                @endif
            </li>

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="page-link is-disabled">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="page-link is-current" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="page-link">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="page-link" rel="next" aria-label="Next page">›</a>
                @else
                    <span class="page-link is-disabled" aria-hidden="true">›</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
