@if ($paginator->hasPages())
    <nav class="flex items-center justify-center gap-1 mt-10" aria-label="Paginasi">
        @if ($paginator->onFirstPage())
            <span class="w-10 h-10 inline-flex items-center justify-center rounded-full text-sm text-gray-300">&laquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="w-10 h-10 inline-flex items-center justify-center rounded-full text-sm hover:bg-white">&laquo;</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-3 py-2 text-sm text-muted">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="w-10 h-10 inline-flex items-center justify-center rounded-full bg-ink text-white text-sm font-semibold">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="w-10 h-10 inline-flex items-center justify-center rounded-full text-sm hover:bg-white">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="w-10 h-10 inline-flex items-center justify-center rounded-full text-sm hover:bg-white">&raquo;</a>
        @else
            <span class="w-10 h-10 inline-flex items-center justify-center rounded-full text-sm text-gray-300">&raquo;</span>
        @endif
    </nav>
@endif
