@props([
    'variant' => 'footer', // footer | guest | cross
    'exclude' => null,
])

@php
    $items = [
        'terms' => ['route' => 'legal.terms', 'label' => __('legal.nav_terms')],
        'privacy' => ['route' => 'legal.privacy', 'label' => __('legal.nav_privacy')],
        'cookies' => ['route' => 'legal.cookies', 'label' => __('legal.nav_cookies')],
    ];

    if ($exclude) {
        unset($items[$exclude]);
    }

    $navClass = match ($variant) {
        'guest' => 'flex flex-wrap items-center justify-center gap-x-1 gap-y-1 text-[11px] text-neutral-500',
        'cross' => 'flex flex-wrap items-center gap-x-1 gap-y-1 text-xs text-neutral-500',
        default => 'flex flex-wrap items-center gap-x-1 gap-y-1 text-xs text-neutral-500',
    };

    $linkClass = match ($variant) {
        'cross' => 'underline underline-offset-2 transition-colors hover:text-neutral-950',
        default => 'px-2 transition-colors hover:text-neutral-950',
    };
@endphp

<nav {{ $attributes->class($navClass) }} aria-label="{{ __('legal.nav_terms') }}">
    @foreach($items as $key => $item)
        @if(! $loop->first)
            <span class="select-none text-neutral-300" aria-hidden="true">·</span>
        @endif
        <a
            href="{{ route($item['route']) }}"
            @class([
                $linkClass,
                'font-medium text-neutral-950' => request()->routeIs($item['route']),
            ])
        >{{ $item['label'] }}</a>
    @endforeach
</nav>
