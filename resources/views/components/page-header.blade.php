@props([
    'eyebrow' => null,
    'title',
    'subtitle' => null,
    'variant' => 'page', // page | hero | legal
])

@php
    $shell = match ($variant) {
        'hero' => 'bg-grid border border-neutral-950 px-4 py-10 sm:px-8 sm:py-14',
        'legal' => 'border border-neutral-950 bg-white px-4 py-6 sm:px-6',
        default => 'bg-grid mb-8 border border-neutral-950 px-4 py-6 sm:px-6',
    };
    $titleClass = $variant === 'hero'
        ? 'max-w-2xl text-3xl font-semibold tracking-tight sm:text-4xl'
        : 'text-2xl font-semibold tracking-tight';
@endphp

<div {{ $attributes->class($shell) }}>
    @if($eyebrow)
        <p class="mono mb-2 text-[10px] font-medium uppercase tracking-[0.3em] text-neutral-500">// {{ $eyebrow }}</p>
    @endif
    <h1 @class([$titleClass])>{{ $title }}</h1>
    @if($subtitle)
        <p @class([
            'mt-2 max-w-3xl text-xs text-neutral-500 sm:text-sm' => $variant !== 'hero',
            'mt-4 max-w-2xl text-sm leading-relaxed text-neutral-600 sm:text-base' => $variant === 'hero',
        ])>{{ $subtitle }}</p>
    @endif
    @isset($actions)
        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
            {{ $actions }}
        </div>
    @endisset
    @isset($meta)
        <div class="mono mt-3 text-[11px] text-neutral-400">{{ $meta }}</div>
    @endisset
    {{ $slot }}
</div>
