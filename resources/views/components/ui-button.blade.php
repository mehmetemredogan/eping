@props([
    'variant' => 'primary', // primary | secondary | ghost | danger
    'type' => 'submit',
    'href' => null,
    'block' => false,
    'disabled' => false,
])

@php
    $base = 'inline-flex items-center justify-center border px-4 py-2 text-sm font-medium transition-colors disabled:opacity-50';
    $variants = [
        'primary' => 'border-neutral-950 bg-neutral-950 text-white hover:bg-white hover:text-neutral-950',
        'secondary' => 'border-neutral-950 bg-white text-neutral-950 hover:bg-neutral-950 hover:text-white',
        'ghost' => 'border-transparent bg-transparent text-neutral-600 hover:text-neutral-950',
        'danger' => 'border-red-700 bg-white text-red-700 hover:bg-red-700 hover:text-white',
    ];
    $classes = trim($base.' '.($variants[$variant] ?? $variants['primary']).($block ? ' w-full' : ''));
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }} @if($disabled) aria-disabled="true" @endif>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }} @disabled($disabled)>
        {{ $slot }}
    </button>
@endif

