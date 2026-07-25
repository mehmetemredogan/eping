@props([
    'title' => null,
    'variant' => 'bar', // bar | plain
])

<section {{ $attributes->class('border border-neutral-950 bg-white') }}>
    @if($title && $variant === 'bar')
        <div class="border-b border-neutral-950 bg-neutral-50 px-4 py-3">
            <h2 class="text-sm font-semibold uppercase tracking-widest">{{ $title }}</h2>
        </div>
    @endif
    <div @class([
        'p-4 sm:p-6' => $variant !== 'plain',
        'p-6 sm:p-8' => $variant === 'plain',
    ])>
        @if($title && $variant === 'plain')
            <h2 class="mb-5 text-lg font-semibold tracking-tight">{{ $title }}</h2>
        @endif
        {{ $slot }}
    </div>
</section>
