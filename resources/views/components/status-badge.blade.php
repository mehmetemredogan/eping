@props([
    'status',
])

@php
    $tone = match ($status) {
        'success' => 'text-green-600',
        'timeout' => 'text-yellow-600',
        'failed' => 'text-red-600',
        default => 'text-neutral-500',
    };
    $label = match ($status) {
        'success' => __('ping.status_success'),
        'timeout' => __('ping.status_timeout'),
        'failed' => __('ping.status_failed'),
        default => $status,
    };
@endphp

<span {{ $attributes->class(['text-xs', $tone]) }}>{{ $label }}</span>
