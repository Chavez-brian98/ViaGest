@php
    $etiqueta = $etiqueta ?? 'registros';
    $elements = [];

    if ($paginator->hasPages()) {
        $window = \Illuminate\Pagination\UrlWindow::make($paginator);
        $elements = array_filter([
            $window['first'],
            is_array($window['slider']) ? '...' : null,
            $window['slider'],
            is_array($window['last']) ? '...' : null,
            $window['last'],
        ]);
    }
@endphp

@if ($paginator->total() > 0)
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-ink/45">
            Mostrando
            <span class="font-semibold text-ink/70">{{ $paginator->firstItem() }}</span>
            a
            <span class="font-semibold text-ink/70">{{ $paginator->lastItem() }}</span>
            de
            <span class="font-semibold text-ink/70">{{ $paginator->total() }}</span>
            {{ $etiqueta }}
        </p>

        @if ($paginator->hasPages())
            <nav role="navigation" aria-label="Paginación" class="flex items-center gap-1">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="Página anterior" class="flex h-8 w-8 items-center justify-center rounded-lg border border-ink/10 text-ink/25">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Página anterior" class="flex h-8 w-8 items-center justify-center rounded-lg border border-ink/15 text-ink/55 transition hover:border-secondary/40 hover:bg-secondary/10 hover:text-primary focus:outline-none focus:ring-2 focus:ring-secondary/40">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-disabled="true" class="flex h-8 min-w-8 items-center justify-center px-1 text-xs font-medium text-ink/35">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $pagina => $url)
                            @if ($pagina == $paginator->currentPage())
                                <span aria-current="page" class="flex h-8 min-w-8 items-center justify-center rounded-lg bg-primary px-2 text-xs font-semibold text-white shadow-sm">{{ $pagina }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="Página {{ $pagina }}" class="flex h-8 min-w-8 items-center justify-center rounded-lg border border-ink/15 px-2 text-xs font-medium text-ink/60 transition hover:border-secondary/40 hover:bg-secondary/10 hover:text-primary focus:outline-none focus:ring-2 focus:ring-secondary/40">{{ $pagina }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Página siguiente" class="flex h-8 w-8 items-center justify-center rounded-lg border border-ink/15 text-ink/55 transition hover:border-secondary/40 hover:bg-secondary/10 hover:text-primary focus:outline-none focus:ring-2 focus:ring-secondary/40">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="Página siguiente" class="flex h-8 w-8 items-center justify-center rounded-lg border border-ink/10 text-ink/25">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                @endif
            </nav>
        @endif
    </div>
@endif
