@props([
    'padded' => true,
])

@php
    $wrap = $padded
        ? 'mx-auto max-w-6xl px-4 pt-4 sm:px-6'
        : 'mx-4 mt-4 sm:mx-6';
@endphp

@if(session('success') || session('error'))
    <div {{ $attributes->class($wrap.' space-y-3') }}>
        @if(session('success'))
            <div class="border border-neutral-950 bg-white px-4 py-3 text-sm">
                <span class="mr-2 text-green-600">[OK]</span>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="border border-red-700 bg-white px-4 py-3 text-sm">
                <span class="mr-2 text-red-700">[!]</span>{{ session('error') }}
            </div>
        @endif
    </div>
@endif
