@props([
    'for' => null,
    'value' => null,
])

<label
    @if($for) for="{{ $for }}" @endif
    {{ $attributes->class('mb-1 block text-[10px] font-medium uppercase tracking-widest text-neutral-400') }}
>
    {{ $value ?? $slot }}
</label>
