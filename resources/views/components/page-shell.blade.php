@props([
    'wide' => true,
])

<div {{ $attributes->class([
    'mx-auto px-4 py-6 sm:px-6 sm:py-10',
    'max-w-6xl' => $wide,
    'max-w-3xl' => ! $wide,
]) }}>
    {{ $slot }}
</div>
