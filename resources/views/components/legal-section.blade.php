@props([
    'title',
    'emphasis' => false,
])

<section @class([
    $emphasis ? 'border border-neutral-950 bg-neutral-50 p-4 sm:p-5' : null,
])>
    <h2 class="text-base font-semibold text-neutral-950">{{ $title }}</h2>
    <div class="mt-2 space-y-3">
        {{ $slot }}
    </div>
</section>
