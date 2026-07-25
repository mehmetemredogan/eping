@props([
    'ms' => null,
    'suffix' => ' ms',
])

@php
    $value = $ms !== null && $ms !== '' ? (float) $ms : null;
    $tone = $value === null
        ? 'text-neutral-400'
        : ($value < 80 ? 'text-green-600' : ($value < 180 ? 'text-yellow-600' : 'text-red-600'));
    $label = $value === null ? '—' : (fmod($value, 1.0) === 0.0 ? (string) (int) $value : rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.')).$suffix;
@endphp

<span {{ $attributes->class(['mono', $tone]) }}>{{ $label }}</span>
