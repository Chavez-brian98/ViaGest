@php
    $items = $items ?? [];
    $ultimo = count($items) - 1;
@endphp

@if ($items !== [])
    <nav aria-label="Ruta de navegación" class="flex flex-wrap items-center gap-1.5 text-xs">
        @foreach ($items as $indice => $item)
            @if ($indice > 0)
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5 shrink-0 text-ink/25" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
            @endif

            @if ($indice < $ultimo && ! empty($item['url']))
                <a href="{{ $item['url'] }}" class="font-medium text-ink/45 transition hover:text-primary focus:outline-none focus:ring-2 focus:ring-secondary/40">
                    {{ $item['label'] }}
                </a>
            @elseif ($indice === $ultimo)
                <span aria-current="page" class="font-semibold text-ink/70">{{ $item['label'] }}</span>
            @else
                <span class="font-medium text-ink/45">{{ $item['label'] }}</span>
            @endif
        @endforeach
    </nav>
@endif
