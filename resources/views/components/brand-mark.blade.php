@props([
    'href' => null,
    'size' => 'md', // sm | md | lg
    'showLabel' => true,
])

@php
    $box = match ($size) {
        'sm' => 'h-5 w-5 text-[9px]',
        'lg' => 'h-7 w-7 text-[11px]',
        default => 'h-6 w-6 text-[10px]',
    };
    $label = match ($size) {
        'lg' => 'text-base',
        default => 'text-sm',
    };
    $href = $href ?? route('home');
@endphp

<a href="{{ $href }}" {{ $attributes->class('inline-flex shrink-0 items-center gap-2 font-semibold tracking-tight text-neutral-950') }}>
    <span @class(['flex items-center justify-center border border-neutral-950 bg-neutral-950 text-white', $box])>></span>
    @if($showLabel)
        <span @class([$label])>PING</span>
    @endif
</a>
