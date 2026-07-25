@props([
    'title',
    'eyebrow',
    'updated',
    'exclude',
])

<x-ping-layout :title="$title">
    <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 sm:py-10">
        <x-page-header :eyebrow="$eyebrow" :title="$title" variant="legal">
            <x-slot:meta>{{ $updated }}</x-slot:meta>
        </x-page-header>

        @isset($intro)
            <p class="mt-6 text-sm leading-relaxed text-neutral-700">{{ $intro }}</p>
        @endisset

        <div class="mt-8 space-y-8 text-sm leading-relaxed text-neutral-700">
            {{ $slot }}
        </div>

        <div class="mt-10 border-t border-neutral-200 pt-4">
            <x-legal-nav variant="cross" :exclude="$exclude" />
        </div>
    </div>
</x-ping-layout>
